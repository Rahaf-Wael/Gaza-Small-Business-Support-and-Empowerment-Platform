<?php
require_once '../config/database.php';
session_start();

// ✅ تعريف المتغيرات مع تحقق أمني
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$name = $_SESSION['name'] ?? 'Admin';

// ✅ تحقق أمني قوي - منع التخمين والهجمات
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // تسجيل محاولة دخول غير مصرح بها
    error_log("محاولة دخول غير مصرح بها إلى لوحة المدير من IP: " . $_SERVER['REMOTE_ADDR']);
    header('Location: ../login.php?error=unauthorized');
    exit;
}

// ✅ حماية من هجمات CSRF - توليد توكن
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// معالجة الموافقة أو الرفض مع تحقق CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    // ✅ التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("محاولة CSRF من IP: " . $_SERVER['REMOTE_ADDR']);
        header('Location: dashboard.php?error=csrf');
        exit;
    }
    
    $project_id = (int)$_POST['project_id'];
    $action = $_POST['action'];
    
    // ✅ التحقق من صحة الإجراء
    if (!in_array($action, ['approved', 'rejected'])) {
        header('Location: dashboard.php?error=invalid_action');
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$action, $project_id]);
    
    // ✅ تسجيل النشاط
    error_log("Admin {$name} قام بـ {$action} للمشروع ID: {$project_id}");
    
    header('Location: dashboard.php?success=1');
    exit;
}

