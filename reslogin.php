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
    <div class="tell" id="tell">
        <h3 style="top: 10px; left: 10px">广告位招租</h3>
    </div>
    <div style="height: auto;" class="login" id="reslogin">
        <h3>注册</h3>
        <p>邮箱</p>
        <input type="text" id="email" placeholder="请输入邮箱">
        <p>用户名</p>
        <input type="text" id="username" placeholder="请输入用户名">
        <p>密码</p>
        <input type="password" id="password" placeholder="请输入密码">
        <p>确认密码</p>
        <input type="password" id="password2" placeholder="确认密码"><br><br>
        <button onclick="First();">注册</button><br><br>
    </div>
    <div style="height: auto; display: none" class="login" id="reslogin-1">
        <h3>验证邮箱验证码</h3>
        <p>邮箱: <span id="EmailP"></span></p>
        <input type="text" id="EN" placeholder="请输入验证码">
        <button onclick="Second();">注册</button><br><br>
    </div>
    <div style="height: auto; display: none" class="login" id="reslogin-erroy">
        <h3 id="erroy"></h3>
        <button style='top: 386px;' onclick='res();'>返回注册</button>
    </div>
</main>

</body>
<script src="js/usuall/header.js"></script>
<script src="js/reslogin.js"></script>
</html>