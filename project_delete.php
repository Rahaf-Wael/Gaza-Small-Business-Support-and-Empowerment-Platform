<?php
require_once 'config/database.php';
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// التحقق من صلاحية المستخدم
if ($_SESSION['role'] === 'admin') {
    // المدير يقدر يحذف أي مشروع
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
} else {
    // رائد أعمال يحذف مشروعه فقط (إذا كان pending)
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
}
$project = $stmt->fetch();

if (!$project) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        try {
            // ✅ بدء المعاملة
            $pdo->beginTransaction();
            
            // 1️⃣ حذف الرسائل المرتبطة
            $stmt = $pdo->prepare("DELETE FROM messages WHERE project_id = ?");
            $stmt->execute([$project_id]);
            
            // 2️⃣ حذف سجل الاهتمامات المرتبط
            $stmt = $pdo->prepare("DELETE FROM interests WHERE project_id = ?");
            $stmt->execute([$project_id]);
            
            // 3️⃣ حذف المشروع
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            
            // ✅ تأكيد المعاملة
            $pdo->commit();
            
            header('Location: dashboard.php?deleted=1');
            exit;
        } catch (PDOException $e) {
            // ❌ في حال الخطأ، نلغي المعاملة
            $pdo->rollBack();
            $error = " حدث خطأ أثناء حذف المشروع";
        }
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف المشروع | GazaBiz</title>
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
        .admin-link { background: #e74c3c; color: #fff !important; }
        .admin-link:hover { background: #c0392b; }
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

        .page-wrapper {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .delete-container {
            max-width: 500px;
            width: 100%;
            background: #fff;
            padding: 40px 35px;
            border-radius: 20px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            text-align: center;
        }

        .delete-icon {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 15px;
        }

        .delete-container h2 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .delete-container .project-title {
            font-size: 18px;
            font-weight: 700;
            color: #3498db;
            margin: 10px 0;
        }

        .delete-container .warning {
            color: #e74c3c;
            font-weight: 600;
            margin: 12px 0;
            padding: 10px;
            background: #fde8e8;
            border-radius: 10px;
        }
        .delete-container .warning i { margin-left: 8px; }

        .delete-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-delete-confirm {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 12px 35px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-delete-confirm:hover { background: #c0392b; transform: scale(1.03); }

        .btn-cancel {
            background: #ecf0f1;
            color: #2c3e50;
            border: none;
            padding: 12px 35px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel:hover { background: #d5dbdb; }

        .alert-error {
            background: #fde8e8;
            color: #c0392b;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #f5c6cb;
        }

        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .delete-container { padding: 25px 18px; }
            .delete-actions { flex-direction: column; }
            .btn-delete-confirm, .btn-cancel { width: 100%; justify-content: center; }
            .header-container { flex-direction: column; align-items: stretch; }
            .nav { justify-content: center; gap: 6px; flex-wrap: wrap; }
            .nav-link { font-size: 12px; padding: 6px 12px; }
            .user-info { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="logo"><a href="index.php"> GazaBiz</a></div>
            <nav class="nav">
                <a href="index.php" class="nav-link"> الرئيسية</a>
                <?php if ($_SESSION['role'] === 'entrepreneur' || $_SESSION['role'] === 'admin'): ?>
                    <a href="project_submit.php" class="nav-link"> أضف مشروعك</a>
                <?php endif; ?>
                <a href="dashboard.php" class="nav-link active"> لوحتي</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="nav-link admin-link"> لوحة التحكم</a>
                <?php endif; ?>
                <div class="user-info">
                    <span class="user-name"> <?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                    <span class="user-role">(<?= $_SESSION['role'] === 'entrepreneur' ? 'رائد أعمال' : ($_SESSION['role'] === 'investor' ? 'مستثمر' : 'مدير') ?>)</span>
                    <a href="logout.php" class="btn-logout"> خروج</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="delete-container">

            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h2> هل أنت متأكد من حذف هذا المشروع؟</h2>
            <p class="project-title">"<?= htmlspecialchars($project['title']) ?>"</p>

            <div class="warning">
                <i class="fas fa-info-circle"></i>
                سيتم حذف جميع البيانات المرتبطة بهذا المشروع:
                <br>
                 الرسائل ·  الاهتمامات ·  الإحصائيات
                <br>
                <strong>هذا الإجراء لا يمكن التراجع عنه!</strong>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="confirm" value="yes">
                <div class="delete-actions">
                    <button type="submit" class="btn-delete-confirm">
                        <i class="fas fa-trash"></i> نعم، احذف المشروع
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>

        </div>
    </div>

    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

</body>
</html>