// ✅ معالجة حذف مشروع مع حذف البيانات المرتبطة
if (isset($_GET['delete']) && isset($_GET['id'])) {
    // ✅ التحقق من وجود توكن
    if (!isset($_GET['token']) || $_GET['token'] !== $_SESSION['csrf_token']) {
        error_log("محاولة حذف غير مصرح بها من IP: " . $_SERVER['REMOTE_ADDR']);
        header('Location: dashboard.php?error=csrf');
        exit;
    }
    
    $project_id = (int)$_GET['id'];
    
    // ✅ التحقق من وجود المشروع قبل الحذف
    $check = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
    $check->execute([$project_id]);
    if ($check->rowCount() === 0) {
        header('Location: dashboard.php?error=not_found');
        exit;
    }
    
    try {
        // ✅ بدء المعاملة (Transaction) لضمان الحذف الكامل
        $pdo->beginTransaction();
        
        // 1️⃣ حذف الرسائل المرتبطة بالمشروع أولاً
        $stmt = $pdo->prepare("DELETE FROM messages WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        // 2️⃣ حذف سجل الاهتمامات المرتبطة بالمشروع
        $stmt = $pdo->prepare("DELETE FROM interests WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        // 3️⃣ حذف المشروع
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        
        // ✅ تأكيد المعاملة
        $pdo->commit();
        
        error_log("Admin {$name} حذف المشروع ID: {$project_id} مع جميع البيانات المرتبطة");
        
        header('Location: dashboard.php?deleted=1');
        exit;
        
    } catch (PDOException $e) {
        // ❌ في حال حدوث خطأ، نلغي المعاملة
        $pdo->rollBack();
        error_log("خطأ في حذف المشروع ID: {$project_id} - " . $e->getMessage());
        header('Location: dashboard.php?error=delete_failed');
        exit;
    }
}

// جلب الإحصائيات
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'approved'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'rejected'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()
];

// جلب المشاريع
$stmt = $pdo->query("SELECT p.*, u.full_name as owner_name 
                    FROM projects p 
                    JOIN users u ON p.user_id = u.id 
                    ORDER BY p.created_at DESC");
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدير | GazaBiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== نفس الستايلات السابقة ===== */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        .page-title i { color: #3498db; margin-left: 10px; }

        .admin-actions-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            padding: 16px 20px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eef2f7;
            align-items: center;
        }
        .admin-actions-bar .label {
            font-weight: 600;
            color: #2c3e50;
        }
        .admin-actions-bar .btn-admin {
            background: #2c3e50;
            color: #fff;
            padding: 8px 22px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }
        .admin-actions-bar .btn-admin:hover { background: #1a2a3a; transform: translateY(-2px); }
        .admin-actions-bar .btn-admin.success { background: #27ae60; }
        .admin-actions-bar .btn-admin.success:hover { background: #219a52; }
        .admin-actions-bar .btn-admin.primary { background: #3498db; }
        .admin-actions-bar .btn-admin.primary:hover { background: #2980b9; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px 22px;
            border-radius: 16px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #fff;
            flex-shrink: 0;
        }
        .stat-card .icon.pending { background: #f39c12; }
        .stat-card .icon.approved { background: #27ae60; }
        .stat-card .icon.rejected { background: #e74c3c; }
        .stat-card .icon.users { background: #3498db; }
        .stat-card .icon.total { background: #2c3e50; }

        .stat-card .info .number {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-card .info .label {
            font-size: 13px;
            color: #95a5a6;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .project-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #eef2f7;
            transition: 0.3s;
        }
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }

        .project-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .project-card .card-title {
            font-size: 17px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        .project-card .card-category {
            font-size: 13px;
            color: #95a5a6;
        }
        .project-card .card-owner {
            font-size: 12px;
            color: #3498db;
            margin-top: 2px;
        }
        .project-card .card-owner i { color: #2c3e50; }

        .project-card .status-badge {
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .project-card .card-meta {
            display: flex;
            gap: 16px;
            margin: 10px 0 12px;
            font-size: 14px;
            color: #555;
            flex-wrap: wrap;
        }
        .project-card .card-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .project-card .card-meta i { color: #3498db; }

        .project-card .card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-sm {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: none;
            cursor: pointer;
        }
        .btn-view { background: #3498db; color: #fff; }
        .btn-view:hover { background: #2980b9; }
        .btn-edit { background: #f39c12; color: #fff; }
        .btn-edit:hover { background: #e67e22; }
        .btn-delete { background: #e74c3c; color: #fff; }
        .btn-delete:hover { background: #c0392b; }
        .btn-approve-sm { background: #27ae60; color: #fff; }
        .btn-approve-sm:hover { background: #219a52; }
        .btn-reject-sm { background: #e74c3c; color: #fff; }
        .btn-reject-sm:hover { background: #c0392b; }
        .btn-done-sm { background: #ecf0f1; color: #95a5a6; }

        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: #3498db; }
        .section-title .badge-count {
            background: #3498db;
            color: #fff;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eef2f7;
        }
        .empty-state i {
            font-size: 48px;
            color: #95a5a6;
            margin-bottom: 10px;
        }
        .empty-state h3 { color: #2c3e50; margin-bottom: 4px; }
        .empty-state p { color: #95a5a6; }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #c3e6cb;
        }
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
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .projects-grid { grid-template-columns: 1fr; }
            .admin-actions-bar { flex-direction: column; align-items: stretch; text-align: center; }
            .admin-actions-bar .btn-admin { justify-content: center; }
            .header-container { flex-direction: column; align-items: stretch; }
            .nav { justify-content: center; gap: 6px; flex-wrap: wrap; }
            .nav-link { font-size: 12px; padding: 6px 12px; }
            .user-info { flex-wrap: wrap; justify-content: center; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .stat-card { padding: 14px 16px; }
            .page-title { font-size: 22px; }
            .project-card .card-actions { flex-direction: column; }
            .project-card .card-actions .btn-sm { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="logo"><a href="../index.php">🚀 GazaBiz</a></div>
            <nav class="nav">
                <a href="../index.php" class="nav-link">🏠 الرئيسية</a>
                <a href="dashboard.php" class="nav-link active admin-link">🔧 لوحة التحكم</a>
                <a href="add_project.php" class="nav-link">➕ إضافة مشروع</a>
                <a href="user_dashboard.php" class="nav-link">📊 المستخدمين</a>
                <div class="user-info">
                    <span class="user-name">👋 <?= htmlspecialchars($name) ?></span>
                    <span class="user-role">(مدير)</span>
                    <a href="../logout.php" class="btn-logout">🚪 خروج</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">

        <h1 class="page-title"><i class="fas fa-shield-alt"></i> لوحة تحكم المدير</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> ✅ تم تحديث حالة المشروع بنجاح!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> ✅ تم حذف المشروع وجميع البيانات المرتبطة به بنجاح!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-triangle"></i> ⚠️ خطأ أمني: طلب غير مصرح به!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-triangle"></i> ⚠️ حدث خطأ أثناء حذف المشروع، حاول مرة أخرى!
            </div>
        <?php endif; ?>

        <!-- ===== Admin Actions Bar ===== -->
        <div class="admin-actions-bar">
            <span class="label"><i class="fas fa-crown"></i> صلاحيات المدير:</span>
            <a href="add_project.php" class="btn-admin success">
                <i class="fas fa-user-plus"></i> إضافة مشروع لمستخدم
            </a>
            <a href="user_dashboard.php" class="btn-admin primary">
                <i class="fas fa-users"></i> لوحة المستخدمين
            </a>
            <span style="color:#95a5a6; font-size:13px; margin-right:10px;">
                <i class="fas fa-info-circle"></i> يمكنك تعديل أو حذف أي مشروع
            </span>
        </div>

        <!-- ===== Stats ===== -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon total"><i class="fas fa-project-diagram"></i></div>
                <div class="info">
                    <div class="number"><?= $stats['total'] ?></div>
                    <div class="label">📋 كل المشاريع</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon pending"><i class="fas fa-clock"></i></div>
                <div class="info">
                    <div class="number"><?= $stats['pending'] ?></div>
                    <div class="label">⏳ قيد المراجعة</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon approved"><i class="fas fa-check-circle"></i></div>
                <div class="info">
                    <div class="number"><?= $stats['approved'] ?></div>
                    <div class="label">✅ المعتمدة</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon rejected"><i class="fas fa-times-circle"></i></div>
                <div class="info">
                    <div class="number"><?= $stats['rejected'] ?></div>
                    <div class="label">❌ المرفوضة</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon users"><i class="fas fa-users"></i></div>
                <div class="info">
                    <div class="number"><?= $stats['total_users'] ?></div>
                    <div class="label">👥 المستخدمين</div>
                </div>
            </div>
        </div>

        <!-- ===== All Projects ===== -->
        <h2 class="section-title">
            <i class="fas fa-list"></i> كل المشاريع
            <span class="badge-count"><?= $stats['total'] ?></span>
        </h2>

        <?php if (count($projects) > 0): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><?= htmlspecialchars($project['title']) ?></div>
                                <div class="card-category">📂 <?= htmlspecialchars($project['category']) ?></div>
                                <div class="card-owner"><i class="fas fa-user"></i> صاحب المشروع: <?= htmlspecialchars($project['owner_name']) ?></div>
                            </div>
                            <span class="status-badge status-<?= $project['status'] ?>">
                                <?= $project['status'] === 'pending' ? '⏳ قيد المراجعة' : 
                                   ($project['status'] === 'approved' ? '✅ معتمد' : '❌ مرفوض') ?>
                            </span>
                        </div>

                        <div class="card-meta">
                            <span><i class="fas fa-dollar-sign"></i> $<?= number_format($project['target_budget']) ?></span>
                            <span><i class="fas fa-heart"></i> <?= $project['interest_count'] ?> مهتم</span>
                            <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($project['created_at'])) ?></span>
                        </div>

                        <!-- ===== ACTIONS ===== -->
                        <div class="card-actions">
                            <a href="../project_details.php?id=<?= $project['id'] ?>" class="btn-sm btn-view">
                                <i class="fas fa-eye"></i> عرض
                            </a>

                            <a href="../project_edit.php?id=<?= $project['id'] ?>" class="btn-sm btn-edit">
                                <i class="fas fa-pen"></i> تعديل
                            </a>

                            <!-- ✅ رابط الحذف مع توكن CSRF للأمان -->
                            <a href="dashboard.php?delete=1&id=<?= $project['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" 
                               class="btn-sm btn-delete" 
                               onclick="return confirm('⚠️ هل أنت متأكد من حذف هذا المشروع؟\nسيتم حذف جميع البيانات المرتبطة به (الرسائل، الاهتمامات)!')">
                                <i class="fas fa-trash"></i> حذف
                            </a>

                            <?php if ($project['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" name="action" value="approved" class="btn-sm btn-approve-sm">
                                        ✅ موافقة
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" name="action" value="rejected" class="btn-sm btn-reject-sm">
                                        ❌ رفض
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="btn-sm btn-done-sm"><i class="fas fa-check"></i> تمت المعالجة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>لا توجد مشاريع</h3>
                <p>لم يقم أي رائد أعمال بإضافة مشاريع بعد</p>
            </div>
        <?php endif; ?>

    </div>

    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

</body>
</html>