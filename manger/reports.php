<?php
// reports.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Set page title and active menu
$page_title = "التقارير والإحصائيات";
$active_page = "reports";

// Initialize stats array
$stats = [
    'students' => 0,
    'schedules' => 0,
    'departments' => 0,
    'recent_students' => [],
    'students_by_dept' => []
];

// Get basic stats
$result = $conn->query("SELECT COUNT(*) as count FROM students");
if ($result) {
    $stats['students'] = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM schedules");
if ($result) {
    $stats['schedules'] = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM departments");
if ($result) {
    $stats['departments'] = $result->fetch_assoc()['count'];
}

// Get recent students
$result = $conn->query("
    SELECT s.*, d.name as department_name 
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY s.created_at DESC 
    LIMIT 5
");
if ($result) {
    $stats['recent_students'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get students by department
$result = $conn->query("
    SELECT d.name as department_name, COUNT(s.id) as student_count
    FROM departments d
    LEFT JOIN students s ON d.id = s.department_id
    GROUP BY d.id
    ORDER BY student_count DESC
");
if ($result) {
    $stats['students_by_dept'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Include header
include 'header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-chart-line me-2"></i>التقارير والإحصائيات</h1>
            <p class="text-muted mb-0">عرض إحصائيات النظام وتقارير الطلاب</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>طباعة التقرير
            </button>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            إجمالي الطلاب</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['students']); ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="icon bg-gradient-primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            الجداول الدراسية</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['schedules']); ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="icon bg-gradient-success">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            عدد الأقسام</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['departments']); ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="icon bg-gradient-info">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            متوسط الطلاب بالقسم</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['departments'] > 0 ? round($stats['students'] / $stats['departments'], 1) : 0; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="icon bg-gradient-warning">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-user-graduate me-2"></i>أحدث الطلاب المسجلين
        </h6>
        <a href="students.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>عرض الكل
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>القسم</th>
                        <th>رقم الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>تاريخ التسجيل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['recent_students'])): ?>
                        <?php foreach ($stats['recent_students'] as $index => $student): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                        <?php echo strtoupper(substr($student['student_name'] ?? '', 0, 1)); ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($student['student_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-white">
                                    <?php echo htmlspecialchars($student['department_name'] ?? 'غير محدد'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('Y/m/d', strtotime($student['created_at'])); ?>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">لا توجد بيانات متاحة</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Students by Department -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-chart-pie me-2"></i>توزيع الطلاب حسب الأقسام
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>اسم القسم</th>
                        <th>عدد الطلاب</th>
                        <th>النسبة المئوية</th>
                        <th>مستوى الأداء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['students_by_dept'])): ?>
                        <?php 
                        $total_students = $stats['students'] > 0 ? $stats['students'] : 1;
                        foreach ($stats['students_by_dept'] as $dept): 
                            $percentage = ($dept['student_count'] / $total_students) * 100;
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                        <?php echo strtoupper(substr($dept['department_name'] ?? '', 0, 1)); ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($dept['department_name'] ?? 'غير محدد'); ?></span>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo number_format($dept['student_count']); ?></strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="width: 100px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%;" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="text-muted"><?php echo round($percentage, 1); ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($percentage >= 30): ?>
                                    <span class="badge bg-success">مرتفع</span>
                                <?php elseif ($percentage >= 15): ?>
                                    <span class="badge bg-warning">متوسط</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">منخفض</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">لا توجد بيانات متاحة</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>إجراءات سريعة
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="students.php?action=add" class="btn btn-success btn-block">
                            <i class="fas fa-plus-circle me-2"></i>إضافة طالب جديد
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="schedules.php?action=add" class="btn btn-info btn-block">
                            <i class="fas fa-plus-circle me-2"></i>إضافة جدول دراسي
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="export_reports.php" class="btn btn-warning btn-block">
                            <i class="fas fa-download me-2"></i>تصدير التقارير
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-secondary btn-block" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>طباعة الصفحة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>
