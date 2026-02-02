<?php
// إعداد الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$password = "";
$db = "college_schedule_system";

$conn = new mysqli($host, $user, $password, $db);

// التحقق من نجاح الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// التأكد من أن البيانات أُرسلت عبر POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (
        isset($_POST['student_name']) &&
        isset($_POST['student_id']) &&
        isset($_POST['phone']) &&
        isset($_POST['department_id'])
    ) {
        // استقبال البيانات وتنقيحها
        $student_name = $conn->real_escape_string($_POST['student_name']);
        $student_id = $conn->real_escape_string($_POST['student_id']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $department_id = intval($_POST['department_id']);

        // إدخال البيانات إلى قاعدة البيانات
        $sql = "INSERT INTO students (student_name, student_id, phone, department_id)
                VALUES ('$student_name', '$student_id', '$phone', $department_id)";

        if ($conn->query($sql) === TRUE) {
            echo "✅ تم تسجيل الطالب بنجاح.";
        } else {
            echo "❌ خطأ في قاعدة البيانات: " . $conn->error;
        }
    } else {
        echo "⚠️ يرجى تعبئة جميع الحقول المطلوبة.";
    }
}

$conn->close();
?>
