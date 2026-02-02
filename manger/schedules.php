<?php
// تضمين الاتصال بقاعدة البيانات
include('db.php');

// التحقق من تسجيل الدخول
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// معالجة الحذف
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // الحصول على اسم الملف قبل الحذف
    $file_query = $conn->query("SELECT file_name FROM schedules WHERE id = $delete_id");
    if ($file_query->num_rows > 0) {
        $file_row = $file_query->fetch_assoc();
        $file_path = "uploads/" . $file_row['file_name'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $conn->query("DELETE FROM schedules WHERE id = $delete_id");
    header("Location: schedules.php?deleted=1");
    exit();
}

// معالجة الإضافة
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $term = $conn->real_escape_string($_POST['term']);
    $academic_year = $conn->real_escape_string($_POST['academic_year']);
    $department_id = intval($_POST['department_id']);
    
    // معالجة رفع الملف
    if (isset($_FILES['schedule_file']) && $_FILES['schedule_file']['error'] === 0) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['schedule_file']['type'];
        $file_size = $_FILES['schedule_file']['size'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file_type, $allowed_types)) {
            $error_msg = 'نوع الملف غير مسموح. يُسمح فقط بـ PDF والصور.';
        } elseif ($file_size > $max_size) {
            $error_msg = 'حجم الملف كبير جداً. الحد الأقصى 10MB.';
        } else {
            $file_ext = pathinfo($_FILES['schedule_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'schedule_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = 'uploads/' . $file_name;
            
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['schedule_file']['tmp_name'], $upload_path)) {
                $sql = "INSERT INTO schedules (file_name, term, academic_year, department_id) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $file_name, $term, $academic_year, $department_id);
                
                if ($stmt->execute()) {
                    $success_msg = 'تم رفع الجدول بنجاح!';
                } else {
                    $error_msg = 'حدث خطأ أثناء حفظ البيانات.';
                }
            } else {
                $error_msg = 'فشل في رفع الملف.';
            }
        }
    } else {
        $error_msg = 'الرجاء اختيار ملف للرفع.';
    }
}

// جلب الأقسام
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// جلب الجداول مع أسماء الأقسام
$schedules_query = "
    SELECT s.*, d.name as department_name 
    FROM schedules s 
    LEFT JOIN departments d ON s.department_id = d.id 
    ORDER BY s.id DESC
";
$schedules = $conn->query($schedules_query);

