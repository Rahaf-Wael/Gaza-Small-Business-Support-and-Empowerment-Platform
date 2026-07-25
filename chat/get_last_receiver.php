<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['receiver_id' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if (!$project_id) {
    echo json_encode(['receiver_id' => 0]);
    exit;
}

// ✅ جلب آخر مستخدم تواصل معه المستخدم الحالي في هذا المشروع
// ✅ (باستثناء نفسه)
$stmt = $pdo->prepare("SELECT 
                        CASE 
                            WHEN sender_id = ? THEN receiver_id 
                            WHEN receiver_id = ? THEN sender_id 
                        END as other_user
                       FROM messages 
                       WHERE project_id = ? 
                       AND (sender_id = ? OR receiver_id = ?)
                       AND sender_id != receiver_id
                       ORDER BY sent_at DESC 
                       LIMIT 1");
$stmt->execute([$user_id, $user_id, $project_id, $user_id, $user_id]);
$result = $stmt->fetch();

if ($result && $result['other_user'] && $result['other_user'] != $user_id) {
    echo json_encode(['receiver_id' => (int)$result['other_user']]);
} else {
    // ✅ إذا ما في رسائل سابقة، نستخدم ownerId (لكن نحتاج نجيبها من المشروع)
    // ✅ لكن بما إننا في ملف منفصل، نجيب owner_id من المشروع
    $stmt2 = $pdo->prepare("SELECT user_id FROM projects WHERE id = ?");
    $stmt2->execute([$project_id]);
    $project = $stmt2->fetch();
    
    if ($project && $project['user_id'] != $user_id) {
        echo json_encode(['receiver_id' => (int)$project['user_id']]);
    } else {
        echo json_encode(['receiver_id' => 0]);
    }
}
?>