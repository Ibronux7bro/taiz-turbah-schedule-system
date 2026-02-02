<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include('db.php');

// Set page variables
$page_title = "إدارة الطلاب";
$active_page = "students";

// Handle actions
$action = $_GET['action'] ?? 'view';

if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn->query("DELETE FROM students WHERE id = $id");
    header("Location: students.php?success=deleted");
    exit();
}

// Get students with department info
$students = [];
$result = $conn->query("
    SELECT s.*, d.name as department_name 
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY s.student_name
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get departments for add/edit form
$departments = [];
$result = $conn->query("SELECT * FROM departments ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Include header
include 'header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-users me-2"></i>إدارة الطلاب</h1>
            <p class="text-muted mb-0">عرض وإدارة بيانات الطلاب</p>
        </div>
        <div>
            <a href="add_student.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة طالب جديد
            </a>
        </div>
    </div>
</div>

<!-- Success Message -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        switch($_GET['success']) {
            case 'added': echo 'تم إضافة الطالب بنجاح'; break;
            case 'updated': echo 'تم تحديث بيانات الطالب بنجاح'; break;
            case 'deleted': echo 'تم حذف الطالب بنجاح'; break;
            default: echo 'تمت العملية بنجاح';
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Students Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>قائمة الطلاب
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($students)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الطالب</th>
                            <th>الرقم الجامعي</th>
                            <th>القسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>رقم الهاتف</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
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
                                <span class="badge bg-secondary text-white">
                                    <?php echo htmlspecialchars($student['student_id'] ?? '-'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info text-white">
                                    <?php echo htmlspecialchars($student['department_name'] ?? 'غير محدد'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('Y/m/d', strtotime($student['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">لا يوجد طلاب</h5>
                <p class="text-muted mb-3">ابدأ بإضافة طلاب جدد</p>
                <a href="add_student.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة طالب جديد
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            إجمالي الطلاب</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($students); ?></div>
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
                            هذا الشهر</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                            $this_month = date('Y-m');
                            $month_students = array_filter($students, function($s) use ($this_month) {
                                return date('Y-m', strtotime($s['created_at'])) == $this_month;
                            });
                            echo count($month_students);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="icon bg-gradient-success">
                            <i class="fas fa-user-plus"></i>
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
                            الأقسام</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($departments); ?></div>
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
                            متوسط بالقسم</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo count($departments) > 0 ? round(count($students) / count($departments), 1) : 0; ?>
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

<!-- Students by Department -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-chart-pie me-2"></i>توزيع الطلاب حسب الأقسام
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <?php 
            // Group students by department
            $students_by_dept = [];
            foreach ($students as $student) {
                $dept_name = $student['department_name'] ?? 'غير محدد';
                if (!isset($students_by_dept[$dept_name])) {
                    $students_by_dept[$dept_name] = [];
                }
                $students_by_dept[$dept_name][] = $student;
            }
            
            foreach ($students_by_dept as $dept_name => $dept_students): 
                $percentage = count($dept_students) / count($students) * 100;
            ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary"><?php echo htmlspecialchars($dept_name); ?></h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="h5 mb-0"><?php echo count($dept_students); ?></span>
                            <span class="text-muted"><?php echo round($percentage, 1); ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo $percentage; ?>%;" 
                                 aria-valuenow="<?php echo $percentage; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php 
                            $recent = array_slice($dept_students, -3);
                            $names = array_map(function($s) { return $s['student_name']; }, $recent);
                            echo implode(', ', array_slice($names, 0, 2));
                            echo (count($names) > 2) ? ' وآخرون' : '';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-clock me-2"></i>أحدث الطلاب المسجلين
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <?php 
            $recent_students = array_slice($students, -6);
            foreach (array_reverse($recent_students) as $student): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 16px;">
                                <?php echo strtoupper(substr($student['student_name'] ?? '', 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($student['student_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($student['department_name'] ?? 'غير محدد'); ?></small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('Y/m/d', strtotime($student['created_at'])); ?>
                            </small>
                            <div class="btn-group btn-group-sm">
                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
                        <a href="add_student.php" class="btn btn-success btn-block">
                            <i class="fas fa-plus-circle me-2"></i>إضافة طالب جديد
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="import_students.php" class="btn btn-info btn-block">
                            <i class="fas fa-file-import me-2"></i>استيراد من Excel
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="export_students.php" class="btn btn-warning btn-block">
                            <i class="fas fa-file-export me-2"></i>تصدير البيانات
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-secondary btn-block" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>طباعة القائمة
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
