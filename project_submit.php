<?php
require_once 'config/database.php';
session_start();

// التحقق من أن المستخدم مسجل دخول ورائد أعمال
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'entrepreneur') {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $target_budget = floatval($_POST['target_budget']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($title) || empty($category) || empty($description) || $target_budget <= 0) {
        $error_message = ' الرجاء تعبئة جميع الحقول بشكل صحيح';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (user_id, title, category, description, target_budget, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $title, $category, $description, $target_budget]);
            $success_message = ' تم إضافة مشروعك بنجاح! سيتم مراجعته من قبل المدير قريباً.';
        } catch (PDOException $e) {
            $error_message = ' حدث خطأ أثناء إضافة المشروع';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مشروع جديد | GazaBiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        /* ===== Header ===== */
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

        .logo a {
            color: #fff;
            text-decoration: none;
            font-size: 26px;
            font-weight: bold;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 25px;
            transition: 0.3s;
            font-size: 14px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 6px 16px;
            border-radius: 25px;
        }

        .user-name {
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }

        .user-role {
            color: #95a5a6;
            font-size: 12px;
        }

        .btn-logout {
            color: #e74c3c;
            text-decoration: none;
            font-size: 13px;
            padding: 4px 12px;
            border-radius: 15px;
            transition: 0.3s;
        }

        .btn-logout:hover {
            background: #e74c3c;
            color: #fff;
        }

        /* ===== Page Content ===== */
        .page-wrapper {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .form-container {
            width: 100%;
            max-width: 620px;
            background: #ffffff;
            padding: 40px 35px;
            border-radius: 20px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
        }

        .form-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .form-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .form-header p {
            color: #95a5a6;
            font-size: 15px;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #fde8e8;
            color: #c0392b;
            border: 1px solid #f5c6cb;
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 15px;
            transition: 0.3s;
            background: #fafafa;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            background: #fff;
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .char-counter {
            display: block;
            text-align: left;
            font-size: 12px;
            color: #95a5a6;
            margin-top: 4px;
        }

        /* Tips */
        .form-tips {
            background: #f8f9fa;
            padding: 18px 20px;
            border-radius: 12px;
            margin: 20px 0;
            border-right: 4px solid #3498db;
        }

        .form-tips h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .form-tips ul {
            list-style: none;
            padding: 0;
        }

        .form-tips ul li {
            padding: 4px 0;
            color: #555;
            font-size: 14px;
        }

        .form-tips ul li:before {
            content: "✅ ";
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-submit {
            flex: 1;
            padding: 14px;
            background: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #1a2a3a;
            transform: translateY(-2px);
        }

        .btn-cancel {
            padding: 14px 30px;
            background: #ecf0f1;
            color: #2c3e50;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #d5dbdb;
        }

        .btn-dashboard {
            display: inline-block;
            padding: 14px 30px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-dashboard:hover {
            background: #2980b9;
        }

        /* ===== Footer ===== */
        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px 20px;
            text-align: center;
            font-size: 14px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 520px) {
            .form-container {
                padding: 25px 18px;
                border-radius: 16px;
            }

            .form-header h1 {
                font-size: 22px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-cancel,
            .btn-dashboard {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php"> GazaBiz</a>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link"> الرئيسية</a>
                <a href="project_submit.php" class="nav-link active"> أضف مشروعك</a>
                <a href="dashboard.php" class="nav-link"> لوحتي</a>
                <div class="user-info">
                    <span class="user-name"> <?= htmlspecialchars($_SESSION['name']) ?></span>
                    <span class="user-role">(رائد أعمال)</span>
                    <a href="logout.php" class="btn-logout"> خروج</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="page-wrapper">
        <div class="form-container">
            <div class="form-header">
                <h1>➕ إضافة مشروع جديد</h1>
                <p>شارك فكرتك مع العالم وابدأ رحلة نجاحك</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success_message ?>
                </div>
                <div style="text-align:center; margin-top:10px;">
                    <a href="dashboard.php" class="btn-dashboard">
                        <i class="fas fa-chart-simple"></i> الذهاب للوحة التحكم
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> عنوان المشروع</label>
                        <input type="text" name="title" placeholder="مثال: منصة تعليمية للأطفال" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-folder"></i> التصنيف</label>
                        <select name="category" required>
                            <option value="">اختر التصنيف</option>
                            <option value="تكنولوجيا"> تكنولوجيا</option>
                            <option value="تعليم"> تعليم</option>
                            <option value="صحة"> صحة</option>
                            <option value="زراعة"> زراعة</option>
                            <option value="صناعة"> صناعة</option>
                            <option value="خدمات"> خدمات</option>
                            <option value="تجارة"> تجارة</option>
                            <option value="فنون"> فنون</option>
                            <option value="سياحة"> سياحة</option>
                            <option value="طاقة"> طاقة</option>
                            <option value="بيئة"> بيئة</option>
                            <option value="أخرى"> أخرى</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> وصف المشروع</label>
                        <textarea name="description" id="description" placeholder="اشرح فكرتك بالتفصيل... ما هي المشكلة التي تحلها؟ كيف ستعمل؟" required></textarea>
                        <span class="char-counter" id="charCounter">0 / 1000 حرف</span>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> المبلغ المستهدف ($)</label>
                        <input type="number" name="target_budget" placeholder="5000" min="1" step="0.01" required>
                    </div>

                    <div class="form-tips">
                        <h4> نصائح لكتابة مشروع ناجح:</h4>
                        <ul>
                            <li>كن واضحاً ومحدداً في وصف فكرتك</li>
                            <li>اشرح القيمة المضافة التي تقدمها</li>
                            <li>حدد المبلغ المستهدف بشكل واقعي</li>
                            <li>اذكر الفئة المستهدفة من مشروعك</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-plus"></i> إضافة المشروع
                        </button>
                        <a href="dashboard.php" class="btn-cancel">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

    <script>
        // عداد الأحرف
        const textarea = document.getElementById('description');
        const counter = document.getElementById('charCounter');

        textarea.addEventListener('input', function() {
            const count = this.value.length;
            counter.textContent = `${count} / 1000 حرف`;
            if (count > 1000) {
                counter.style.color = 'red';
                this.value = this.value.substring(0, 1000);
                counter.textContent = '1000 / 1000 حرف';
            } else {
                counter.style.color = '#95a5a6';
            }
        });
    </script>

</body>
</html>