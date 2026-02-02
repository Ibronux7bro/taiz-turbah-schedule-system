<?php
/**
 * Database Connection Configuration
 * جامعة تعز فرع التربة - نظام إدارة الجداول
 */

$host = "localhost";
$user = "root";
$password = "";
$db = "college_schedule_system";

// Create connection
$conn = new mysqli($host, $user, $password, $db);

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>
