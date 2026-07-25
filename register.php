<?php
require_once 'config/database.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $wallet = 0;
    
    // ✅ إذا كان مستثمر، خذ المبلغ من الفورم
    if ($role === 'investor') {
        $wallet = floatval($_POST['wallet'] ?? 0);
        if ($wallet < 0) {
            $error = " الرجاء إدخال مبلغ صحيح";
        }
    }
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = " الرجاء تعبئة جميع الحقول";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = " البريد الإلكتروني غير صحيح";
    } elseif (strlen($password) < 6) {
        $error = " كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, wallet) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password_hash, $role, $wallet]);
            $success = " تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.";
            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = " هذا البريد الإلكتروني مسجل مسبقاً";
            } else {
                $error = " حدث خطأ، حاول مرة أخرى";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب | GazaBiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }

        .header {
            background: #2c3e50;
            color: #fff;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .logo a { color: #fff; text-decoration: none; font-size: 26px; font-weight: bold; }
        .nav { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .nav-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 25px;
            transition: 0.3s;
            font-size: 14px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.15); color: #fff; }
        .nav-link.active { background: rgba(255,255,255,0.2); color: #fff; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 6px 16px;
            border-radius: 25px;
        }
        .user-name { color: #fff; font-weight: bold; font-size: 14px; }
        .user-role { color: #95a5a6; font-size: 12px; }
        .btn-logout { color: #e74c3c; text-decoration: none; font-size: 13px; padding: 4px 12px; border-radius: 15px; transition: 0.3s; }
        .btn-logout:hover { background: #e74c3c; color: #fff; }
        .guest-actions { display: flex; gap: 10px; }
        .btn-login {
            color: #fff;
            text-decoration: none;
            padding: 8px 22px;
            border: 2px solid #fff;
            border-radius: 25px;
            transition: 0.3s;
            font-size: 14px;
        }
        .btn-login:hover { background: #fff; color: #2c3e50; }
        .btn-register {
            background: #27ae60;
            color: #fff;
            text-decoration: none;
            padding: 8px 22px;
            border-radius: 25px;
            transition: 0.3s;
            font-size: 14px;
        }
        .btn-register:hover { background: #219a52; }

        .page-wrapper {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            padding: 40px 35px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }

        .register-card h2 {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        .register-card .subtitle {
            text-align: center;
            color: #95a5a6;
            font-size: 15px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .form-group label i {
            color: #3498db;
            margin-left: 6px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 15px;
            transition: 0.3s;
            background: #fafafa;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #3498db;
            background: #fff;
            outline: none;
        }

        .form-group .wallet-input {
            display: none;
        }
        .form-group .wallet-input.show {
            display: block;
        }
        .form-group .wallet-input input {
            background: #f0fdf4;
            border-color: #2ecc71;
        }
        .form-group .wallet-input input:focus {
            border-color: #27ae60;
        }

        .form-group .wallet-hint {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 4px;
            display: block;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .alert-error { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 6px;
        }
        .btn-submit:hover {
            background: #1a2a3a;
            transform: translateY(-2px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
            color: #95a5a6;
            font-size: 14px;
        }
        .auth-footer a {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover { text-decoration: underline; }

        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 520px) {
            .register-card { padding: 25px 18px; }
            .register-card h2 { font-size: 22px; }
            .btn-submit { font-size: 16px; padding: 12px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="logo"><a href="index.php"> GazaBiz</a></div>
            <nav class="nav">
                <a href="index.php" class="nav-link"> الرئيسية</a>
                <a href="login.php" class="nav-link"> تسجيل دخول</a>
                <a href="register.php" class="nav-link active"> إنشاء حساب</a>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="register-card">
            <h2> إنشاء حساب</h2>
            <p class="subtitle">انضم إلى مجتمع رواد الأعمال والمستثمرين</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> الاسم الكامل</label>
                    <input type="text" name="full_name" placeholder="أحمد محمد" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
                    <input type="email" name="email" placeholder="example@email.com" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> كلمة المرور</label>
                    <input type="password" name="password" placeholder="********" required minlength="6">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> نوع الحساب</label>
                    <select name="role" id="roleSelect" required onchange="toggleWallet()">
                        <option value="entrepreneur"> رائد أعمال</option>
                        <option value="investor"> مستثمر</option>
                    </select>
                </div>

                <!-- ✅ حقل المبلغ المالي (يظهر فقط للمستثمر) -->
                <div class="form-group">
                    <div class="wallet-input" id="walletField">
                        <label><i class="fas fa-wallet"></i> المبلغ المالي (رصيدك الابتدائي)</label>
                        <input type="number" name="wallet" placeholder="10000" min="0" step="0.01" value="10000">
                        <small class="wallet-hint"> أدخل المبلغ الذي تبدأ به كمستثمر</small>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> تسجيل
                </button>
            </form>

            <div class="auth-footer">
                <p>لديك حساب؟ <a href="login.php">سجل دخول</a></p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

    <script>
        function toggleWallet() {
            const role = document.getElementById('roleSelect').value;
            const walletField = document.getElementById('walletField');
            if (role === 'investor') {
                walletField.classList.add('show');
            } else {
                walletField.classList.remove('show');
            }
        }

        // تشغيل عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            toggleWallet();
        });
    </script>

</body>
</html>