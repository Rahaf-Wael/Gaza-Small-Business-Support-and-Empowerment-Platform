# Gaza Biz (منصة غزة بيز)

Gaza Biz هي منصة إلكترونية متكاملة تهدف إلى دعم وتطوير بيئة الأعمال وريادة الأعمال المحلية في قطاع غزة، من خلال ربط أصحاب المشاريع الصغيرة والناشئة بالمستثمرين والممولين والمهتمين.

---

## الميزات الأساسية (Features)

* إدارة الحسابات والأدوار (Authentication & Authorization):
  * تسجيل وتسجيل دخول آمن للمستخدمين (رواد أعمال / مستثمرين).
  * لوحة تحكم إدارية خاصة بالأدمن (Admin Dashboard) لإدارة المشاريع والمستخدمين.

* إدارة المشاريع الريادية (Project Management):
  * إضافة مشاريع جديدة وتحديد التفاصيل والميزانية المطلوبة.
  * تعديل وحذف المشاريع وتصفح قائمة المشاريع المتاحة.
  * إبداء الاهتمام السريع بالمنشورات والمشاريع (Quick Interest).

* نظام محادثات ودردشة فورية (Real-time Messaging System):
  * تواصل مباشر بين أصحاب المشاريع والمستثمرين المهتمين.
  * استرجاع وتحديث الرسائل تلقائياً (chat_load.php, chat_send.php).

* نظام إشعارات لحظي (Notifications System):
  * إشعارات فورية بالتفاعلات، التبرعات/الدعم، والرسائل الجديدة.
  * حساب الإشعارات غير المقروءة وتحديث حالتها تلقائياً.

* نظام التمويل والدعم (Funding & Donations):
  * تمكين المستثمرين من تقديم الدعم المالي للمشاريع مع تتبع الإحصائيات.

---

## الهيكلية البرمجية (Project Structure)

```text
gaza-biz/
├── admin/                     # لوحة تحكم المسؤول (Admin Panel)
│   ├── add_project.php        # إضافة مشروع من قبل الأدمن
│   ├── dashboard.php          # لوحة القيادة العامة للمشرفين
│   └── user_dashboard.php     # إدارة المستخدمين
├── assets/                    # الملفات الثابتة (CSS, JS, Images)
│   ├── css/style.css
│   └── js/main.js
├── chat/                      # نظام المحادثات المباشرة
│   ├── chat_load.php
│   ├── chat_send.php
│   └── get_last_receiver.php
├── config/                    # إعدادات النظام والاتصال
│   └── database.php           # الاتصال بقاعدة البيانات (MySQL)
├── includes/                  # الأجزاء المشتركة من الواجهة
│   ├── header.php
│   └── header_admin.php
├── dashboard.php              # لوحة تحكم المستخدم / رائد الأعمال
├── project_details.php        # صفحة تفاصيل المشروع
├── project_submit.php         # تقديم مشروع جديد
├── project_edit.php           # تعديل بيانات المشروع
├── project_delete.php         # حذف مشروع
├── interest_quick.php         # إبداء اهتمام سريع
├── notification_count.php     # عداد الإشعارات
├── notification_list.php      # قائمة الإشعارات
├── notification_mark_read.php # تعليم الإشعارات كمقروءة
├── login.php                  # تسجيل الدخول
├── register.php               # إنشاء حساب جديد
├── logout.php                 # تسجيل الخروج
├── index.php                  # الصفحة الرئيسية
└── gaza_biz.sql               # ملف قاعدة البيانات SQL

التقنيات المستخدمة (Tech Stack)
Back-end: PHP (Native / Pure PHP)

Database: MySQL / MariaDB

Front-end: HTML5, CSS3, JavaScript (AJAX / Fetch API)

Server Compatibility: Apache / Nginx (XAMPP / WAMP / LAMP)

طريقة التشغيل المباشر (Installation Setup)
متطلبات التشغيل:

بيئة سيرفر محلي مثل XAMPP أو WAMP.

PHP إصدار 7.4 أو أعلى.

MySQL / MariaDB.

خطوات التثبيت:

انسخ مجلد المشروع gaza-biz وضعه داخل مجلد السيرفر المحلي (مثلاً: C:/xampp/htdocs/gaza-biz).

قم بفتح phpMyAdmin على الرابط http://localhost/phpmyadmin.

أنشئ قاعدة بيانات جديدة باسم gaza_biz.

قم باستيراد (Import) ملف gaza_biz.sql المرفق داخل قاعدة البيانات.

تأكد من إعدادات الاتصال في ملف config/database.php:

PHP
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gaza_biz";
افتح المتصفح وانتقل إلى: http://localhost/gaza-biz/index.php.

الترخيص (License)
هذا المشروع تم تطويره كمنصة داعمة لريادة الأعمال. جميع الحقوق محفوظة لـ Gaza Biz Team.
