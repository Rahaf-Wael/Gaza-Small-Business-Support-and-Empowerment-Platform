<?php
session_start();

// تدمير الجلسة
session_unset();
session_destroy();

// حذف كوكيز الجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// التوجيه للصفحة الرئيسية
header('Location: index.php');
exit;
?>