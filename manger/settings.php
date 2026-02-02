<?php
// إعداد صفحة الإعدادات
include 'db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// تحميل معلومات المشرف
$user_id = intval($_SESSION['admin_id']);
$user_info = [];
$u = $conn->query("SELECT * FROM admins WHERE admin_id = $user_id");
if ($u && $u->num_rows > 0) {
    $user_info = $u->fetch_assoc();
}

// رسائل الحالة
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// معالجة النماذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = $conn->real_escape_string($_POST['username'] ?? '');
        $conn->query("UPDATE admins SET username = '$username' WHERE admin_id = $user_id");
        $_SESSION['admin_username'] = $username;
        $_SESSION['success'] = 'تم تحديث الملف الشخصي بنجاح.';
        header('Location: settings.php');
        exit();
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $res = $conn->query("SELECT password FROM admins WHERE admin_id = $user_id");
        if ($res && $row = $res->fetch_assoc()) {
            if (password_verify($current, $row['password'])) {
                if ($new === $confirm && strlen($new) >= 6) {
                    $h = password_hash($new, PASSWORD_DEFAULT);
                    $conn->query("UPDATE admins SET password = '$h' WHERE admin_id = $user_id");
                    $_SESSION['success'] = 'تم تغيير كلمة المرور بنجاح.';
                } else {
                    $_SESSION['error'] = 'كلمات المرور غير متطابقة أو أقل من 6 أحرف.';
                }
            } else {
                $_SESSION['error'] = 'كلمة المرور الحالية غير صحيحة.';
            }
        }
        header('Location: settings.php');
        exit();
    }
}

// جلب الإحصائيات
$stats = [
    'students' => $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'] ?? 0,
    'schedules' => $conn->query("SELECT COUNT(*) as c FROM schedules")->fetch_assoc()['c'] ?? 0,
    'departments' => $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'] ?? 0,
    'admins' => $conn->query("SELECT COUNT(*) as c FROM admins")->fetch_assoc()['c'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - جامعة تعز فرع التربة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

        .settings-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            border: var(--border-glass);
            overflow: hidden;
        }

        .settings-card-header {
            background: rgba(245, 158, 11, 0.1);
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: var(--border-glass);
        }

        .settings-card-header i {
            font-size: 1.3rem;
            color: var(--accent);
        }

        .settings-card-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .settings-card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Cairo', sans-serif;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(245, 158, 11, 0.05);
        }

        .btn-save {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--primary-dark);
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        }

        .stats-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .stats-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: var(--border-glass);
        }

        .stats-list li:last-child {
            border-bottom: none;
        }

        .stats-list .stat-label {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-secondary);
        }

        .stats-list .stat-label i {
            width: 35px;
            height: 35px;
            background: rgba(245, 158, 11, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .stats-list .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
        }

        .user-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 auto 20px;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header">
            <div class="page-title">
                <i class="fas fa-cog"></i>
                <h1>الإعدادات</h1>
            </div>
            <div class="user-menu">
                <a href="admin_panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                </a>
            </div>
        </header>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Settings Grid -->
        <div class="settings-grid">
            <!-- Profile Settings -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <i class="fas fa-user"></i>
                    <h3>الملف الشخصي</h3>
                </div>
                <div class="settings-card-body">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($user_info['username'] ?? 'م', 0, 1)); ?>
                    </div>
                    <form method="post">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> اسم المستخدم</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user_info['username'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> حفظ التغييرات
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password Settings -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <i class="fas fa-lock"></i>
                    <h3>تغيير كلمة المرور</h3>
                </div>
                <div class="settings-card-body">
                    <form method="post">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> كلمة المرور الحالية</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> كلمة المرور الجديدة</label>
                            <input type="password" name="new_password" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> تأكيد كلمة المرور</label>
                            <input type="password" name="confirm_password" minlength="6" required>
                        </div>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-key"></i> تغيير كلمة المرور
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Stats -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>إحصائيات النظام</h3>
                </div>
                <div class="settings-card-body">
                    <ul class="stats-list">
                        <li>
                            <span class="stat-label">
                                <i class="fas fa-user-graduate"></i>
                                إجمالي الطلاب
                            </span>
                            <span class="stat-value"><?php echo number_format($stats['students']); ?></span>
                        </li>
                        <li>
                            <span class="stat-label">
                                <i class="fas fa-calendar-alt"></i>
                                الجداول المرفوعة
                            </span>
                            <span class="stat-value"><?php echo number_format($stats['schedules']); ?></span>
                        </li>
                        <li>
                            <span class="stat-label">
                                <i class="fas fa-building"></i>
                                الأقسام الأكاديمية
                            </span>
                            <span class="stat-value"><?php echo number_format($stats['departments']); ?></span>
                        </li>
                        <li>
                            <span class="stat-label">
                                <i class="fas fa-users-cog"></i>
                                المشرفين
                            </span>
                            <span class="stat-value"><?php echo number_format($stats['admins']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <i class="fas fa-link"></i>
                    <h3>روابط سريعة</h3>
                </div>
                <div class="settings-card-body">
                    <div class="d-grid gap-3">
                        <a href="students.php" class="btn btn-secondary">
                            <i class="fas fa-users"></i> إدارة الطلاب
                        </a>
                        <a href="schedules.php" class="btn btn-secondary">
                            <i class="fas fa-calendar"></i> إدارة الجداول
                        </a>
                        <a href="departments.php" class="btn btn-secondary">
                            <i class="fas fa-building"></i> إدارة الأقسام
                        </a>
                        <a href="../home.html" class="btn btn-info">
                            <i class="fas fa-globe"></i> زيارة الموقع
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('active');
        });

        // Back to Top
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            backToTop?.classList.toggle('show', window.scrollY > 300);
        });
        backToTop?.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>
