<?php
$host = "localhost";
$user = "root";   // XAMPP默认用户
$pass = "124536";
$db   = "food_db"; // 您正在使用的数据库名称

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}
?>
