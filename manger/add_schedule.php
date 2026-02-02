<?php
session_start();

// إعداد الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$password = "";
$db = "college_schedule_system";

$conn = new mysqli($host, $user, $password, $db);

// تأكد من نجاح الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// 🔒 التحقق من أن المدير مسجّل دخول
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// معالجة رفع الملف عند استخدام POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (
        isset($_POST['department_id'], $_POST['term'], $_POST['academic_year']) &&
        isset($_FILES['scheduleFile'])
    ) {

        $department_id = intval($_POST["department_id"]);
        $term = $conn->real_escape_string($_POST["term"]);
        $academic_year = $conn->real_escape_string($_POST["academic_year"]);
        $scheduleFile = $_FILES['scheduleFile'];

        // التأكد من أنه ملف PDF
        $ext = strtolower(pathinfo($scheduleFile['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            echo "⚠️ يُرجى رفع ملف بصيغة PDF فقط.";
            exit;
        }

        // مجلد الرفع
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // اسم فريد للملف
        $fileName = time() . '_' . uniqid() . '.pdf';
        $filePath = $uploadDir . $fileName;

        // رفع الملف
        if (move_uploaded_file($scheduleFile['tmp_name'], $filePath)) {

            // إدخال البيانات لقاعدة البيانات (حفظ اسم الملف فقط)
            $sql = "INSERT INTO schedules (department_id, term, academic_year, file_path) 
                    VALUES ($department_id, '$term', '$academic_year', '$fileName')";

            if ($conn->query($sql) === TRUE) {
                echo "✅ تم رفع الجدول الدراسي بنجاح.";
            } else {
                echo "❌ خطأ في قاعدة البيانات: " . $conn->error;
            }

        } else {
            echo "❌ فشل رفع الملف.";
        }

    } else {
        echo "⚠️ يرجى تعبئة جميع الحقول المطلوبة.";
    }
}

$conn->close();
?>
