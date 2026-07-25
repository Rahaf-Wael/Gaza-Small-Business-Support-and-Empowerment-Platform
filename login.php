<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        header('Location: index.php');
        exit;
    } else {
        $error = " البريد الإلكتروني أو كلمة المرور غير صحيحة";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | GazaBiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== Auth Page ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Tahoma', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* الكارد */
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
            text-align: center;
        }

        /* الشعار */
        .auth-logo {
            width: 72px;
            height: 72px;
            background: #2c3e50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 34px;
            color: #fff;
        }

        /* العنوان */
        .auth-title {
            font-size: 30px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .auth-sub {
            color: #95a5a6;
            font-size: 15px;
            margin-bottom: 26px;
        }

        /* الخطأ */
        .auth-error {
            background: #fde8e8;
            color: #c0392b;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* الحقول */
        .auth-field {
            margin-bottom: 16px;
            text-align: right;
        }

        .auth-field label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .auth-field input[type="text"],
        .auth-field input[type="email"],
        .auth-field input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 15px;
            transition: 0.3s;
            background: #fafafa;
        }

        .auth-field input:focus {
            border-color: #3498db;
            background: #fff;
            outline: none;
        }

        /* كلمة المرور */
        .auth-pass-wrap {
            position: relative;
        }

        .auth-pass-wrap input {
            padding-left: 46px !important;
        }

        .auth-eye {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
            font-size: 18px;
        }

        .auth-eye:hover {
            color: #2c3e50;
        }

        /* الزر */
        .auth-btn {
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
            margin-top: 4px;
        }

        .auth-btn:hover {
            background: #1a2a3a;
            transform: translateY(-2px);
        }

        /* فوتر */
        .auth-footer {
            margin-top: 20px;
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

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .auth-copy {
            text-align: center;
            margin-top: 18px;
            color: #95a5a6;
            font-size: 13px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 480px) {
            .auth-card {
                padding: 32px 22px;
                border-radius: 18px;
            }

            .auth-title {
                font-size: 26px;
            }

            .auth-btn {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">
                <span></span>
            </div>

            <h1 class="auth-title"> Welcome Back!</h1>
            <p class="auth-sub">سجل دخولك للمتابعة</p>

            <?php if (isset($error)): ?>
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-field">
                    <label> البريد الإلكتروني</label>
                    <input type="email" name="email" placeholder="example@email.com" required>
                </div>

                <div class="auth-field">
                    <label> كلمة المرور</label>
                    <div class="auth-pass-wrap">
                        <input type="password" name="password" id="password" placeholder="********" required>
                        <button type="button" class="auth-eye" onclick="togglePass()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </button>
            </form>

            <p class="auth-footer">
                ليس لديك حساب؟ <a href="register.php"> أنشئ حساب الأن !</a>
            </p>
        </div>

        <p class="auth-copy">© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>