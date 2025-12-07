<?php
// 统一PHP头部文件
// 包含数据库连接和会话管理

// 启动会话 (仅当会话尚未开始时)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 包含数据库配置
include 'config.php';

// 受保护页面检查函数
function check_login() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
        header("Location: login.php");
        exit;
    }
}

// 管理员页面检查函数
function check_admin() {
    if (!isset($_SESSION['admin_name']) && !isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit;
    }
}
?>