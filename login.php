<?php

$LoginHTML = 0;

?>

<!DOCTYPE html>
<html>
<head>
    <?php include "php/usuall/head.php"; ?>
    <title>美林湖广附八00 - 登录</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<?php include "php/usuall/header.php";?>
<main>
    <div class="tell">
        <h3 style="top: 10px; left: 10px">广告位招租</h3>
    </div>
    <div class="login" id="login">
        <h3>登录</h3>
        <p>用户名</p>
        <input type="text" id="username" placeholder="请输入用户名">
        <p>密码</p>
        <input type="password" id="password" placeholder="请输入密码"><br><br>
        <a href="reslogin.php">注册账号</a>
        <button onclick="login();">登录</button>
    </div>
</main>

</body>
<script src="js/usuall/header.js"></script>
<script src="js/login.js"></script>
</html>