if (isset($_GET['deleted'])) {
    $success_msg = 'تم حذف الجدول بنجاح!';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الجداول - جامعة تعز فرع التربة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #0a1628;
            --primary: #1a365d;
            --primary-light: #2563eb;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --bg-dark: #0f172a;
            --bg-medium: #1e293b;
            --bg-glass: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-glass: 1px solid rgba(255, 255, 255, 0.1);
            --transition: all 0.3s ease;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --success: #22c55e;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(245, 158, 11, 0.1) 0%, transparent 50%);
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 20px 0;
            z-index: 1000;
            border-left: var(--border-glass);
        }

        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: var(--border-glass);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            margin-bottom: 15px;
        }

        .sidebar-header h3 {
            font-size: 1.2rem;
            color: var(--text-primary);
        }

        .nav-menu {
            list-style: none;
            padding: 0 15px;
        }

        .nav-menu li {
            margin-bottom: 8px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: var(--transition);
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: rgba(245, 158, 11, 0.2);
            color: var(--accent);
        }

        .nav-menu a i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-right: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Cards */
        .card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            border: var(--border-glass);
            padding: 30px;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: var(--border-glass);
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .card-header h3 {
            font-size: 1.3rem;
            margin: 0;
        }

        /* Form */
        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border-radius: var(--radius-lg);
            padding: 14px 18px;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            background: rgba(245, 158, 11, 0.05);
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-select option {
            background: var(--bg-dark);
            color: var(--text-primary);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--primary-dark);
            border: none;
            padding: 14px 30px;
            border-radius: 50px;
            font-weight: 700;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            color: var(--primary-dark);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        .btn-info {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
            border: 1px solid rgba(37, 99, 235, 0.3);
        }

        .btn-info:hover {
            background: var(--primary-light);
            color: white;
        }

        /* Table */
        .table {
            color: var(--text-primary);
        }

        .table thead th {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent);
            font-weight: 700;
            padding: 18px;
            border: none;
        }

        .table tbody td {
            padding: 16px 18px;
            border-bottom: var(--border-glass);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Alerts */
        .alert {
            border-radius: var(--radius-lg);
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: none;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            padding: 40px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--accent);
            background: rgba(245, 158, 11, 0.05);
        }

        .file-upload i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .file-upload input {
            display: none;
        }

        /* Badge */
        .badge {
            padding: 8px 14px;
            border-radius: 50px;
            font-weight: 600;
        }

        .badge-department {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
            }
            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../img/colleg.jpg" alt="شعار الجامعة" class="sidebar-logo">
            <h3>لوحة التحكم</h3>
        </div>
        <ul class="nav-menu">
            <li><a href="admin_panel.php"><i class="fas fa-tachometer-alt"></i> الرئيسية</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> الطلاب</a></li>
            <li><a href="departments.php"><i class="fas fa-building"></i> الأقسام</a></li>
            <li><a href="schedules.php" class="active"><i class="fas fa-calendar-alt"></i> الجداول</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> التقارير</a></li>
            <li><a href="admins.php"><i class="fas fa-users-cog"></i> المشرفين</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> إدارة الجداول الدراسية</h1>
            <a href="../home.html" class="btn btn-info">
                <i class="fas fa-home"></i> الموقع الرئيسي
            </a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cloud-upload-alt"></i>
                <h3>رفع جدول جديد</h3>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">الفصل الدراسي</label>
                        <select name="term" class="form-select" required>
                            <option value="">اختر الفصل</option>
                            <option value="الأول">الفصل الأول</option>
                            <option value="الثاني">الفصل الثاني</option>
                            <option value="الصيفي">الفصل الصيفي</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العام الأكاديمي</label>
                        <input type="text" name="academic_year" class="form-control" 
                               placeholder="مثال: 2024-2025" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القسم</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">اختر القسم</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملف الجدول (PDF أو صورة)</label>
                        <label class="file-upload w-100" id="fileUploadLabel">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>اسحب الملف هنا أو انقر للاختيار</p>
                            <small class="text-muted">PDF, JPG, PNG - الحد الأقصى 10MB</small>
                            <input type="file" name="schedule_file" id="scheduleFile" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                        </label>
                        <div id="fileName" class="mt-2 text-center" style="color: var(--accent);"></div>
                    </div>
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> رفع الجدول
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Schedules List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                <h3>الجداول المرفوعة</h3>
            </div>
            
            <?php if ($schedules->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الملف</th>
                                <th>القسم</th>
                                <th>الفصل</th>
                                <th>العام الأكاديمي</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; while ($schedule = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <i class="fas fa-file-pdf" style="color: var(--danger);"></i>
                                        <?php echo htmlspecialchars($schedule['file_name']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-department">
                                            <?php echo htmlspecialchars($schedule['department_name'] ?? 'غير محدد'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['term']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['academic_year']); ?></td>
                                    <td>
                                        <a href="uploads/<?php echo $schedule['file_name']; ?>" 
                                           class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?delete=<?php echo $schedule['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('هل أنت متأكد من حذف هذا الجدول؟');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h4>لا توجد جداول مرفوعة</h4>
                    <p>قم برفع أول جدول باستخدام النموذج أعلاه</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // File upload preview
        document.getElementById('scheduleFile').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.getElementById('fileName').innerHTML = 
                    '<i class="fas fa-check-circle"></i> تم اختيار: ' + fileName;
            }
        });
    </script>
</body>
</html>
