<?php
include('db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get current admin data
$admin_id = $_SESSION['admin_id'];
$admin = [];
$tmp = $conn->query("SELECT * FROM admins WHERE admin_id = $admin_id");
if ($tmp && $tmp->num_rows > 0) {
    $admin = $tmp->fetch_assoc();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate current password if changing password
    if (!empty($new_password)) {
        if (!password_verify($current_password, $admin['password'])) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'كلمة المرور الجديدة غير متطابقة';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'يجب أن تكون كلمة المرور الجديدة 6 أحرف على الأقل';
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
            if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE admin_id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE admin_id = ?");
            $stmt->bind_param("si", $username, $admin_id);
        }
        
            if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $success = 'تم تحديث الملف الشخصي بنجاح';
            // Refresh admin data
                $tmp = $conn->query("SELECT * FROM admins WHERE admin_id = $admin_id");
                if ($tmp && $tmp->num_rows > 0) $admin = $tmp->fetch_assoc();
        } else {
            $errors[] = 'حدث خطأ أثناء تحديث الملف الشخصي';
        }
        
        $stmt->close();
    }
}

// Include header
$page_title = 'الملف الشخصي';
include('includes/header.php');
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">الملف الشخصي</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">معلومات الحساب</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <div class="profile-picture">
                            <div class="initials">
                                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                            </div>
                        </div>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($admin['username']); ?></h4>
                    <p class="text-muted mb-3">
                        <?php echo (isset($admin['is_admin']) && $admin['is_admin'] == 1) ? 'مدير النظام' : 'مشرف'; ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        مسجل منذ <?php echo isset($admin['created_at']) ? date('Y/m/d', strtotime($admin['created_at'])) : '-'; ?>
                    </p>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">إحصائيات</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>عدد الطلاب</span>
                        <span class="fw-bold">
                            <?php 
                            $count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
                            echo number_format($count);
                            ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>الجداول الدراسية</span>
                        <span class="fw-bold">
                            <?php 
                            $count = $conn->query("SELECT COUNT(*) as count FROM schedules")->fetch_assoc()['count'];
                            echo number_format($count);
                            ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>الأقسام</span>
                        <span class="fw-bold">
                            <?php 
                            $count = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
                            echo number_format($count);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">تعديل الملف الشخصي</h6>
                </div>
                <div class="card-body">
                    <form id="profileForm" method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">اسم المستخدم</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3">تغيير كلمة المرور</h5>
                        <p class="text-muted small mb-4">اترك الحقول التالية فارغة إذا كنت لا تريد تغيير كلمة المرور</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">نشاط النظام</h6>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php 
                        // Get recent activities (this is a simplified example)
                        $activities = [
                            ['icon' => 'user-plus', 'text' => 'تم تسجيل الدخول بنجاح', 'time' => 'منذ دقيقتين'],
                            ['icon' => 'file-upload', 'text' => 'تم رفع جدول دراسي جديد', 'time' => 'منذ ساعة'],
                            ['icon' => 'user-edit', 'text' => 'تم تحديث بيانات الطالب #1234', 'time' => 'منذ 3 ساعات'],
                            ['icon' => 'file-export', 'text' => 'تم تصدير قائمة الطلاب', 'time' => 'بالأمس'],
                            ['icon' => 'user-shield', 'text' => 'تم تحديث صلاحيات المستخدم', 'time' => 'منذ يومين']
                        ];
                        
                        foreach ($activities as $activity):
                        ?>
                            <div class="activity-item d-flex mb-3">
                                <div class="activity-icon me-3">
                                    <i class="fas fa-<?php echo $activity['icon']; ?> text-primary"></i>
                                </div>
                                <div class="activity-content flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo $activity['text']; ?></span>
                                        <small class="text-muted"><?php echo $activity['time']; ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-picture {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: bold;
}

.initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.activity-item {
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f8f9fc;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php include('includes/footer.php'); ?>

<script>
// Form validation
(function() {
    'use strict';
    
    var form = document.getElementById('profileForm');
    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('كلمات المرور غير متطابقة');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
})();
</script>
