<?php

$LoginHTML = 1;

$servername = "localhost";
$username = "urse";
$password = "lOb1sccJLEToPDDW";
$dbname = "infrom";

$conninfrom = new mysqli($servername, $username, $password, $dbname);

?>

<!DOCTYPE html>
<html>
<head>
    <?php include "php/usuall/head.php"; ?>
    <title>美林湖广附八00 - 首页</title>
    <link rel="stylesheet" href="CSS/main.css">
</head>
<body>

<?php include "php/usuall/header.php";?>

<main>

<div class="top-blue"></div>
<div class="content">
    <div class="all" style="background-color: #449fff; color: white;">
        <h1>欢迎来到美林湖广附八00网站</h1>
        <p style="margin-top: -25px;">Welcome to the website of Meilin Lake Guangzhou University Affiliated High School 800</p>
        <br>
        <img src="asstes/001.webp" width="100%">
    </div>
    <div class="cnt" style="background-color: white; color: white;">

    </div>
    <div class="cnt" style="background-color: white; ">
        <h3 style="text-align: center;">课程表</h3>
        <?php
            $sql = "SELECT * FROM `classtable`";
            $result = $conninfrom->query($sql);
        ?>
    </div>
</div>
    
</main>

</body>
<script src="js/usuall/header.js"></script>
</html>