<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'investor') {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول كمستثمر']);
    exit;
}

$project_id = (int)$_POST['project_id'];
$investor_id = $_SESSION['user_id'];

// التحقق من أن المستثمر ليس صاحب المشروع
$stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = ? AND status = 'approved'");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'المشروع غير موجود']);
    exit;
}

if ($project['user_id'] == $investor_id) {
    echo json_encode(['success' => false, 'message' => 'لا يمكنك الاهتمام بمشروعك الخاص']);
    exit;
}

try {
    // إضافة سجل اهتمام
    $stmt = $pdo->prepare("INSERT INTO interests (project_id, investor_id) VALUES (?, ?)");
    $stmt->execute([$project_id, $investor_id]);
    
    // تحديث عداد الاهتمامات
    $stmt = $pdo->prepare("UPDATE projects SET interest_count = interest_count + 1 WHERE id = ?");
    $stmt->execute([$project_id]);
    
    echo json_encode(['success' => true, 'message' => 'تم تسجيل اهتمامك بنجاح']);
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        echo json_encode(['success' => false, 'message' => 'أنت مهتم بالفعل بهذا المشروع']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ، حاول مرة أخرى']);
    }
}
?>