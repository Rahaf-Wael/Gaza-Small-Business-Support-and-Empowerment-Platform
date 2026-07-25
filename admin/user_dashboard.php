<?php
require_once '../config/database.php';
session_start();

// التحقق من صلاحية المدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// جلب كل المستخدمين
$users = $pdo->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();

// جلب مشاريع كل مستخدم
$projects = $pdo->query("SELECT p.*, u.full_name as owner_name 
                        FROM projects p 
                        JOIN users u ON p.user_id = u.id 
                        ORDER BY p.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المستخدمين | GazaBiz</title>
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

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }

        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin: 25px 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: #3498db; }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .user-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #eef2f7;
            transition: 0.3s;
        }
        .user-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .user-card .user-name-card {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }
        .user-card .user-email {
            color: #95a5a6;
            font-size: 14px;
        }
        .user-card .user-role-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
        }
        .role-entrepreneur { background: #ebf5fb; color: #3498db; }
        .role-investor { background: #d4edda; color: #27ae60; }
        .role-admin { background: #fde8e8; color: #c0392b; }

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
        .project-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .project-card .card-title {
            font-size: 17px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        .project-card .card-category { font-size: 13px; color: #95a5a6; }
        .project-card .card-owner { font-size: 12px; color: #3498db; margin-top: 2px; }
        .project-card .status-badge {
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .project-card .card-meta {
            display: flex;
            gap: 16px;
            margin: 8px 0;
            font-size: 14px;
            color: #555;
            flex-wrap: wrap;
        }
        .project-card .card-meta span { display: flex; align-items: center; gap: 4px; }
        .project-card .card-meta i { color: #3498db; }

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

        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px;
            text-align: center;
            font-size: 14px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .users-grid { grid-template-columns: 1fr; }
            .projects-grid { grid-template-columns: 1fr; }
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
            <div class="logo"><a href="../index.php">🚀 GazaBiz</a></div>
            <nav class="nav">
                <a href="../index.php" class="nav-link">🏠 الرئيسية</a>
                <a href="dashboard.php" class="nav-link admin-link">🔧 لوحة التحكم</a>
                <a href="user_dashboard.php" class="nav-link active">📊 لوحة المستخدمين</a>
                <div class="user-info">
                    <span class="user-name">👋 <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
                    <span class="user-role">(مدير)</span>
                    <a href="../logout.php" class="btn-logout">🚪 خروج</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">

        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-right"></i> العودة للوحة تحكم المدير</a>

        <h1 class="page-title"><i class="fas fa-users-cog"></i> لوحة تحكم المستخدمين</h1>

        <!-- ===== Users ===== -->
        <h2 class="section-title"><i class="fas fa-users"></i> جميع المستخدمين</h2>

        <?php if (count($users) > 0): ?>
            <div class="users-grid">
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-name-card"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="user-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></div>
                        <span class="user-role-badge role-<?= $user['role'] ?>">
                            <?= $user['role'] === 'entrepreneur' ? '🚀 رائد أعمال' : 
                               ($user['role'] === 'investor' ? '💰 مستثمر' : '🔧 مدير') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>لا يوجد مستخدمين</h3>
            </div>
        <?php endif; ?>

        <!-- ===== All Projects ===== -->
        <h2 class="section-title"><i class="fas fa-project-diagram"></i> مشاريع المستخدمين</h2>

        <?php if (count($projects) > 0): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="card-title"><?= htmlspecialchars($project['title']) ?></div>
                        <div class="card-category">📂 <?= htmlspecialchars($project['category']) ?></div>
                        <div class="card-owner"><i class="fas fa-user"></i> <?= htmlspecialchars($project['owner_name']) ?></div>
                        <span class="status-badge status-<?= $project['status'] ?>">
                            <?= $project['status'] === 'pending' ? '⏳ قيد المراجعة' : 
                               ($project['status'] === 'approved' ? '✅ معتمد' : '❌ مرفوض') ?>
                        </span>
                        <div class="card-meta">
                            <span><i class="fas fa-dollar-sign"></i> $<?= number_format($project['target_budget']) ?></span>
                            <span><i class="fas fa-heart"></i> <?= $project['interest_count'] ?> مهتم</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>لا توجد مشاريع</h3>
            </div>
        <?php endif; ?>

    </div>

    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

</body>
</html>