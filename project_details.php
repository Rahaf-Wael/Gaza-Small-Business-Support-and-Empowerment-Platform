<?php
require_once 'config/database.php';
session_start();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;
$role = $is_logged_in ? $_SESSION['role'] : '';

// جلب بيانات المشروع
if ($is_logged_in && ($role === 'admin' || $role === 'entrepreneur')) {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name, u.id as owner_id 
                           FROM projects p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ?");
    $stmt->execute([$project_id]);
} else {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name, u.id as owner_id 
                           FROM projects p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ? AND p.status = 'approved'");
    $stmt->execute([$project_id]);
}
$project = $stmt->fetch();

if (!$project) {
    header('Location: index.php');
    exit;
}

$is_owner = $is_logged_in && ($user_id == $project['owner_id']);
$is_admin = $is_logged_in && ($role === 'admin');
$is_investor = $is_logged_in && ($role === 'investor');
$is_entrepreneur = $is_logged_in && ($role === 'entrepreneur');

if ($project['status'] === 'pending' && !$is_owner && !$is_admin) {
    header('Location: index.php');
    exit;
}

// ✅ التحقق إذا كان المشروع مكتمل
$is_completed = ($project['current_investment'] >= $project['target_budget']);

// جلب رصيد المستثمر
$investor_wallet = 0;
if ($is_logged_in && $is_investor) {
    $stmt = $pdo->prepare("SELECT wallet FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $investor_wallet = (float)$stmt->fetchColumn();
}

// معالجة إبداء الاهتمام والدعم
$interest_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // إبداء الاهتمام
    if ($_POST['action'] === 'interest' && $is_logged_in && $is_investor && !$is_owner && $project['status'] === 'approved') {
        $investor_id = $_SESSION['user_id'];
        try {
            $stmt = $pdo->prepare("INSERT INTO interests (project_id, investor_id) VALUES (?, ?)");
            $stmt->execute([$project_id, $investor_id]);
            $stmt = $pdo->prepare("UPDATE projects SET interest_count = interest_count + 1 WHERE id = ?");
            $stmt->execute([$project_id]);
            $interest_message = " تم تسجيل اهتمامك بالمشروع بنجاح!";
            $project['interest_count']++;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $interest_message = " أنت مهتم بالفعل بهذا المشروع!";
            } else {
                $interest_message = " حدث خطأ، حاول مرة أخرى";
            }
        }
    }
    
    // دعم مالي مع التحقق من الرصيد (فقط إذا المشروع غير مكتمل)
    if ($_POST['action'] === 'donate' && $is_logged_in && $is_investor && !$is_owner && $project['status'] === 'approved' && !$is_completed) {
        $amount = (float)$_POST['amount'];
        $investor_id = $_SESSION['user_id'];
        
        // حساب الحد الأقصى المسموح
        $remaining_project = $project['target_budget'] - $project['current_investment'];
        $max_allowed = min($investor_wallet, $remaining_project);
        
        if ($amount <= 0) {
            $interest_message = " الرجاء إدخال مبلغ صحيح";
        } 
        elseif ($amount > $max_allowed) {
            $interest_message = " المبلغ يتجاوز الحد المسموح (الحد الأقصى: $" . number_format($max_allowed) . ")";
        } 
        else {
            try {
                $pdo->beginTransaction();
                
                // خصم من رصيد المستثمر
                $stmt = $pdo->prepare("UPDATE users SET wallet = wallet - ? WHERE id = ?");
                $stmt->execute([$amount, $investor_id]);
                
                // إضافة للمشروع
                $stmt = $pdo->prepare("UPDATE projects SET current_investment = current_investment + ? WHERE id = ?");
                $stmt->execute([$amount, $project_id]);
                
                // تسجيل التبرع
                $stmt = $pdo->prepare("INSERT INTO donations (project_id, investor_id, amount, donation_date) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$project_id, $investor_id, $amount]);
                
                $pdo->commit();
                
                // تحديث المتغيرات
                $project['current_investment'] += $amount;
                $investor_wallet -= $amount;
                
                // ✅ التحقق مرة أخرى إذا أصبح مكتملاً
                $is_completed = ($project['current_investment'] >= $project['target_budget']);
                
                $interest_message = " تم دعم المشروع بمبلغ $" . number_format($amount) . " بنجاح! ";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $interest_message = " حدث خطأ أثناء الدعم، حاول مرة أخرى";
            }
        }
    }
}

