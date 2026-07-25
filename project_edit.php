<?php
require_once 'config/database.php';
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// جلب بيانات المشروع
if ($_SESSION['role'] === 'admin') {
    // المدير يقدر يعدل أي مشروع
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
} else {
    // رائد أعمال يعدل مشروعه فقط (إذا كان pending)
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
}
$project = $stmt->fetch();

if (!$project) {
    header('Location: dashboard.php');
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $target_budget = floatval($_POST['target_budget']);
    
    if (empty($title) || empty($category) || empty($description) || $target_budget <= 0) {
        $error_message = ' الرجاء تعبئة جميع الحقول بشكل صحيح';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE projects SET title = ?, category = ?, description = ?, target_budget = ? WHERE id = ?");
            $stmt->execute([$title, $category, $description, $target_budget, $project_id]);
            $success_message = ' تم تحديث المشروع بنجاح!';
            // تحديث البيانات
            $project['title'] = $title;
            $project['category'] = $category;
            $project['description'] = $description;
            $project['target_budget'] = $target_budget;
        } catch (PDOException $e) {
            $error_message = ' حدث خطأ أثناء التحديث';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المشروع | GazaBiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
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

        /* ===== Page ===== */
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
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
        }

        .form-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .form-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        .form-header h1 i { color: #f39c12; }

        .form-header p {
            color: #95a5a6;
            font-size: 15px;
        }

        .form-header .project-status {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

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
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

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
        .form-group label i { color: #3498db; margin-left: 6px; }

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

        .form-group .readonly-info {
            padding: 10px 14px;
            background: #f8f9fa;
            border-radius: 10px;
            color: #2c3e50;
            font-size: 14px;
            border: 1px solid #ecf0f1;
        }
        .form-group .readonly-info i { color: #95a5a6; margin-left: 6px; }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-submit {
            flex: 1;
            padding: 14px;
            background: #f39c12;
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
        .btn-submit:hover { background: #e67e22; transform: translateY(-2px); }

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
        .btn-cancel:hover { background: #d5dbdb; }

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
        .btn-dashboard:hover { background: #2980b9; }

        /* ===== Footer ===== */
        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 520px) {
            .form-container {
                padding: 25px 18px;
                border-radius: 16px;
            }
            .form-header h1 { font-size: 22px; }
            .form-actions { flex-direction: column; }
            .btn-submit, .btn-cancel, .btn-dashboard {
                width: 100%;
                justify-content: center;
            }
            .header-container {
                flex-direction: column;
                align-items: stretch;
            }
            .nav {
                justify-content: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            .nav-link {
                font-size: 12px;
                padding: 6px 12px;
            }
            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
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

    <!-- ===== MAIN ===== -->
    <div class="page-wrapper">
        <div class="form-container">

            <div class="form-header">
                <h1><i class="fas fa-pen"></i> تعديل المشروع</h1>
                <p>قم بتعديل بيانات مشروعك</p>
                <span class="project-status status-<?= $project['status'] ?>">
                    <?= $project['status'] === 'pending' ? ' قيد المراجعة' : 
                       ($project['status'] === 'approved' ? ' معتمد' : ' مرفوض') ?>
                </span>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div style="margin-top:6px;font-size:13px;color:#95a5a6;">
                        <i class="fas fa-shield-alt"></i> أنت مدير - يمكنك تعديل أي مشروع
                    </div>
                <?php endif; ?>
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

                <?php if ($project['status'] !== 'pending' && $_SESSION['role'] !== 'admin'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> هذا المشروع تمت معالجته، لا يمكن تعديله
                    </div>
                    <div style="text-align:center; margin-top:10px;">
                        <a href="dashboard.php" class="btn-dashboard">
                            <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                        </a>
                    </div>
                <?php else: ?>

                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> عنوان المشروع</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($project['title']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-folder"></i> التصنيف</label>
                            <select name="category" required>
                                <option value="">اختر التصنيف</option>
                                <option value="تكنولوجيا" <?= $project['category'] == 'تكنولوجيا' ? 'selected' : '' ?>> تكنولوجيا</option>
                                <option value="تعليم" <?= $project['category'] == 'تعليم' ? 'selected' : '' ?>> تعليم</option>
                                <option value="صحة" <?= $project['category'] == 'صحة' ? 'selected' : '' ?>> صحة</option>
                                <option value="زراعة" <?= $project['category'] == 'زراعة' ? 'selected' : '' ?>> زراعة</option>
                                <option value="صناعة" <?= $project['category'] == 'صناعة' ? 'selected' : '' ?>> صناعة</option>
                                <option value="خدمات" <?= $project['category'] == 'خدمات' ? 'selected' : '' ?>> خدمات</option>
                                <option value="تجارة" <?= $project['category'] == 'تجارة' ? 'selected' : '' ?>> تجارة</option>
                                <option value="فنون" <?= $project['category'] == 'فنون' ? 'selected' : '' ?>> فنون</option>
                                <option value="سياحة" <?= $project['category'] == 'سياحة' ? 'selected' : '' ?>> سياحة</option>
                                <option value="طاقة" <?= $project['category'] == 'طاقة' ? 'selected' : '' ?>> طاقة</option>
                                <option value="بيئة" <?= $project['category'] == 'بيئة' ? 'selected' : '' ?>> بيئة</option>
                                <option value="أخرى" <?= $project['category'] == 'أخرى' ? 'selected' : '' ?>> أخرى</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> وصف المشروع</label>
                            <textarea name="description" rows="6" required><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> المبلغ المستهدف ($)</label>
                            <input type="number" name="target_budget" value="<?= $project['target_budget'] ?>" min="1" step="0.01" required>
                        </div>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <div class="form-group">
                                <div class="readonly-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>ملاحظة:</strong> أنت مدير، يمكنك تعديل أي مشروع
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                            <a href="dashboard.php" class="btn-cancel">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>

                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

</body>
</html>