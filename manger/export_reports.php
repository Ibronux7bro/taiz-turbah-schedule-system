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
$page_title = "تصدير التقارير الملونة";
$active_page = "export_reports";

// Get departments with their assigned colors
$departments = [];
$check_column = $conn->query("SHOW COLUMNS FROM departments LIKE 'color'");
if ($check_column && $check_column->num_rows > 0) {
    $result = $conn->query("SELECT * FROM departments ORDER BY name");
} else {
    $result = $conn->query("SELECT *, '#3498db' as color FROM departments ORDER BY name");
}
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Handle color assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_colors'])) {
    // First, check if color column exists, add it if not
    $check_column = $conn->query("SHOW COLUMNS FROM departments LIKE 'color'");
    if ($check_column && $check_column->num_rows == 0) {
        $conn->query("ALTER TABLE departments ADD COLUMN color VARCHAR(7) DEFAULT '#3498db'");
    }
    
    foreach ($departments as $dept) {
        $color = $_POST['color_' . $dept['id']] ?? '#3498db';
        $conn->query("UPDATE departments SET color = '$color' WHERE id = " . $dept['id']);
    }
    header("Location: export_reports.php?success=colors_updated");
    exit();
}

// Handle export
if (isset($_POST['export_format'])) {
    $format = $_POST['export_format'];
    $selected_departments = $_POST['departments'] ?? [];
    
    if (empty($selected_departments)) {
        $error = "يرجى اختيار قسم واحد على الأقل";
    } else {
        // Get data for selected departments
        $dept_ids = implode(',', $selected_departments);
        $students = [];
        
        // Check if color column exists before using it in SELECT
        $check_column = $conn->query("SHOW COLUMNS FROM departments LIKE 'color'");
        if ($check_column && $check_column->num_rows > 0) {
            $result = $conn->query("
                SELECT s.*, d.name as department_name, d.color 
                FROM students s 
                JOIN departments d ON s.department_id = d.id 
                WHERE s.department_id IN ($dept_ids)
                ORDER BY d.name, s.student_name
            ");
        } else {
            $result = $conn->query("
                SELECT s.*, d.name as department_name, '#3498db' as color 
                FROM students s 
                JOIN departments d ON s.department_id = d.id 
                WHERE s.department_id IN ($dept_ids)
                ORDER BY d.name, s.student_name
            ");
        }
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        }
        
        if ($format == 'excel') {
            exportToExcel($students, $selected_departments);
        } elseif ($format == 'pdf') {
            exportToPDF($students, $selected_departments);
        } elseif ($format == 'csv') {
            exportToCSV($students, $selected_departments);
        }
    }
}

// Export functions
function exportToExcel($students, $selected_depts) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="students_report_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    echo "<table border='1'>";
    echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
    echo "<th>اسم الطالب</th>";
    echo "<th>الرقم الجامعي</th>";
    echo "<th>القسم</th>";
    echo "<th>البريد الإلكتروني</th>";
    echo "<th>رقم الهاتف</th>";
    echo "<th>تاريخ التسجيل</th>";
    echo "</tr>";
    
    foreach ($students as $student) {
        $color = $student['color'] ?? '#3498db';
        echo "<tr style='background-color:{$color}20;'>";
        echo "<td>" . htmlspecialchars($student['student_name']) . "</td>";
        echo "<td>" . htmlspecialchars($student['student_id'] ?? '-') . "</td>";
        echo "<td style='background-color:{$color}; color:white; font-weight:bold;'>" . 
             htmlspecialchars($student['department_name']) . "</td>";
        echo "<td>" . htmlspecialchars($student['email'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($student['phone'] ?? '-') . "</td>";
        echo "<td>" . date('Y/m/d', strtotime($student['created_at'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit();
}

function exportToPDF($students, $selected_depts) {
    // Simple HTML to PDF conversion
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="students_report_' . date('Y-m-d') . '.pdf"');
    
    $html = '<!DOCTYPE html><html dir="rtl"><head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<style>';
    $html .= 'body { font-family: Arial, sans-serif; margin: 20px; }';
    $html .= 'h1 { text-align: center; color: #2c3e50; }';
    $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    $html .= 'th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }';
    $html .= 'th { background-color: #f2f2f2; font-weight: bold; }';
    $html .= '</style></head><body>';
    
    $html .= '<h1>تقرير الطلاب الملون</h1>';
    $html .= '<p>التاريخ: ' . date('Y/m/d') . '</p>';
    
    $html .= '<table>';
    $html .= '<tr><th>اسم الطالب</th><th>الرقم الجامعي</th><th>القسم</th><th>البريد</th><th>الهاتف</th></tr>';
    
    foreach ($students as $student) {
        $color = $student['color'] ?? '#3498db';
        $html .= '<tr style="background-color:' . $color . '20;">';
        $html .= '<td>' . htmlspecialchars($student['student_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['student_id'] ?? '-') . '</td>';
        $html .= '<td style="background-color:' . $color . '; color:white; font-weight:bold;">' . 
                 htmlspecialchars($student['department_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['email'] ?? '-') . '</td>';
        $html .= '<td>' . htmlspecialchars($student['phone'] ?? '-') . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table></body></html>';
    
    echo $html;
    exit();
}

function exportToCSV($students, $selected_depts) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="students_report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['اسم الطالب', 'الرقم الجامعي', 'القسم', 'البريد الإلكتروني', 'رقم الهاتف', 'تاريخ التسجيل']);
    
    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_name'],
            $student['student_id'] ?? '',
            $student['department_name'],
            $student['email'] ?? '',
            $student['phone'] ?? '',
            date('Y/m/d', strtotime($student['created_at']))
        ]);
    }
    
    fclose($output);
    exit();
}

// Include header
include 'header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-palette me-2"></i>تصدير التقارير الملونة</h1>
            <p class="text-muted mb-0">تصميم وتصدير تقارير بألوان مخصصة للأقسام</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i>تحديث
            </button>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        switch($_GET['success']) {
            case 'colors_updated': echo 'تم تحديث ألوان الأقسام بنجاح'; break;
            default: echo 'تمت العملية بنجاح';
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Color Assignment Section -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-paint-brush me-2"></i>تخصيص ألوان الأقسام
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="export_reports.php">
            <input type="hidden" name="assign_colors" value="1">
            
            <div class="row">
                <?php foreach ($departments as $department): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="card-title"><?php echo htmlspecialchars($department['name']); ?></h6>
                            <div class="mb-3">
                                <input type="color" name="color_<?php echo $department['id']; ?>" 
                                       class="form-control form-control-color" 
                                       value="<?php echo htmlspecialchars($department['color'] ?? '#3498db'); ?>"
                                       style="width: 80px; height: 80px; border-radius: 50%; cursor: pointer;">
                            </div>
                            <small class="text-muted">اختر لون القسم</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ الألوان
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Export Section -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-download me-2"></i>تصدير التقرير
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="export_reports.php" id="exportForm">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6><i class="fas fa-building me-2"></i>اختر الأقسام للتصدير:</h6>
                    <div class="row mt-3">
                        <?php foreach ($departments as $department): ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="departments[]" 
                                       value="<?php echo $department['id']; ?>" id="dept_<?php echo $department['id']; ?>">
                                <label class="form-check-label" for="dept_<?php echo $department['id']; ?>">
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($department['color'] ?? '#3498db'); ?>; color: white;">
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6><i class="fas fa-file-export me-2"></i>اختر تنسيق التصدير:</h6>
                    <div class="btn-group" role="group">
                        <button type="submit" name="export_format" value="excel" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Excel
                        </button>
                        <button type="submit" name="export_format" value="pdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>PDF
                        </button>
                        <button type="submit" name="export_format" value="csv" class="btn btn-info">
                            <i class="fas fa-file-csv me-2"></i>CSV
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Section -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-eye me-2"></i>معاينة الألوان
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th>اللون المخصص</th>
                        <th>عدد الطلاب</th>
                        <th>معاينة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): 
                        $student_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE department_id = " . $department['id'])->fetch_assoc()['count'];
                        $color = $department['color'] ?? '#3498db';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($department['name']); ?></td>
                        <td>
                            <span class="badge" style="background-color: <?php echo $color; ?>; color: white;">
                                <?php echo $color; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo $student_count; ?></span>
                        </td>
                        <td>
                            <div style="background-color: <?php echo $color; ?>20; border: 2px solid <?php echo $color; ?>; padding: 10px; border-radius: 5px; text-align: center;">
                                <strong style="color: <?php echo $color; ?>;">نص تجريبي</strong>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
                        <button class="btn btn-success btn-block" onclick="selectAllDepartments()">
                            <i class="fas fa-check-double me-2"></i>تحديد كل الأقسام
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-warning btn-block" onclick="deselectAllDepartments()">
                            <i class="fas fa-times me-2"></i>إلغاء التحديد
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-info btn-block" onclick="randomizeColors()">
                            <i class="fas fa-random me-2"></i>ألوان عشوائية
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-secondary btn-block" onclick="resetColors()">
                            <i class="fas fa-undo me-2"></i>إعادة تعيين الألوان
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

<script>
// JavaScript functions
function selectAllDepartments() {
    const checkboxes = document.querySelectorAll('input[name="departments[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function deselectAllDepartments() {
    const checkboxes = document.querySelectorAll('input[name="departments[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

function randomizeColors() {
    const colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#95a5a6', '#d35400'];
    const colorInputs = document.querySelectorAll('input[type="color"]');
    
    colorInputs.forEach(input => {
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        input.value = randomColor;
    });
    
    // Auto-submit to save colors
    document.querySelector('form').submit();
}

function resetColors() {
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        input.value = '#3498db';
    });
    
    // Auto-submit to save colors
    document.querySelector('form').submit();
}

// Form validation
document.getElementById('exportForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('input[name="departments[]"]:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('يرجى اختيار قسم واحد على الأقل للتصدير');
    }
});
</script>