// ✅ حساب الحد الأقصى للدعم
$max_allowed = 0;
if ($is_investor && $project['status'] === 'approved' && !$is_completed) {
    $remaining_project = $project['target_budget'] - $project['current_investment'];
    $max_allowed = min($investor_wallet, $remaining_project);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> | GazaBiz</title>
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

        .wallet-badge {
            background: rgba(255,255,255,0.15);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: #2ecc71;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        .wallet-badge i { color: #f1c40f; }

        .page-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .project-card {
            background: #fff;
            border-radius: 20px;
            padding: 32px 35px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
        }
        /* ✅ إذا كان المشروع مكتمل، حدود خضراء */
        .project-card.completed {
            border: 2px solid #27ae60;
            position: relative;
        }
        .project-card.completed::after {
            content: "✅";
            position: absolute;
            top: -12px;
            right: -12px;
            font-size: 28px;
            background: #fff;
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .project-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .project-category {
            display: inline-block;
            background: #ebf5fb;
            color: #3498db;
            padding: 4px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .pending-notice {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ffeeba;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            background: #f8f9fa;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 22px;
        }
        .meta-item { display: flex; flex-direction: column; }
        .meta-item .label { font-size: 12px; color: #95a5a6; }
        .meta-item .value { font-weight: 600; color: #2c3e50; font-size: 15px; }
        .meta-item .value.completed {
            color: #27ae60;
        }

        .desc-section { margin-bottom: 20px; }
        .desc-section h3 { font-size: 16px; color: #2c3e50; margin-bottom: 8px; }
        .desc-section h3 i { color: #3498db; margin-left: 6px; }
        .desc-section p {
            color: #555;
            line-height: 1.8;
            background: #fafafa;
            padding: 16px 20px;
            border-radius: 12px;
            border-right: 4px solid #3498db;
        }

        .progress-section {
            background: #f8f9fa;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
        }
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2c3e50);
            border-radius: 10px;
            transition: width 0.6s ease;
        }
        /* ✅ إذا كان مكتمل، اللون أخضر */
        .progress-fill.completed {
            background: linear-gradient(90deg, #27ae60, #2ecc71) !important;
            animation: glow 1.5s ease-in-out infinite alternate;
        }
        @keyframes glow {
            from { opacity: 0.8; }
            to { opacity: 1; }
        }
        .progress-details {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 13px;
            color: #95a5a6;
        }
        .progress-details .completed-text {
            color: #27ae60;
            font-weight: 700;
        }

        .actions-section {
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-danger { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6cb; }

        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-interest {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-interest:hover { background: #c0392b; transform: scale(1.02); }

        .btn-chat {
            background: #2ecc71;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-chat:hover { background: #27ae60; }

        .btn-donate {
            background: #f39c12;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-donate:hover { background: #e67e22; transform: scale(1.02); }

        .btn-donate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #95a5a6;
        }
        .btn-donate:disabled:hover {
            transform: none;
            background: #95a5a6;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #95a5a6;
            color: #fff;
            padding: 12px 32px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-back:hover { background: #7f8c8d; }

        /* ===== Completed Badge في الصورة ===== */
        .card-completed-badge {
            background: #27ae60;
            color: #fff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 25px rgba(46, 204, 113, 0.4);
            border: 2px solid rgba(255,255,255,0.3);
        }

        /* ===== Donation Modal ===== */
        .donation-modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .donation-modal.show { display: block; }
        .donation-modal-content {
            background: #fff;
            margin: 10% auto;
            width: 92%;
            max-width: 420px;
            border-radius: 20px;
            padding: 30px 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .donation-modal-content h3 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 6px;
        }
        .donation-modal-content .sub {
            color: #95a5a6;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .donation-modal-content .wallet-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 15px;
            color: #2c3e50;
        }
        .donation-modal-content .wallet-info span {
            font-weight: 700;
            color: #2ecc71;
        }
        .donation-modal-content .wallet-info.remaining {
            background: #fff3cd;
            border: 1px solid #ffeeba;
        }
        .donation-modal-content .wallet-info.remaining span {
            color: #856404;
        }
        .donation-modal-content .amount-input {
            width: 100%;
            padding: 14px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 20px;
            text-align: center;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .donation-modal-content .amount-input:focus {
            border-color: #3498db;
            outline: none;
        }
        .donation-modal-content .quick-amounts {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .donation-modal-content .quick-amounts button {
            padding: 6px 18px;
            border: 2px solid #ecf0f1;
            border-radius: 20px;
            background: #f8f9fa;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .donation-modal-content .quick-amounts button:hover {
            border-color: #3498db;
            background: #ebf5fb;
        }
        .donation-modal-content .info-text {
            font-size: 13px;
            color: #95a5a6;
            margin: 10px 0;
        }
        .donation-modal-content .info-text strong {
            color: #2c3e50;
        }
        .donation-modal-content .btn-confirm {
            width: 100%;
            padding: 14px;
            background: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .donation-modal-content .btn-confirm:hover { background: #27ae60; }
        .donation-modal-content .btn-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .donation-modal-content .btn-cancel-modal {
            width: 100%;
            padding: 12px;
            background: #ecf0f1;
            color: #2c3e50;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 8px;
            transition: 0.3s;
        }
        .donation-modal-content .btn-cancel-modal:hover { background: #d5dbdb; }
        .donation-modal-content .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #ffeeba;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* ===== Chat Modal ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: #fff;
            margin: 5% auto;
            width: 92%;
            max-width: 500px;
            border-radius: 20px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px 20px 0 0;
            background: #2c3e50;
            color: #fff;
        }
        .modal-header .close {
            font-size: 28px;
            cursor: pointer;
            color: #fff;
            background: none;
            border: none;
        }
        .modal-header .close:hover { color: #e74c3c; }
        .chat-messages {
            flex: 1;
            padding: 16px 20px;
            overflow-y: auto;
            max-height: 350px;
            min-height: 150px;
            background: #f8f9fa;
        }
        .message-sent {
            background: #3498db;
            color: #fff;
            padding: 8px 16px;
            border-radius: 18px 18px 4px 18px;
            margin: 6px 0;
            max-width: 80%;
            margin-left: auto;
            text-align: right;
        }
        .message-received {
            background: #ecf0f1;
            color: #2c3e50;
            padding: 8px 16px;
            border-radius: 18px 18px 18px 4px;
            margin: 6px 0;
            max-width: 80%;
            text-align: right;
        }
        .message-sent .time, .message-received .time {
            font-size: 10px;
            opacity: 0.7;
            margin-right: 8px;
        }
        .chat-loading { text-align: center; color: #95a5a6; padding: 20px; }
        .chat-input {
            padding: 12px 16px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 10px;
            background: #fff;
            border-radius: 0 0 20px 20px;
        }
        .chat-input input {
            flex: 1;
            padding: 10px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .chat-input input:focus { border-color: #3498db; }
        .chat-input button {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .chat-input button:hover { background: #2980b9; }

        .footer {
            background: #2c3e50;
            color: rgba(255,255,255,0.7);
            padding: 20px;
            text-align: center;
            font-size: 14px;
            margin-top: 30px;
        }

        @media (max-width: 600px) {
            .project-card { padding: 20px 16px; }
            .project-title { font-size: 22px; }
            .meta-grid { grid-template-columns: 1fr 1fr; }
            .btn-group { flex-direction: column; }
            .btn-interest, .btn-chat, .btn-donate, .btn-back { width: 100%; justify-content: center; }
            .header-container { flex-direction: column; align-items: stretch; }
            .nav { justify-content: center; gap: 6px; flex-wrap: wrap; }
            .nav-link { font-size: 12px; padding: 6px 12px; }
            .user-info { flex-wrap: wrap; justify-content: center; }
            .donation-modal-content { margin: 20% auto; padding: 20px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="logo"><a href="index.php"> GazaBiz</a></div>
            <nav class="nav">
                <a href="index.php" class="nav-link"> الرئيسية</a>
                <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] === 'entrepreneur' || $_SESSION['role'] === 'admin')): ?>
                    <a href="project_submit.php" class="nav-link"> أضف مشروعك</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="nav-link"> لوحتي</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="nav-link admin-link">🔧 لوحة التحكم</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <div class="notification-wrapper">
                            <div class="notification-bell" onclick="toggleNotifications()">
                                <i class="fas fa-bell"></i>
                                <?php
                                $notif_count = 0;
                                if (isset($_SESSION['user_id'])) {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $notif_count = $stmt->fetchColumn();
                                }
                                if ($notif_count > 0): ?>
                                    <span class="notification-badge"><?= $notif_count ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($is_investor): ?>
                            <span class="wallet-badge">
                                <i class="fas fa-wallet"></i> $<?= number_format($investor_wallet) ?>
                            </span>
                        <?php endif; ?>
                        <span class="user-name"> <?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                        <span class="user-role">(<?= $_SESSION['role'] === 'entrepreneur' ? 'رائد أعمال' : ($_SESSION['role'] === 'investor' ? 'مستثمر' : 'مدير') ?>)</span>
                        <a href="logout.php" class="btn-logout"> خروج</a>
                    </div>
                <?php else: ?>
                    <div style="display:flex; gap:10px;">
                        <a href="login.php" class="btn-login" style="color:#fff; text-decoration:none; padding:8px 22px; border:2px solid #fff; border-radius:25px;">🔐 تسجيل دخول</a>
                        <a href="register.php" class="btn-register" style="background:#27ae60; color:#fff; text-decoration:none; padding:8px 22px; border-radius:25px;">📝 إنشاء حساب</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <!-- ✅ إضافة كلاس completed إذا كان المشروع مكتمل -->
        <div class="project-card <?= $is_completed ? 'completed' : '' ?>">

            <?php if ($project['status'] === 'pending' && ($is_owner || $is_admin)): ?>
                <div class="pending-notice">
                    <i class="fas fa-clock"></i>
                    <span> هذا المشروع قيد المراجعة من قبل المدير.</span>
                </div>
            <?php endif; ?>

            <h1 class="project-title"><?= htmlspecialchars($project['title']) ?></h1>
            <span class="project-category"> <?= htmlspecialchars($project['category']) ?></span>

            <div class="meta-grid">
                <div class="meta-item">
                    <span class="label"> صاحب المشروع</span>
                    <span class="value"><?= htmlspecialchars($project['full_name']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="label"> المبلغ المستهدف</span>
                    <span class="value">$<?= number_format($project['target_budget']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="label"> عدد المهتمين</span>
                    <span class="value"><?= $project['interest_count'] ?></span>
                </div>
                <div class="meta-item">
                    <span class="label"> تاريخ النشر</span>
                    <span class="value"><?= date('d/m/Y', strtotime($project['created_at'])) ?></span>
                </div>
                <div class="meta-item">
                    <span class="label"> الحالة</span>
                    <span class="value <?= $is_completed ? 'completed' : '' ?>">
                        <?php if ($is_completed): ?>
                             مكتمل 
                        <?php else: ?>
                            <?= $project['status'] === 'pending' ? ' قيد المراجعة' : 
                               ($project['status'] === 'approved' ? ' معتمد' : ' مرفوض') ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="desc-section">
                <h3><i class="fas fa-align-left"></i> وصف المشروع</h3>
                <p><?= nl2br(htmlspecialchars($project['description'])) ?></p>
            </div>

            <!-- ===== شريط التقدم ===== -->
            <div class="progress-section">
                <div class="progress-header">
                    <span> التقدم المالي</span>
                    <span>
                        <?php if ($is_completed): ?>
                             <strong style="color:#27ae60;">مكتمل!</strong>
                        <?php else: ?>
                            <?= number_format(($project['current_investment'] / $project['target_budget']) * 100, 1) ?>%
                        <?php endif; ?>
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?= $is_completed ? 'completed' : '' ?>" 
                         style="width: <?= min(($project['current_investment'] / $project['target_budget']) * 100, 100) ?>%;">
                    </div>
                </div>
                <div class="progress-details">
                    <span>تم جمع: $<?= number_format($project['current_investment']) ?></span>
                    <span>
                        <?php if ($is_completed): ?>
                            <span class="completed-text"> الهدف تم تحقيقه!</span>
                        <?php else: ?>
                            المتبقي: $<?= number_format($project['target_budget'] - $project['current_investment']) ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="actions-section">

                <?php if ($interest_message): ?>
                    <div class="alert <?= strpos($interest_message, '') !== false ? 'alert-success' : (strpos($interest_message, '⚠️') !== false ? 'alert-warning' : 'alert-danger') ?>">
                        <i class="fas <?= strpos($interest_message, '') !== false ? 'fa-check-circle' : (strpos($interest_message, '⚠️') !== false ? 'fa-exclamation-triangle' : 'fa-times-circle') ?>"></i>
                        <?= $interest_message ?>
                    </div>
                <?php endif; ?>

                <?php if (!$is_logged_in): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> سجل دخولك كمستثمر لدعم هذا المشروع
                    </div>
                    <div class="btn-group">
                        <a href="login.php" class="btn-back" style="background:#2c3e50;"><i class="fas fa-sign-in-alt"></i> تسجيل دخول</a>
                    </div>

                <?php elseif ($is_owner): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 📌 هذا مشروعك الخاص
                        <?php if ($is_completed): ?>
                             <strong style="color:#27ae60;">وقد اكتمل!</strong>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <button class="btn-chat" onclick="openChat()">
                            <i class="fas fa-comment"></i>  الرسائل
                        </button>
                        <a href="dashboard.php" class="btn-back">
                            <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                        </a>
                    </div>

                <?php elseif ($is_investor && $project['status'] === 'approved'): ?>
                    <div class="btn-group">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="interest">
                            <button type="submit" class="btn-interest">
                                <i class="fas fa-heart"></i> إبداء الاهتمام
                            </button>
                        </form>
                        
                        <?php if (!$is_completed): ?>
                            <!-- ✅ المشروع غير مكتمل → زر الدعم ظاهر -->
                            <button class="btn-donate" onclick="openDonation()">
                                <i class="fas fa-hand-holding-usd"></i>  دعم المشروع
                            </button>
                        <?php else: ?>
                            <!-- ✅ المشروع مكتمل → زر معطل مع رسالة -->
                            <button class="btn-donate" disabled>
                                <i class="fas fa-check-circle"></i>  المشروع مكتمل
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn-chat" onclick="openChat()">
                            <i class="fas fa-comment"></i> تواصل مع صاحب المشروع
                        </button>
                    </div>

                <?php elseif ($is_admin): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt"></i> أنت مدير
                    </div>
                    <div class="btn-group">
                        <button class="btn-chat" onclick="openChat()">
                            <i class="fas fa-comment"></i>  الرسائل
                        </button>
                        <a href="admin/dashboard.php" class="btn-back" style="background:#e74c3c;">
                            <i class="fas fa-shield-alt"></i> لوحة التحكم
                        </a>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> هذا المشروع غير متاح للتفاعل حالياً
                    </div>
                    <div class="btn-group">
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-right"></i> العودة للرئيسية
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ===== DONATION MODAL ===== -->
    <div id="donationModal" class="donation-modal">
        <div class="donation-modal-content">
            <h3> دعم المشروع</h3>
            <p class="sub">أدخل المبلغ الذي ترغب في التبرع به</p>
            
            <div class="wallet-info">
                <i class="fas fa-wallet"></i> رصيدك الحالي: 
                <span>$<?= number_format($investor_wallet) ?></span>
            </div>
            
            <div class="wallet-info remaining">
                <i class="fas fa-info-circle"></i> المتبقي من الهدف: 
                <span>$<?= number_format($project['target_budget'] - $project['current_investment']) ?></span>
            </div>
            
            <form method="POST" id="donationForm">
                <input type="hidden" name="action" value="donate">
                
                <input type="number" name="amount" id="donationAmount" class="amount-input" 
                       placeholder="المبلغ $" min="1" 
                       max="<?= max($max_allowed, 0) ?>" 
                       required>
                
                <?php if ($max_allowed <= 0): ?>
                    <div class="alert-warning">
                         لا يمكنك الدعم حالياً (المشروع مكتمل أو رصيدك صفر)
                    </div>
                <?php endif; ?>
                
                <div class="quick-amounts">
                    <button type="button" onclick="setAmount(10)">$10</button>
                    <button type="button" onclick="setAmount(50)">$50</button>
                    <button type="button" onclick="setAmount(100)">$100</button>
                    <button type="button" onclick="setAmount(500)">$500</button>
                </div>
                
                <div class="info-text">
                     الحد الأقصى المسموح: <strong>$<?= number_format(max($max_allowed, 0)) ?></strong>
                </div>
                
                <button type="submit" class="btn-confirm" <?= $max_allowed <= 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-check-circle"></i> تأكيد الدعم
                </button>
                
                <button type="button" class="btn-cancel-modal" onclick="closeDonation()">
                    <i class="fas fa-times"></i> إلغاء
                </button>
            </form>
        </div>
    </div>

    <!-- ===== CHAT MODAL ===== -->
    <div id="chatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-comment"></i> محادثة</h3>
                <button class="close" onclick="closeChat()">&times;</button>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="chat-loading"> جاري تحميل الرسائل...</div>
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="اكتب رسالتك...">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i> إرسال</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>© 2026 GazaBiz - منصة دعم المشاريع الصغيرة في غزة</p>
    </footer>

    <script>
        // ===== DONATION FUNCTIONS =====
        function openDonation() {
            document.getElementById('donationModal').classList.add('show');
        }
        
        function closeDonation() {
            document.getElementById('donationModal').classList.remove('show');
        }
        
        function setAmount(amount) {
            document.getElementById('donationAmount').value = amount;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('donationModal');
            if (event.target == modal) {
                modal.classList.remove('show');
            }
            const chatModal = document.getElementById('chatModal');
            if (event.target == chatModal) {
                closeChat();
            }
        }

        // ===== CHAT FUNCTIONS =====
        let currentUserId = <?= $user_id ?>;
        let ownerId = <?= $project['owner_id'] ?>;
        let projectId = <?= $project_id ?>;
        let chatInterval = null;
        let receiverId = null;

        function openChat() {
            document.getElementById('chatModal').style.display = 'block';
            fetch(`chat/get_last_receiver.php?project_id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.receiver_id && data.receiver_id != currentUserId) {
                        receiverId = data.receiver_id;
                    } else {
                        receiverId = ownerId;
                    }
                    loadMessages();
                })
                .catch(() => {
                    receiverId = ownerId;
                    loadMessages();
                });
            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(loadMessages, 3000);
        }

        function closeChat() {
            document.getElementById('chatModal').style.display = 'none';
            if (chatInterval) {
                clearInterval(chatInterval);
                chatInterval = null;
            }
        }

        function loadMessages() {
            fetch(`chat/chat_load.php?receiver_id=${ownerId}&project_id=${projectId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('chatMessages').innerHTML = data;
                    const msgs = document.getElementById('chatMessages');
                    msgs.scrollTop = msgs.scrollHeight;
                })
                .catch(() => {
                    document.getElementById('chatMessages').innerHTML = 
                        '<div class="chat-loading"> خطأ في تحميل الرسائل</div>';
                });
        }

        function sendMessage() {
            let input = document.getElementById('messageInput');
            let message = input.value.trim();
            if (!message) {
                alert('الرجاء كتابة رسالة');
                return;
            }

            if (!receiverId || receiverId == currentUserId) {
                fetch(`chat/get_last_receiver.php?project_id=${projectId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.receiver_id && data.receiver_id != currentUserId) {
                            receiverId = data.receiver_id;
                            doSendMessage(message);
                        } else {
                            alert(' لا يوجد مستقبل للرسالة');
                        }
                    });
            } else {
                doSendMessage(message);
            }
        }

        function doSendMessage(message) {
            if (receiverId == currentUserId) {
                alert('لا يمكنك إرسال رسالة لنفسك');
                return;
            }

            fetch('chat/chat_send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${receiverId}&project_id=${projectId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('messageInput').value = '';
                    loadMessages();
                } else {
                    alert(' ' + (data.error || 'فشل إرسال الرسالة'));
                }
            });
        }

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // ===== NOTIFICATION FUNCTIONS =====
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
                    container.innerHTML = '<div class="notif-empty">❌ حدث خطأ</div>';
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
    </script>

</body>
</html>