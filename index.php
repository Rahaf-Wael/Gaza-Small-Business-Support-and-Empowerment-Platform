<?php
require_once 'config/database.php';
session_start();

// جلب التصنيفات الفريدة للمشاريع المعتمدة
$categories = $pdo->query("SELECT DISTINCT category FROM projects WHERE status = 'approved' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// جلب المشاريع المعتمدة فقط
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
if ($selected_category) {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name, u.id as owner_id 
                           FROM projects p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.status = 'approved' AND p.category = ? 
                           ORDER BY p.created_at DESC");
    $stmt->execute([$selected_category]);
} else {
    $stmt = $pdo->query("SELECT p.*, u.full_name, u.id as owner_id 
                         FROM projects p 
                         JOIN users u ON p.user_id = u.id 
                         WHERE p.status = 'approved' 
                         ORDER BY p.created_at DESC");
}
$projects = $stmt->fetchAll();
$total_projects = count($projects);

// إحصائيات
$total_entrepreneurs = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'entrepreneur'")->fetchColumn();
$total_investors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'investor'")->fetchColumn();
$total_interests = $pdo->query("SELECT SUM(interest_count) FROM projects WHERE status = 'approved'")->fetchColumn();

// متغيرات المستخدم
$is_logged_in = isset($_SESSION['user_id']);
$role = $is_logged_in ? $_SESSION['role'] : '';
$name = $is_logged_in ? $_SESSION['name'] : '';

// جلب عدد الإشعارات للمستخدم المسجل
$notification_count = 0;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $notification_count = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GazaBiz | دعم المشاريع الصغيرة في غزة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== جميع الستايلات السابقة ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            color: #2c3e50;
            line-height: 1.6;
        }
        a { text-decoration: none; color: #3498db; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        .header {
            background: #2c3e50;
            color: #fff;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
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
        .logo a i { color: #e74c3c; }
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

        /* ===== Notification Styles ===== */
        .notification-wrapper { position: relative; display: inline-block; }
        .notification-bell {
            position: relative;
            cursor: pointer;
            color: #fff;
            font-size: 20px;
            padding: 4px 8px;
            transition: 0.3s;
        }
        .notification-bell:hover { color: #3498db; }
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #e74c3c;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            top: 40px;
            right: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            min-width: 320px;
            max-width: 380px;
            max-height: 380px;
            overflow-y: auto;
            z-index: 1000;
            border: 1px solid #eef2f7;
        }
        .notification-dropdown.show { display: block; }
        .notification-dropdown .notif-header {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: #fff;
            border-radius: 12px 12px 0 0;
            z-index: 5;
        }
        .notification-dropdown .notif-header .mark-read {
            font-size: 12px;
            color: #3498db;
            cursor: pointer;
            font-weight: 600;
        }
        .notification-dropdown .notif-header .mark-read:hover { text-decoration: underline; }
        .notification-dropdown .notif-item {
            padding: 10px 16px;
            border-bottom: 1px solid #f5f5f5;
            transition: 0.3s;
            cursor: pointer;
        }
        .notification-dropdown .notif-item:hover { background: #f8f9fa; }
        .notification-dropdown .notif-item:last-child { border-bottom: none; }
        .notification-dropdown .notif-item .notif-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        .notification-dropdown .notif-item .notif-sender {
            font-size: 13px;
            color: #3498db;
        }
        .notification-dropdown .notif-item .notif-project {
            font-size: 12px;
            color: #95a5a6;
        }
        .notification-dropdown .notif-item .notif-time {
            font-size: 11px;
            color: #95a5a6;
        }
        .notification-dropdown .notif-empty {
            padding: 30px 20px;
            text-align: center;
            color: #95a5a6;
        }
        .notification-dropdown .notif-empty i {
            font-size: 36px;
            display: block;
            margin-bottom: 8px;
            color: #ddd;
        }

        /* ===== Hero ===== */
        .hero-section {
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 50%, #3498db 100%);
            color: #fff;
            padding: 60px 20px 40px;
            text-align: center;
        }
        .hero-container { max-width: 1000px; margin: 0 auto; }
        .hero-content h1 { font-size: 42px; margin-bottom: 15px; font-weight: 700; }
        .hero-subtitle {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* ✅ الأزرار تظهر فقط للضيف (غير مسجل) */
        .hero-buttons {
            display: <?= $is_logged_in ? 'none' : 'flex' ?>;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 35px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .hero-btn i { font-size: 20px; }
        .hero-btn.primary { background: #e74c3c; color: #fff; }
        .hero-btn.primary:hover { background: #c0392b; transform: translateY(-3px); box-shadow: 0 8px 25px rgba(231,76,60,0.4); }
        .hero-btn.secondary { background: transparent; color: #fff; border: 2px solid #fff; }
        .hero-btn.secondary:hover { background: #fff; color: #2c3e50; transform: translateY(-3px); }

        /* ✅ رسالة ترحيب للمستخدم المسجل */
        .welcome-message {
            display: <?= $is_logged_in ? 'block' : 'none' ?>;
            font-size: 18px;
            margin-top: 10px;
            color: #fff;
            background: rgba(255,255,255,0.15);
            padding: 12px 24px;
            border-radius: 30px;
            display: inline-block;
        }
        .welcome-message i { color: #f1c40f; }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .stat-item { text-align: center; }
        .stat-number { display: block; font-size: 32px; font-weight: 700; }
        .stat-label { font-size: 14px; opacity: 0.8; }

        /* ===== Categories ===== */
        .categories-bar {
            background: #fff;
            padding: 16px 24px;
            border-radius: 50px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin: -25px auto 30px;
            max-width: 1100px;
            position: relative;
            z-index: 10;
        }
        .category-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            color: #95a5a6;
            font-size: 14px;
            transition: 0.3s;
            background: #f8f9fa;
        }
        .category-link:hover { background: #3498db; color: #fff; }
        .category-link.active { background: #3498db; color: #fff; }
        .category-link i { font-size: 14px; }

        /* ===== Projects ===== */
        .projects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
            gap: 15px;
        }
        .projects-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .projects-title h2 { font-size: 24px; color: #2c3e50; }
        .projects-title h2 i { color: #3498db; }
        .projects-count {
            background: #3498db;
            color: #fff;
            padding: 2px 14px;
            border-radius: 20px;
            font-size: 14px;
        }
        .guest-badge {
            background: #fff;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            color: #95a5a6;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .guest-badge i { color: #f39c12; }
        .guest-badge a { font-weight: 600; }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .project-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }
        .project-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(0,0,0,0.15); }

        .card-image {
            height: 160px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            position: relative;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .card-category-badge {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(4px);
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
        }
        .card-interest-badge {
            background: #e74c3c;
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .card-completed-badge {
            background: #27ae60;
            color: #fff;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 20px rgba(46, 204, 113, 0.3);
        }

        .card-body { padding: 18px 20px 14px; }
        .card-title { font-size: 18px; margin-bottom: 8px; color: #2c3e50; }
        .card-description {
            color: #95a5a6;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        .card-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: #95a5a6;
        }
        .card-meta .meta-item { display: flex; align-items: center; gap: 4px; }
        .card-meta .meta-item i { color: #3498db; }

        .card-footer {
            padding: 12px 20px 18px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .btn-details {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #3498db;
            color: #fff;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn-details:hover { background: #2980b9; transform: scale(1.03); }
        .btn-interest-small {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-interest-small:hover { background: #c0392b; transform: scale(1.05); }
        .guest-tip {
            color: #95a5a6;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .empty-icon { font-size: 60px; color: #95a5a6; margin-bottom: 15px; }
        .empty-state h3 { color: #2c3e50; margin-bottom: 8px; }
        .empty-state p { color: #95a5a6; }
        .empty-state a { font-weight: 600; }

        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 40px 20px 20px;
            margin-top: 40px;
        }
        .footer-container { max-width: 1200px; margin: 0 auto; }
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 25px;
        }
        .footer-section h4 { color: #fff; margin-bottom: 12px; }
        .footer-section ul { list-style: none; }
        .footer-section ul li { margin-bottom: 6px; }
        .footer-section ul li a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: 0.3s;
        }
        .footer-section ul li a:hover { color: #fff; }
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .hero-content h1 { font-size: 28px; }
            .hero-subtitle { font-size: 16px; }
            .hero-stats { gap: 25px; flex-wrap: wrap; }
            .stat-number { font-size: 24px; }
            .categories-bar {
                padding: 12px 16px;
                border-radius: 20px;
                margin-top: -15px;
                overflow-x: auto;
                flex-wrap: nowrap;
                gap: 8px;
            }
            .category-link { font-size: 12px; padding: 6px 14px; white-space: nowrap; }
            .projects-grid { grid-template-columns: 1fr; }
            .projects-header { flex-direction: column; align-items: flex-start; }
            .guest-badge { font-size: 12px; padding: 6px 14px; }
            .header-container { flex-direction: column; align-items: stretch; }
            .nav { justify-content: center; gap: 6px; flex-wrap: wrap; }
            .nav-link { font-size: 12px; padding: 6px 12px; }
            .user-info { flex-wrap: wrap; justify-content: center; }
            .guest-actions { flex-wrap: wrap; justify-content: center; }
            .hero-btn { padding: 12px 24px; font-size: 15px; width: 100%; justify-content: center; }
            .hero-buttons { flex-direction: column; }
            .card-meta { flex-direction: column; gap: 4px; }
            .card-footer { flex-wrap: wrap; }
            .btn-details, .btn-interest-small { width: 100%; justify-content: center; }
            .notification-dropdown {
                min-width: 280px;
                max-width: 320px;
                right: 0;
                left: auto;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 { font-size: 22px; }
            .stat-number { font-size: 20px; }
            .projects-title h2 { font-size: 18px; }
            .notification-dropdown {
                min-width: 260px;
                max-width: 290px;
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
                <a href="index.php" class="nav-link active"> الرئيسية</a>
                
                <?php if ($is_logged_in && $role === 'admin'): ?>
                    <!-- شريط المدير -->
                    <a href="admin/dashboard.php" class="nav-link admin-link"> لوحة التحكم</a>
                    <a href="admin/add_project.php" class="nav-link"> إضافة مشروع</a>
                    <a href="admin/user_dashboard.php" class="nav-link"> المستخدمين</a>
                    <div class="user-info">
                        <div class="notification-wrapper">
                            <div class="notification-bell" onclick="toggleNotifications()">
                                <i class="fas fa-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge"><?= $notification_count ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notif-header">
                                    <span> الإشعارات</span>
                                    <?php if ($notification_count > 0): ?>
                                        <span class="mark-read" onclick="markAllRead()">تحديد الكل كمقروء</span>
                                    <?php endif; ?>
                                </div>
                                <div id="notificationList">
                                    <div class="notif-empty">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        جاري التحميل...
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="user-name"> <?= htmlspecialchars($name) ?></span>
                        <span class="user-role">(مدير)</span>
                        <a href="logout.php" class="btn-logout"> خروج</a>
                    </div>

                <?php elseif ($is_logged_in && $role === 'entrepreneur'): ?>
                    <!-- شريط رائد الأعمال -->
                    <a href="project_submit.php" class="nav-link"> أضف مشروعك</a>
                    <a href="dashboard.php" class="nav-link"> لوحتي</a>
                    <div class="user-info">
                        <div class="notification-wrapper">
                            <div class="notification-bell" onclick="toggleNotifications()">
                                <i class="fas fa-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge"><?= $notification_count ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notif-header">
                                    <span> الإشعارات</span>
                                    <?php if ($notification_count > 0): ?>
                                        <span class="mark-read" onclick="markAllRead()">تحديد الكل كمقروء</span>
                                    <?php endif; ?>
                                </div>
                                <div id="notificationList">
                                    <div class="notif-empty">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        جاري التحميل...
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="user-name"> <?= htmlspecialchars($name) ?></span>
                        <span class="user-role">(رائد أعمال)</span>
                        <a href="logout.php" class="btn-logout"> خروج</a>
                    </div>

                <?php elseif ($is_logged_in && $role === 'investor'): ?>
                    <!-- شريط المستثمر -->
                    <a href="dashboard.php" class="nav-link"> لوحتي</a>
                    <div class="user-info">
                        <div class="notification-wrapper">
                            <div class="notification-bell" onclick="toggleNotifications()">
                                <i class="fas fa-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge"><?= $notification_count ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notif-header">
                                    <span> الإشعارات</span>
                                    <?php if ($notification_count > 0): ?>
                                        <span class="mark-read" onclick="markAllRead()">تحديد الكل كمقروء</span>
                                    <?php endif; ?>
                                </div>
                                <div id="notificationList">
                                    <div class="notif-empty">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        جاري التحميل...
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="user-name"> <?= htmlspecialchars($name) ?></span>
                        <span class="user-role">(مستثمر)</span>
                        <a href="logout.php" class="btn-logout"> خروج</a>
                    </div>

                <?php else: ?>
                    <!-- شريط الضيف -->
                    <div class="guest-actions">
                        <a href="login.php" class="btn-login"> تسجيل دخول</a>
                        <a href="register.php" class="btn-register"> إنشاء حساب</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- ===== HERO ===== -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <h1>🇵🇸 دعم المشاريع الصغيرة في غزة</h1>
                <p class="hero-subtitle">منصة تفاعلية تربط رواد الأعمال بالمستثمرين، لتحول الأفكار إلى مشاريع ناجحة</p>
                
                <!-- ✅ الأزرار تظهر فقط للضيف (غير مسجل) -->
                <?php if (!$is_logged_in): ?>
                    <div class="hero-buttons">
                        <a href="register.php?role=investor" class="hero-btn primary">
                            <i class="fas fa-hand-holding-usd"></i> انضم كمستثمر
                        </a>
                        <a href="register.php?role=entrepreneur" class="hero-btn secondary">
                            <i class="fas fa-rocket"></i> انضم كرائد أعمال
                        </a>
                    </div>
                <?php else: ?>
                    <!-- ✅ رسالة ترحيب للمستخدم المسجل -->
                    <div class="welcome-message">
                        <i class="fas fa-smile"></i> مرحباً بك في منصة دعم المشاريع الصغيرة في غزة
                    </div>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_projects ?></span>
                    <span class="stat-label">مشروع منشور</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $total_entrepreneurs + $total_investors ?></span>
                    <span class="stat-label">رواد أعمال ومستثمرين</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $total_interests ?: 0 ?></span>
                    <span class="stat-label">إجمالي الاهتمامات</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">

        <div class="categories-bar">
            <a href="index.php" class="category-link <?= !$selected_category ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> جميع المشاريع
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?= urlencode($cat) ?>" 
                   class="category-link <?= $selected_category === $cat ? 'active' : '' ?>">
                    <i class="fas fa-folder"></i> <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="projects-header">
            <div class="projects-title">
                <h2><i class="fas fa-project-diagram"></i> المشاريع المعروضة</h2>
                <span class="projects-count"><?= $total_projects ?> مشروع</span>
            </div>
            <?php if (!$is_logged_in): ?>
                <div class="guest-badge">
                    <i class="fas fa-user"></i> أنت تتصفح كضيف - 
                    <a href="login.php">سجل دخول</a> لتتمكن من إبداء الاهتمام
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_projects > 0): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $proj): 
                    $is_completed = ($proj['current_investment'] >= $proj['target_budget']);
                ?>
                    <div class="project-card <?= $is_completed ? 'completed' : '' ?>">
                        <div class="card-image">
                            <span class="card-category-badge"><?= htmlspecialchars($proj['category']) ?></span>
                            
                            <?php if ($is_completed): ?>
                                <span class="card-completed-badge">
                                    <i class="fas fa-check-circle"></i> مكتمل 
                                </span>
                            <?php else: ?>
                                <span class="card-interest-badge">
                                    <i class="fas fa-heart"></i> <?= $proj['interest_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($proj['title']) ?></h3>
                            <p class="card-description">
                                <?= mb_substr(htmlspecialchars($proj['description']), 0, 100) . (strlen($proj['description']) > 100 ? '...' : '') ?>
                            </p>
                            <div class="card-meta">
                                <span class="meta-item"><i class="fas fa-user"></i> <?= htmlspecialchars($proj['full_name']) ?></span>
                                <span class="meta-item"><i class="fas fa-dollar-sign"></i> $<?= number_format($proj['target_budget']) ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="project_details.php?id=<?= $proj['id'] ?>" class="btn-details">
                                <i class="fas fa-eye"></i> عرض التفاصيل
                            </a>
                            <?php if ($is_logged_in && $role === 'investor' && !$is_completed): ?>
                                <button class="btn-interest-small" onclick="quickInterest(<?= $proj['id'] ?>)">
                                    <i class="fas fa-heart"></i> اهتمام
                                </button>
                            <?php elseif (!$is_logged_in): ?>
                                <span class="guest-tip"><i class="fas fa-lock"></i> سجل دخول</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox empty-icon"></i>
                <h3>لا توجد مشاريع معتمدة حالياً</h3>
                <p>سجل دخول كـ <a href="register.php">رائد أعمال</a> لإضافة مشروعك</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4> GazaBiz</h4>
                    <p>منصة دعم المشاريع الصغيرة في غزة</p>
                </div>
                <div class="footer-section">
                    <h4>روابط سريعة</h4>
                    <ul>
                        <li><a href="index.php">الرئيسية</a></li>
                        <?php if (!$is_logged_in): ?>
                            <li><a href="login.php">تسجيل دخول</a></li>
                            <li><a href="register.php">إنشاء حساب</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
            </div>
        </div>
    </footer>

    <script>
        // ===== Notification Functions =====
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
                if (dropdown.classList.contains('show')) {
                    loadNotificationList();
                }
            }
        }

        function loadNotificationList() {
            const container = document.getElementById('notificationList');
            if (!container) return;
            
            fetch('notification_list.php')
                .then(response => response.text())
                .then(data => {
                    container.innerHTML = data;
                })
                .catch(() => {
                    container.innerHTML = '<div class="notif-empty"> حدث خطأ</div>';
                });
        }

        function markAllRead() {
            fetch('notification_mark_read.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotificationList();
                        updateNotificationBadge();
                        const dropdown = document.getElementById('notificationDropdown');
                        if (dropdown) dropdown.classList.remove('show');
                    }
                });
        }

        function updateNotificationBadge() {
            fetch('notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.notification-badge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = '';
                        } else {
                            const bell = document.querySelector('.notification-bell');
                            if (bell) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.textContent = data.count;
                                bell.appendChild(newBadge);
                            }
                        }
                    } else {
                        if (badge) badge.style.display = 'none';
                    }
                });
        }

        setInterval(updateNotificationBadge, 10000);

        document.addEventListener('click', function(event) {
            const wrapper = document.querySelector('.notification-wrapper');
            const dropdown = document.getElementById('notificationDropdown');
            if (wrapper && dropdown && !wrapper.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // ===== Quick Interest =====
        function quickInterest(projectId) {
            if (!confirm('هل تريد إبداء الاهتمام بهذا المشروع؟')) return;
            
            fetch('interest_quick.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `project_id=${projectId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(' تم تسجيل اهتمامك بنجاح!');
                    location.reload();
                } else {
                    alert('' + data.message);
                }
            });
        }
    </script>

</body>
</html>