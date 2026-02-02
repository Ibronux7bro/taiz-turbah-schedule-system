<?php
// Include session and database configuration
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: manger/admin_panel.php");
    exit();
}

$error_message = '';
$success_message = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    include 'manger/db.php';
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'الرجاء إدخال اسم المستخدم وكلمة المرور';
    } else {
        // Query with prepared statement - using admins table
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $success_message = 'تم تسجيل الدخول بنجاح! جاري التحويل...';
                header("Refresh: 1; URL=manger/admin_panel.php");
            } else {
                $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } else {
            $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - جامعة تعز فرع التربة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === Premium Design Variables === */
        :root {
            --primary-dark: #0a1628;
            --primary: #1a365d;
            --primary-light: #2563eb;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --accent-glow: rgba(245, 158, 11, 0.4);
            --bg-dark: #0f172a;
            --bg-medium: #1e293b;
            --bg-glass: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --shadow-glow: 0 0 40px rgba(245, 158, 11, 0.3);
            --shadow-lg: 0 25px 50px rgba(0, 0, 0, 0.4);
            --border-glass: 1px solid rgba(255, 255, 255, 0.1);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-lg: 24px;
            --radius-xl: 32px;
            --error-color: #ef4444;
            --success-color: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* === Animated Background === */
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
            z-index: -2;
        }

        @keyframes bgPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* === Floating Circles === */
        .floating-circles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%);
            animation: floatCircle 20s infinite linear;
        }

        .circle:nth-child(1) { width: 300px; height: 300px; top: -150px; right: -100px; animation-delay: 0s; }
        .circle:nth-child(2) { width: 200px; height: 200px; bottom: -100px; left: -50px; animation-delay: 5s; }
        .circle:nth-child(3) { width: 150px; height: 150px; top: 50%; left: 10%; animation-delay: 10s; }
        .circle:nth-child(4) { width: 250px; height: 250px; bottom: 20%; right: 5%; animation-delay: 15s; }

        @keyframes floatCircle {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 0.5; }
        }

        /* === Login Container === */
        .login-container {
            background: var(--bg-glass);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: var(--radius-xl);
            padding: 50px 45px;
            width: 100%;
            max-width: 480px;
            border: var(--border-glass);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--primary-light), var(--accent));
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* === Header === */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--accent);
            box-shadow: var(--shadow-glow);
            margin-bottom: 25px;
            transition: var(--transition);
            animation: logoFloat 4s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-logo:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .login-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--accent-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* === Form === */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 18px 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            color: var(--text-primary);
            font-family: 'Cairo', sans-serif;
            font-size: 1.05rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(245, 158, 11, 0.05);
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.2);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .form-control:focus + .input-icon {
            color: var(--accent-light);
            transform: translateY(-50%) scale(1.2);
        }

        /* === Button === */
        .btn-login {
            width: 100%;
            padding: 18px 30px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--primary-dark);
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.4) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: all 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(245, 158, 11, 0.5);
        }

        .btn-login:hover::before {
            transform: translateX(100%);
        }

        .btn-login:active {
            transform: translateY(-2px);
        }

        /* === Messages === */
        .message {
            padding: 15px 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .success-message {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .message i {
            font-size: 1.3rem;
        }

        /* === Footer === */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: var(--border-glass);
        }

        .login-footer p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--accent-light);
            transform: translateX(-5px);
        }

        /* === Responsive === */
        @media (max-width: 520px) {
            .login-container {
                padding: 40px 25px;
            }

            .login-title {
                font-size: 1.7rem;
            }

            .login-logo {
                width: 100px;
                height: 100px;
            }

            .form-control {
                padding: 15px 45px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Circles -->
    <div class="floating-circles">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <img src="img/colleg.jpg" alt="شعار جامعة تعز فرع التربة" class="login-logo" 
                 onerror="this.src='https://via.placeholder.com/120x120/1a365d/f59e0b?text=TU'">
            <h1 class="login-title">
                <i class="fas fa-user-shield"></i> تسجيل الدخول
            </h1>
            <p class="login-subtitle">لوحة إدارة جامعة تعز فرع التربة</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="أدخل اسم المستخدم" required autocomplete="username">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                تسجيل الدخول
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> جامعة تعز فرع التربة</p>
            <a href="home.html" class="back-link">
                <i class="fas fa-arrow-right"></i>
                العودة للصفحة الرئيسية
            </a>
        </div>
    </div>

    <script>
        // Add focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>