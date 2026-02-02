<?php
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

// تحقق من استقبال البيانات عبر POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // تحقق من توفر البيانات المطلوبة
    if (isset($_POST['username'], $_POST['password'])) {
        $username = $conn->real_escape_string($_POST["username"]);
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);  // تشفير كلمة المرور

        // استعلام إدخال البيانات في قاعدة البيانات
        $sql = "INSERT INTO admins (username, password) VALUES ('$username', '$password')";

        if ($conn->query($sql) === TRUE) {
            echo "✅ تم إضافة المدير بنجاح.";
        } else {
            echo "❌ خطأ في قاعدة البيانات: " . $conn->error;
        }
    } else {
        echo "⚠️ يرجى تعبئة جميع الحقول المطلوبة.";
    }
}

$conn->close();
?>
