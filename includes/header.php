<?php
// ✅ الاتصال بقاعدة البيانات إذا لم يكن متصلاً
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

// ✅ التحقق من الإشعارات (للمستخدمين المسجلين فقط)
$notification_count = 0;
$notifications_list = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // جلب عدد الرسائل غير المقروءة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $notification_count = $stmt->fetchColumn();
        
        // جلب آخر 5 رسائل غير مقروءة
        $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name, p.title as project_title 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.id 
                               JOIN projects p ON m.project_id = p.id 
                               WHERE m.receiver_id = ? AND m.is_read = 0
                               ORDER BY m.sent_at DESC 
                               LIMIT 5");
        $stmt->execute([$user_id]);
        $notifications_list = $stmt->fetchAll();
    } catch (PDOException $e) {
        // في حال خطأ في قاعدة البيانات
        $notification_count = 0;
        $notifications_list = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== Notification Bell ===== */
        .notification-wrapper {
            position: relative;
            display: inline-block;
        }

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

        /* ===== Notification Dropdown ===== */
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 40px;
            left: 0;
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
        .notification-dropdown.show {
            display: block;
        }
        
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
        .notification-dropdown .notif-header .mark-read:hover {
            text-decoration: underline;
        }
        
        .notification-dropdown .notif-item {
            padding: 10px 16px;
            border-bottom: 1px solid #f5f5f5;
            transition: 0.3s;
            cursor: pointer;
        }
        .notification-dropdown .notif-item:hover {
            background: #f8f9fa;
        }
        .notification-dropdown .notif-item:last-child {
            border-bottom: none;
        }
        
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

        /* ===== Header Styles ===== */
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

        @media (max-width: 768px) {
            .header-container { flex-direction: column; align-items: stretch; }
            .nav { justify-content: center; gap: 6px; flex-wrap: wrap; }
            .nav-link { font-size: 12px; padding: 6px 12px; }
            .user-info { flex-wrap: wrap; justify-content: center; }
            .guest-actions { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php"> GazaBiz</a>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link"> الرئيسية</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'entrepreneur' || $_SESSION['role'] === 'admin'): ?>
                        <a href="project_submit.php" class="nav-link"> أضف مشروعك</a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="nav-link"> لوحتي</a>
                    
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="nav-link admin-link"> لوحة التحكم</a>
                    <?php endif; ?>
                    
                    <div class="user-info">
                        <!-- ✅ جرس الإشعارات -->
                        <div class="notification-wrapper">
                            <div class="notification-bell" onclick="toggleNotifications()">
                                <i class="fas fa-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge"><?= $notification_count ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- ✅ قائمة الإشعارات -->
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notif-header">
                                    <span> الإشعارات</span>
                                    <?php if ($notification_count > 0): ?>
                                        <span class="mark-read" onclick="markAllRead()">تحديد الكل كمقروء</span>
                                    <?php endif; ?>
                                </div>
                                <div id="notificationList">
                                    <?php if (count($notifications_list) > 0): ?>
                                        <?php foreach ($notifications_list as $notif): ?>
                                            <div class="notif-item" onclick="window.location.href='project_details.php?id=<?= $notif['project_id'] ?>'">
                                                <div class="notif-title"> رسالة جديدة</div>
                                                <div class="notif-sender"> من: <?= htmlspecialchars($notif['sender_name']) ?></div>
                                                <div class="notif-project"> مشروع: <?= htmlspecialchars($notif['project_title']) ?></div>
                                                <div class="notif-time"><?= date('d/m/Y H:i', strtotime($notif['sent_at'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="notif-empty">
                                            <i class="fas fa-bell-slash"></i>
                                            لا توجد إشعارات جديدة
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <span class="user-name"> <?= htmlspecialchars($_SESSION['name']) ?></span>
                        <span class="user-role">(<?= $_SESSION['role'] === 'entrepreneur' ? 'رائد أعمال' : ($_SESSION['role'] === 'investor' ? 'مستثمر' : 'مدير') ?>)</span>
                        <a href="logout.php" class="btn-logout"> خروج</a>
                    </div>
                    
                <?php else: ?>
                    <div class="guest-actions">
                        <a href="login.php" class="btn-login"> تسجيل دخول</a>
                        <a href="register.php" class="btn-register"> إنشاء حساب</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <script>
        // ✅ عرض/إخفاء قائمة الإشعارات
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
                if (dropdown.classList.contains('show')) {
                    loadNotificationList();
                }
            }
        }

        // ✅ تحميل قائمة الإشعارات (تحديث)
        function loadNotificationList() {
            const container = document.getElementById('notificationList');
            if (!container) return;
            
            fetch('notification_list.php')
                .then(response => response.text())
                .then(data => {
                    container.innerHTML = data;
                    updateNotificationBadge();
                })
                .catch(() => {});
        }

        // ✅ تحديث عدد الإشعارات في الجرس
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

        // ✅ تحديد الكل كمقروء
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

        // ✅ تحديث الإشعارات كل 10 ثواني
        setInterval(() => {
            updateNotificationBadge();
        }, 10000);

        // ✅ إغلاق القائمة عند الضغط خارجها
        document.addEventListener('click', function(event) {
            const wrapper = document.querySelector('.notification-wrapper');
            const dropdown = document.getElementById('notificationDropdown');
            if (wrapper && dropdown && !wrapper.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>