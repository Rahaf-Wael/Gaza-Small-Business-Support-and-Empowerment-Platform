<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<div class="chat-loading"> يرجى تسجيل الدخول</div>';
    exit;
}

$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$receiver_id || !$project_id) {
    echo '<div class="chat-loading"> بيانات ناقصة</div>';
    exit;
}

try {
    // ✅ تحديث الرسائل كمقروءة (الرسائل الموجهة للمستخدم الحالي)
    $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id != ? AND project_id = ?");
    $update->execute([$user_id, $user_id, $project_id]);

    // ✅ جلب كل الرسائل المتعلقة بالمشروع والمستخدم الحالي
    // ✅ بغض النظر عن receiver_id، نجيب كل الرسائل التي فيها المستخدم الحالي إما مرسل أو مستقبل
    $stmt = $pdo->prepare("SELECT m.*, u.full_name 
                           FROM messages m 
                           JOIN users u ON m.sender_id = u.id 
                           WHERE (sender_id = ? OR receiver_id = ?)
                           AND project_id = ?
                           ORDER BY sent_at ASC");
    $stmt->execute([$user_id, $user_id, $project_id]);
    $messages = $stmt->fetchAll();

    if (count($messages) == 0) {
        echo '<div class="chat-loading"> لا توجد رسائل بعد، ابدأ المحادثة!</div>';
        exit;
    }

    foreach ($messages as $msg) {
        $class = $msg['sender_id'] == $user_id ? 'message-sent' : 'message-received';
        // ✅ عرض اسم المرسل للرسائل الواردة
        $sender_name = '';
        if ($msg['sender_id'] != $user_id) {
            $sender_name = '<strong>' . htmlspecialchars($msg['full_name']) . ':</strong> ';
        }
        echo "<div class='$class'>";
        echo $sender_name . htmlspecialchars($msg['message']);
        echo " <span class='time'>" . date('H:i', strtotime($msg['sent_at'])) . "</span>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo '<div class="chat-loading"> خطأ في قاعدة البيانات</div>';
}
?>