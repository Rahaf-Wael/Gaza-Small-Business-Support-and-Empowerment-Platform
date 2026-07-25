<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$message = trim($_POST['message'] ?? '');

if (!$receiver_id || !$project_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'بيانات ناقصة']);
    exit;
}

// منع إرسال رسالة لنفس المستخدم
if ($sender_id == $receiver_id) {
    echo json_encode(['success' => false, 'error' => 'لا يمكنك إرسال رسالة لنفسك']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, project_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $project_id, $message]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>