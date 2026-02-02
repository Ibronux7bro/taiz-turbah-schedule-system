<?php
// إعدادات الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'college_schedule_system';
$conn = new mysqli($host, $username, $password, $dbname);

// متغيرات النتيجة
$status = 'error';
$message = 'حدث خطأ غير متوقع';
$student_name = '';
$student_id = '';

// تحقق من اتصال قاعدة البيانات
if ($conn->connect_error) {
    $message = 'فشل الاتصال بقاعدة البيانات';
} elseif (isset($_POST['name']) && isset($_POST['id-number'])) {
    $student_name = htmlspecialchars($_POST['name']);
    $student_id = htmlspecialchars($_POST['id-number']);

    // استعلام للحصول على بيانات الطالب
    $sql = "SELECT phone, department_id FROM students WHERE student_name = ? AND student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $student_name, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $phone_number = $row['phone'];
        $department_id = $row['department_id'];

        // البحث عن الجدول المرتبط بالقسم
        $schedule_sql = "SELECT file_name FROM schedules WHERE department_id = ?";
        $schedule_stmt = $conn->prepare($schedule_sql);
        $schedule_stmt->bind_param("i", $department_id);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();

        if ($schedule_result->num_rows > 0) {
            $schedule_row = $schedule_result->fetch_assoc();
            $file_name = $schedule_row['file_name'];

            // محاولة إرسال الجدول
            $send_result = send_schedule_via_telegram($phone_number, $file_name);
            
            if ($send_result) {
                $status = 'success';
                $message = 'تم إرسال الجدول بنجاح إلى تطبيق Telegram!';
            } else {
                $status = 'warning';
                $message = 'تم العثور على الجدول لكن فشل الإرسال. يرجى المحاولة لاحقاً.';
            }
        } else {
            $message = 'لا يوجد جدول مرتبط بقسمك حالياً.';
        }
    } else {
        $message = 'لم يتم العثور على بياناتك. تأكد من صحة الاسم والرقم الأكاديمي.';
    }
    $conn->close();
} else {
    $message = 'الرجاء ملء جميع الحقول المطلوبة.';
}

// دالة إرسال الملف عبر تيليجرام
function send_schedule_via_telegram($phone_number, $file_name) {
    $flask_url = "http://127.0.0.1:5000/send-schedule";

    $post_data = http_build_query([
        'phone' => $phone_number,
        'file' => $file_name
    ]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => $post_data,
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($flask_url, false, $context);

    return $result !== FALSE;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتيجة الطلب - جامعة تعز فرع التربة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0a1628;
            --primary: #1a365d;
            --primary-light: #2563eb;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --bg-dark: #0f172a;
            --bg-glass: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-glass: 1px solid rgba(255, 255, 255, 0.1);
            --transition: all 0.4s ease;
            --radius-xl: 32px;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(37, 99, 235, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(245, 158, 11, 0.15) 0%, transparent 50%);
            animation: bgPulse 12s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes bgPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .result-card {
            background: var(--bg-glass);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: var(--radius-xl);
            padding: 50px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            border: var(--border-glass);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .result-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            animation: iconPop 0.5s ease-out 0.3s both;
        }

        @keyframes iconPop {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .result-icon.success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.4));
            color: #86efac;
            border: 3px solid rgba(34, 197, 94, 0.5);
        }

        .result-icon.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.4));
            color: #fca5a5;
            border: 3px solid rgba(239, 68, 68, 0.5);
        }

        .result-icon.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.4));
            color: #fcd34d;
            border: 3px solid rgba(245, 158, 11, 0.5);
        }

        .result-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .result-title.success { color: #86efac; }
        .result-title.error { color: #fca5a5; }
        .result-title.warning { color: #fcd34d; }

        .result-message {
            font-size: 1.15rem;
            color: var(--text-secondary);
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .student-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border: var(--border-glass);
        }

        .student-info p {
            margin: 8px 0;
            color: var(--text-secondary);
        }

        .student-info strong {
            color: var(--accent);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 35px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            text-decoration: none;
            transition: var(--transition);
            margin: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--primary-dark);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(245, 158, 11, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent);
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        @media (max-width: 480px) {
            .result-card {
                padding: 35px 25px;
            }
            .result-icon {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            .result-title {
                font-size: 1.5rem;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="result-card">
        <?php if ($status === 'success'): ?>
            <div class="result-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="result-title success">تم الإرسال بنجاح!</h1>
        <?php elseif ($status === 'warning'): ?>
            <div class="result-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="result-title warning">تنبيه</h1>
        <?php else: ?>
            <div class="result-icon error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1 class="result-title error">حدث خطأ</h1>
        <?php endif; ?>

        <p class="result-message"><?php echo $message; ?></p>

        <?php if ($student_name || $student_id): ?>
            <div class="student-info">
                <?php if ($student_name): ?>
                    <p><i class="fas fa-user"></i> الاسم: <strong><?php echo $student_name; ?></strong></p>
                <?php endif; ?>
                <?php if ($student_id): ?>
                    <p><i class="fas fa-id-card"></i> الرقم الأكاديمي: <strong><?php echo $student_id; ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="home.html" class="btn btn-primary">
                <i class="fas fa-home"></i> الصفحة الرئيسية
            </a>
            <a href="table.html" class="btn btn-secondary">
                <i class="fas fa-redo"></i> محاولة أخرى
            </a>
        </div>
    </div>
</body>
</html>
