<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];

// جلب مشاريع المستخدم (إذا كان رائد أعمال)
$my_projects = [];
if ($role === 'entrepreneur') {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $my_projects = $stmt->fetchAll();
}

// جلب المشاريع التي أبدى المستثمر اهتمامه بها
$interested_projects = [];
if ($role === 'investor') {
    $stmt = $pdo->prepare("SELECT p.*, i.interest_date 
                          FROM interests i 
                          JOIN projects p ON i.project_id = p.id 
                          WHERE i.investor_id = ? AND p.status = 'approved'
                          ORDER BY i.interest_date DESC");
    $stmt->execute([$user_id]);
    $interested_projects = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | GazaBiz</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Welcome */
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
            padding: 20px 25px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eef2f7;
        }

        .welcome-section h1 {
            font-size: 26px;
            color: #2c3e50;
        }

        .welcome-section h1 i {
            color: #3498db;
        }

        .welcome-section .btn-add {
            background: #2c3e50;
            color: #fff;
            padding: 10px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .welcome-section .btn-add:hover {
            background: #1a2a3a;
            transform: translateY(-2px);
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            text-align: center;
            border: 1px solid #eef2f7;
        }

        .stat-box .number {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-box .label {
            font-size: 13px;
            color: #95a5a6;
            display: block;
            margin-top: 2px;
        }

        /* Section Title */
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3498db;
        }

        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
            margin-bottom: 4px;
        }

        .project-card .card-category {
            font-size: 13px;
            color: #95a5a6;
        }

        .project-card .status-badge {
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

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

        .project-card .card-meta i {
            color: #3498db;
        }

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

        .btn-view {
            background: #3498db;
            color: #fff;
        }

        .btn-view:hover {
            background: #2980b9;
        }

        .btn-edit {
            background: #f39c12;
            color: #fff;
        }

        .btn-edit:hover {
            background: #e67e22;
        }

        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .btn-done {
            background: #ecf0f1;
            color: #95a5a6;
        }

        /* Empty State */
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

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .empty-state p {
            color: #95a5a6;
        }

        .empty-state a {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
        }

        /* ===== Footer ===== */
        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px 20px;
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 600px) {
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: 1fr 1fr;
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
            <div class="logo">
                <a href="index.php">🚀 GazaBiz</a>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link">🏠 الرئيسية</a>
                <?php if ($role === 'entrepreneur'): ?>
                    <a href="project_submit.php" class="nav-link">➕ أضف مشروعك</a>
                <?php endif; ?>
                <a href="dashboard.php" class="nav-link active">📊 لوحتي</a>
                <?php if ($role === 'admin'): ?>
                    <a href="admin/dashboard.php" class="nav-link">🔧 لوحة التحكم</a>
                <?php endif; ?>
                <div class="user-info">
                    <span class="user-name">👋 <?= htmlspecialchars($name) ?></span>
                    <span class="user-role">(<?= $role === 'entrepreneur' ? 'رائد أعمال' : ($role === 'investor' ? 'مستثمر' : 'مدير') ?>)</span>
                    <a href="logout.php" class="btn-logout">🚪 خروج</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="page-wrapper">

        <!-- Welcome -->
        <div class="welcome-section">
            <h1><i class="fas fa-hand-wave"></i> مرحباً، <?= htmlspecialchars($name) ?></h1>
            <?php if ($role === 'entrepreneur'): ?>
                <a href="project_submit.php" class="btn-add">
                    <i class="fas fa-plus"></i> إضافة مشروع جديد
                </a>
            <?php endif; ?>
        </div>

        <?php if ($role === 'entrepreneur'): ?>

            <!-- Stats -->
            <?php
            $total = count($my_projects);
            $pending = count(array_filter($my_projects, fn($p) => $p['status'] === 'pending'));
            $approved = count(array_filter($my_projects, fn($p) => $p['status'] === 'approved'));
            $rejected = count(array_filter($my_projects, fn($p) => $p['status'] === 'rejected'));
            $total_interests = array_sum(array_column($my_projects, 'interest_count'));
            ?>
            <div class="stats-row">
                <div class="stat-box">
                    <span class="number"><?= $total ?></span>
                    <span class="label">📋 إجمالي المشاريع</span>
                </div>
                <div class="stat-box">
                    <span class="number"><?= $pending ?></span>
                    <span class="label">⏳ قيد المراجعة</span>
                </div>
                <div class="stat-box">
                    <span class="number"><?= $approved ?></span>
                    <span class="label">✅ معتمدة</span>
                </div>
                <div class="stat-box">
                    <span class="number"><?= $total_interests ?></span>
                    <span class="label">❤️ إجمالي الاهتمامات</span>
                </div>
            </div>

            <!-- Projects -->
            <h2 class="section-title"><i class="fas fa-project-diagram"></i> مشاريعي</h2>

            <?php if (count($my_projects) > 0): ?>
                <div class="projects-grid">
                    <?php foreach ($my_projects as $project): ?>
                        <div class="project-card">
                            <div class="card-header">
                                <div>
                                    <div class="card-title"><?= htmlspecialchars($project['title']) ?></div>
                                    <div class="card-category">📂 <?= htmlspecialchars($project['category']) ?></div>
                                </div>
                                <span class="status-badge status-<?= $project['status'] ?>">
                                    <?= $project['status'] === 'pending' ? '⏳ قيد المراجعة' : 
                                       ($project['status'] === 'approved' ? '✅ معتمد' : '❌ مرفوض') ?>
                                </span>
                            </div>
                            <div class="card-meta">
                                <span><i class="fas fa-dollar-sign"></i> $<?= number_format($project['target_budget']) ?></span>
                                <span><i class="fas fa-heart"></i> <?= $project['interest_count'] ?> مهتم</span>
                            </div>
                            <div class="card-actions">
                                <a href="project_details.php?id=<?= $project['id'] ?>" class="btn-sm btn-view">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
                                <?php if ($project['status'] === 'pending'): ?>
                                    <a href="project_edit.php?id=<?= $project['id'] ?>" class="btn-sm btn-edit">
                                        <i class="fas fa-pen"></i> تعديل
                                    </a>
                                    <a href="project_delete.php?id=<?= $project['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا المشروع؟')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                <?php else: ?>
                                    <span class="btn-sm btn-done"><i class="fas fa-check"></i> تمت المعالجة</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>لا توجد مشاريع</h3>
                    <p>لم تقم بإضافة أي مشروع بعد. <a href="project_submit.php">أضف مشروعك الآن!</a></p>
                </div>
            <?php endif; ?>

        <?php elseif ($role === 'investor'): ?>

            <!-- Investor Projects -->
            <h2 class="section-title"><i class="fas fa-heart"></i> المشاريع التي أبديت اهتمامك بها</h2>

            <?php if (count($interested_projects) > 0): ?>
                <div class="projects-grid">
                    <?php foreach ($interested_projects as $project): ?>
                        <div class="project-card">
                            <div class="card-header">
                                <div>
                                    <div class="card-title"><?= htmlspecialchars($project['title']) ?></div>
                                    <div class="card-category">📂 <?= htmlspecialchars($project['category']) ?></div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span><i class="fas fa-dollar-sign"></i> $<?= number_format($project['target_budget']) ?></span>
                                <span><i class="fas fa-heart"></i> <?= $project['interest_count'] ?> مهتم</span>
                                <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($project['created_at'])) ?></span>
                            </div>
                            <div class="card-actions">
                                <a href="project_details.php?id=<?= $project['id'] ?>" class="btn-sm btn-view">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>لا توجد مشاريع</h3>
                    <p>لم تبدِ اهتماماً بأي مشروع بعد. <a href="index.php">استكشف المشاريع</a></p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

</body>
</html>