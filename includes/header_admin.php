<?php
// هذا الهيدر خاص بلوحة تحكم المدير فقط
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="../index.php">🚀 GazaBiz</a>
            </div>
            <nav class="nav">
                <!-- ✅ روابط المدير فقط -->
                <a href="../index.php" class="nav-link">🏠 الرئيسية</a>
                <a href="dashboard.php" class="nav-link active admin-link">🔧 لوحة التحكم</a>
                <a href="add_project.php" class="nav-link">➕ إضافة مشروع</a>
                <a href="user_dashboard.php" class="nav-link">📊 المستخدمين</a>
                
                <div class="user-info">
                    <span class="user-name">👋 <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
                    <span class="user-role">(مدير)</span>
                    <a href="../logout.php" class="btn-logout">🚪 خروج</a>
                </div>
            </nav>
        </div>
    </header>