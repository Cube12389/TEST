<?php
include 'header.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 激活：删除token或添加`is_active`列
        $stmt = $conn->prepare("UPDATE users SET token=NULL WHERE token=?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        echo "账户已激活！ <a href='login.php'>立即登录</a>";
    } else {
        echo "Token无效或已激活。";
    }
} else {
    echo "未找到token。";
}
?>
