<?php
require_once 'config/database.php';
session_start();

// ✅ Debug: نشوف إذا في جلسة
if (!isset($_SESSION['user_id'])) {
    echo '<div class="notif-empty"><i class="fas fa-bell-slash"></i> يرجى تسجيل الدخول</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Debug: نشوف user_id
// echo "<!-- User ID: " . $user_id . " -->";

try {
    // ✅ جلب الرسائل غير المقروءة
    $stmt = $pdo->prepare("SELECT 
                            m.*, 
                            u.full_name as sender_name, 
                            p.title as project_title,
                            p.id as project_id
                           FROM messages m 
                           JOIN users u ON m.sender_id = u.id 
                           JOIN projects p ON m.project_id = p.id 
                           WHERE m.receiver_id = ? AND m.is_read = 0
                           ORDER BY m.sent_at DESC 
                           LIMIT 10");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // ✅ Debug: عدد النتائج
    // echo "<!-- Found: " . count($notifications) . " -->";

    if (count($notifications) == 0) {
        echo '<div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                <div>📭 لا توجد إشعارات جديدة</div>
              </div>';
        exit;
    }

    // ✅ عرض الإشعارات
    foreach ($notifications as $notif) {
        echo '<div class="notif-item" onclick="window.location.href=\'project_details.php?id=' . $notif['project_id'] . '\'">';
        echo '<div class="notif-title"> رسالة جديدة</div>';
        echo '<div class="notif-sender"> من: ' . htmlspecialchars($notif['sender_name']) . '</div>';
        echo '<div class="notif-project"> مشروع: ' . htmlspecialchars($notif['project_title']) . '</div>';
        echo '<div class="notif-time"> ' . date('d/m/Y H:i', strtotime($notif['sent_at'])) . '</div>';
        echo '</div>';
    }
} catch (PDOException $e) {
    echo '<div class="notif-empty"> خطأ: ' . $e->getMessage() . '</div>';
}
?>