<?php
// تضمين الاتصال بقاعدة البيانات
include('db.php');

// التحقق من تسجيل الدخول
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// جلب البيانات
$departments_result = $conn->query("SELECT id, name FROM departments");
$departments = [];
if ($departments_result) {
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row;
    }
}
$total_departments = count($departments);

$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;
$total_schedules = $conn->query("SELECT COUNT(*) as count FROM schedules")->fetch_assoc()['count'] ?? 0;
$total_admins = $conn->query("SELECT COUNT(*) as count FROM admins")->fetch_assoc()['count'] ?? 0;
$recent_students = $conn->query("SELECT s.*, d.name as department_name FROM students s LEFT JOIN departments d ON s.department_id = d.id ORDER BY s.id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - جامعة تعز فرع التربة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Enhanced Premium Styles */
        .dashboard-hero {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(37, 99, 235, 0.1) 100%);
            border-radius: var(--radius-xl);
            padding: 40px;
            margin-bottom: 35px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            animation: pulse-glow 4s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-greeting {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .hero-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .hero-stats {
            display: flex;
            gap: 30px;
            margin-top: 25px;
        }

        .hero-stat {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hero-stat i {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .hero-stat span {
            color: var(--text-secondary);
        }

        /* Enhanced Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card .stat-trend {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 50px;
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
        }

        .stat-card .stat-trend.down {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        /* Quick Actions Enhanced */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 25px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 25px 15px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .action-btn:hover {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.4);
            color: var(--accent);
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(245, 158, 11, 0.2);
        }

        .action-btn:hover::before {
            opacity: 1;
        }

        .action-btn i {
            font-size: 2rem;
            transition: transform 0.3s ease;
        }

        .action-btn:hover i {
            transform: scale(1.1);
        }

        .action-btn span {
            font-size: 0.9rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        /* Enhanced Table */
        .student-row {
            transition: all 0.3s ease;
        }

        .student-row:hover {
            background: rgba(245, 158, 11, 0.05) !important;
        }

        .avatar-sm {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        /* Departments Grid Enhanced */
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            padding: 25px;
        }

        .dept-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .dept-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .dept-card:hover {
            background: rgba(245, 158, 11, 0.08);
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .dept-card:hover::before {
            transform: scaleX(1);
        }

        .dept-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.4));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--accent);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2);
            transition: all 0.3s ease;
        }

        .dept-card:hover .dept-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .dept-card h4 {
            color: var(--text-primary);
            font-size: 1.1rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .dept-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: rgba(245, 158, 11, 0.4);
            border-radius: 50%;
            animation: float-up 15s linear infinite;
        }

        @keyframes float-up {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* Glowing accents */
        .glow-line {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            animation: glow-move 3s ease-in-out infinite;
        }

        @keyframes glow-move {
            0%, 100% { width: 0; opacity: 0; }
            50% { width: 100%; opacity: 1; }
        }

        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            .hero-stats {
                flex-wrap: wrap;
            }
            .dashboard-hero {
                padding: 25px;
            }
            .hero-greeting {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>

    <!-- Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Hero -->
        <div class="dashboard-hero">
            <div class="hero-content">
                <h1 class="hero-greeting">
                    <i class="fas fa-sun" style="color: var(--accent); margin-left: 10px;"></i>
                    مرحباً، <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'المدير'); ?>!
                </h1>
                <p class="hero-subtitle">إليك نظرة عامة على نظام إدارة الجداول الدراسية</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <i class="fas fa-calendar-check"></i>
                        <span><?php echo date('Y/m/d'); ?></span>
                    </div>
                    <div class="hero-stat">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"><?php echo date('H:i'); ?></span>
                    </div>
                    <div class="hero-stat">
                        <i class="fas fa-database"></i>
                        <span>قاعدة البيانات: نشطة</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <span class="stat-trend"><i class="fas fa-arrow-up"></i> نشط</span>
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3 data-count="<?php echo $total_students; ?>">0</h3>
                    <p>إجمالي الطلاب</p>
                </div>
            </div>
            
            <div class="stat-card success">
                <span class="stat-trend"><i class="fas fa-check"></i> محدث</span>
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3 data-count="<?php echo $total_schedules; ?>">0</h3>
                    <p>الجداول المرفوعة</p>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3 data-count="<?php echo $total_departments; ?>">0</h3>
                    <p>الأقسام الأكاديمية</p>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="stat-info">
                    <h3 data-count="<?php echo $total_admins; ?>">0</h3>
                    <p>المشرفين</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Students -->
        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i>
                        <h3>إجراءات سريعة</h3>
                    </div>
                    <div class="quick-actions">
                        <a href="students.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>إضافة طالب</span>
                        </a>
                        <a href="schedules.php" class="action-btn">
                            <i class="fas fa-upload"></i>
                            <span>رفع جدول</span>
                        </a>
                        <a href="departments.php" class="action-btn">
                            <i class="fas fa-building"></i>
                            <span>الأقسام</span>
                        </a>
                        <a href="reports.php" class="action-btn">
                            <i class="fas fa-chart-bar"></i>
                            <span>التقارير</span>
                        </a>
                        <a href="settings.php" class="action-btn">
                            <i class="fas fa-cog"></i>
                            <span>الإعدادات</span>
                        </a>
                        <a href="../home.html" class="action-btn">
                            <i class="fas fa-globe"></i>
                            <span>الموقع</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Students -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users"></i>
                        <h3>أحدث الطلاب المسجلين</h3>
                        <a href="students.php" class="btn btn-sm btn-primary ms-auto">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>الرقم الجامعي</th>
                                    <th>القسم</th>
                                    <th>الهاتف</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_students && $recent_students->num_rows > 0): ?>
                                    <?php while ($student = $recent_students->fetch_assoc()): ?>
                                        <tr class="student-row">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-sm">
                                                        <?php echo strtoupper(substr($student['student_name'] ?? '', 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($student['student_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo htmlspecialchars($student['student_id']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['department_name'] ?? 'غير محدد'); ?></td>
                                            <td>
                                                <span style="direction: ltr; display: inline-block;">
                                                    <?php echo htmlspecialchars($student['phone'] ?? '-'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fas fa-users fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                            <h5>لا يوجد طلاب مسجلين بعد</h5>
                                            <a href="students.php" class="btn btn-primary btn-sm mt-2">
                                                <i class="fas fa-plus"></i> إضافة طالب
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments Overview -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-building"></i>
                <h3>الأقسام الأكاديمية</h3>
                <a href="departments.php" class="btn btn-sm btn-secondary ms-auto">إدارة الأقسام</a>
            </div>
            <div class="departments-grid">
                <?php if (!empty($departments)): ?>
                    <?php 
                    $icons = ['fa-laptop-code', 'fa-cogs', 'fa-briefcase', 'fa-flask', 'fa-book'];
                    $i = 0;
                    foreach ($departments as $dept): 
                    ?>
                        <div class="dept-card">
                            <div class="dept-icon">
                                <i class="fas <?php echo $icons[$i % count($icons)]; ?>"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($dept['name']); ?></h4>
                            <p>قسم أكاديمي</p>
                        </div>
                    <?php 
                    $i++;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <div class="text-center text-muted py-5 w-100">
                        <i class="fas fa-building fa-3x mb-3" style="opacity: 0.3;"></i>
                        <h5>لا توجد أقسام</h5>
                        <a href="departments.php" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus"></i> إضافة قسم
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }
        createParticles();

        // Counter Animation
        function animateCounters() {
            document.querySelectorAll('[data-count]').forEach(counter => {
                const target = parseInt(counter.dataset.count);
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target.toLocaleString();
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current).toLocaleString();
                    }
                }, 16);
            });
        }
        animateCounters();

        // Update time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('current-time').textContent = timeStr;
        }
        setInterval(updateTime, 1000);

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
    </script>
</body>
</html>