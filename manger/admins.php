<?php
// admins.php
include('db.php');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['error'] = 'غير مصرح بالوصول لهذه الصفحة';
    header("Location: ../login.php");
    exit();
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        // Validate input
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'يرجى ملء جميع الحقول المطلوبة';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'يجب أن تتكون كلمة المرور من 6 أحرف على الأقل';
        } else {
            // Check if username exists (use admin_id column)
            $check = $conn->prepare("SELECT admin_id FROM admins WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $_SESSION['error'] = 'اسم المستخدم موجود مسبقاً';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // جدول admins في بعض النسخ يحتوي فقط على admin_id, username, password
                $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $hashed_password);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'تمت إضافة المشرف بنجاح';
                } else {
                    $_SESSION['error'] = 'حدث خطأ أثناء إضافة المشرف: ' . $conn->error;
                }
                $stmt->close();
            }
            $check->close();
        }
    } 
    elseif (isset($_POST['update_admin'])) {
        $id = (int)$_POST['id'];
        $password = $_POST['password'] ?? '';
        
        // Prevent modifying other admins if not super admin
        if ($_SESSION['is_admin'] != 1 && $id != $_SESSION['admin_id']) {
            $_SESSION['error'] = 'غير مصرح لك بهذا الإجراء';
        } else {
                if (!empty($password)) {
                if (strlen($password) < 6) {
                    $_SESSION['error'] = 'يجب أن تتكون كلمة المرور من 6 أحرف على الأقل';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // تحديث كلمة المرور فقط لأن جدول admins قد لا يحتوي على عمود is_admin
                    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                    $stmt->bind_param("si", $hashed_password, $id);
                }
            } else {
                // لا يوجد تحديث آخر متوفر في هذا الإصدار
                $stmt = null;
            }
            
            if (empty($_SESSION['error']) && $stmt) {
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'تم تحديث بيانات المشرف بنجاح';
                } else {
                    $_SESSION['error'] = 'حدث خطأ أثناء تحديث بيانات المشرف: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
    elseif (isset($_POST['delete_admin'])) {
        $id = (int)$_POST['id'];
        
        // Prevent deleting self
        if ($id == $_SESSION['admin_id']) {
            $_SESSION['error'] = 'لا يمكنك حذف حسابك الخاص';
        } else {
            $stmt = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'تم حذف المشرف بنجاح';
            } else {
                $_SESSION['error'] = 'حدث خطأ أثناء حذف المشرف: ' . $conn->error;
            }
            $stmt->close();
        }
    }
    
    header("Location: admins.php");
    exit();
}

// Get all admins with proper error handling
try {
    // جلب المشرفين (قد لا تتوفر أعمدة is_admin/created_at في بعض النسخ)
    $admins = $conn->query("SELECT admin_id AS id, username FROM admins ORDER BY admin_id DESC");
    if (!$admins) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ في جلب بيانات المشرفين: ' . $e->getMessage();
    $admins = [];
}

// Include header
$page_title = 'إدارة المشرفين';
include('includes/header.php');
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">إدارة المشرفين</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus me-1"></i> إضافة مشرف جديد
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="adminsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المستخدم</th>
                            <th>صلاحية المدير</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($admins->num_rows > 0): ?>
                            <?php $i = 1; while($admin = $admins->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td>
                                        <?php $is_admin_flag = isset($admin['is_admin']) ? $admin['is_admin'] : 0; ?>
                                        <?php if ($is_admin_flag == 1): ?>
                                            <span class="badge bg-success">مدير</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">مستخدم</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo isset($admin['created_at']) ? date('Y/m/d', strtotime($admin['created_at'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($admin['id'] != $_SESSION['admin_id'] || $_SESSION['is_admin'] == 1): ?>
                                                <button class="btn btn-sm btn-primary edit-admin" 
                                                    data-id="<?php echo $admin['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                    data-is-admin="<?php echo $is_admin_flag; ?>">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </button>
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                <button class="btn btn-sm btn-danger delete-admin" 
                                                        data-id="<?php echo $admin['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($admin['username']); ?>">
                                                    <i class="fas fa-trash me-1"></i> حذف
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">الحساب الحالي</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">لا يوجد مشرفين مسجلين</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">إضافة مشرف جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAdminForm" method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">يجب أن تتكون كلمة المرور من 6 أحرف على الأقل</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" value="1">
                        <label class="form-check-label" for="is_admin">مدير النظام (صلاحيات كاملة)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="add_admin" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">تعديل بيانات المشرف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm" method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="update_admin" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" class="form-control" id="edit_username" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <div class="form-text">اترك الحقل فارغاً إذا كنت لا تريد تغيير كلمة المرور</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_admin" name="is_admin" value="1">
                        <label class="form-check-label" for="edit_is_admin">مدير النظام (صلاحيات كاملة)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAdminModalLabel">تأكيد الحذف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteAdminForm" method="POST" action="">
                <input type="hidden" name="id" id="delete_id">
                <input type="hidden" name="delete_admin" value="1">
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف المشرف "<span id="delete_admin_username"></span>"؟</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>تحذير: لا يمكن التراجع عن هذه العملية.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">حذف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#adminsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        },
        "order": [[0, "asc"]],
        "responsive": true,
        "pageLength": 25
    });

    // Handle edit admin button click
    $(document).on('click', '.edit-admin', function() {
        var id = $(this).data('id');
        var username = $(this).data('username');
        var isAdmin = $(this).data('is-admin');
        
        $('#edit_id').val(id);
        $('#edit_username').val(username);
        $('#edit_is_admin').prop('checked', isAdmin == 1);
        
        var editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
        editModal.show();
    });

    // Handle delete admin button click
    $(document).on('click', '.delete-admin', function() {
        var id = $(this).data('id');
        var username = $(this).data('username');
        
        $('#delete_id').val(id);
        $('#delete_admin_username').text(username);
        
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
        deleteModal.show();
    });

    // Form validation
    $('#addAdminForm').on('submit', function(e) {
        var password = $('#password').val();
        
        if (password.length < 6) {
            e.preventDefault();
            alert('يجب أن تتكون كلمة المرور من 6 أحرف على الأقل');
            return false;
        }
        return true;
    });
    
    $('#editAdminForm').on('submit', function(e) {
        var password = $('#edit_password').val();
        
        if (password.length > 0 && password.length < 6) {
            e.preventDefault();
            alert('يجب أن تتكون كلمة المرور من 6 أحرف على الأقل');
            return false;
        }
        return true;
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>