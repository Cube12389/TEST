<?php

$servername = "localhost";
$username = "Urse";
$password = "lOb1sccJLEToPDDW";
$dbname = "urse";

$islogin = false;
$token = "";
$UrseName = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("<script>alert('连接失败，请刷新: {$conn->connect_error}'); window.location.href ='index.php';</script>");
}

if(!isset($_COOKIE["token"])) {
    $islogin = false;
} else {
    $token = $_COOKIE["token"];
    $sql = "SELECT * FROM `token` WHERE `Token`='{$token}'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if ($row["Token"] == $token) {
                if (time() > $row["Time"]) {
                    if ($LoginHTML == 1) {
                        setcookie("token", "", 0, "/");
                        echo "<script>alert('登录已过期，请重新登录'); window.location.href ='login.php';</script>";
                    }
                } else {
                    $islogin = true;
                    $UrseName = $row["Name"];
                }
            }
        }
    }
}

$conn->close();

?>

<header id='up'>

<button class="header-icondiv" onclick="window.open('index.php')" style="width: 50px;"><div class="header-icon"></div></button>
<button onclick="window.location.href = 'main.php';">首页</button>
<button onclick="window.location.href = 'news.php';">新闻</button>
<button onclick="window.location.href = 'tings.php';">动态</button>
<div class="dropdown">
    <button class="dropdown-btn">更多</button>
    <div class="dropdown-content">
        
    </div>
</div>

<?php
    if ($islogin) {
        echo "<div class=\"header-login\">";
        echo "<div class=\"dropdown-urse\">";
        echo "<button class=\"dropdown-btn\" style=\"left: 0px; width: 150px\">";
        echo "<div style=\"left: 15px; background-image: url('../../asstes/UrseIcon/BeginUrse.jfif');\" class=\"header-icon\"></div>";
        echo "<p>{$UrseName}</p>";
        echo "</button>";
        echo "<div class=\"dropdown-content\" style=\"width: 140px;\">";
        echo "</div></div></div>";
    } else {
        echo "<div class=\"header-login\">";
        echo "<button style=\"left: 0px\" onclick=\"window.location.href = 'login.php';\">登录</button>";
        echo "<button style=\"left: 75px\" onclick=\"window.location.href = 'reslogin.php';\">注册</button>";
        echo "</div>";
    }
?>

<button class="menu" onclick="ShowMenu();">
    <div class="menu-icon" style="top: 35%"></div>
    <div class="menu-icon" style="top: 45%"></div>
    <div class="menu-icon" style="top: 55%"></div>
</button>
<div id="menu-content" class="menu-content">
    <?php
        if ($islogin) {
            echo "<div class=\"menu-login\">";
            echo "<button style='width: 150px'>";
            echo "<div style=\"background-image: url('../../asstes/UrseIcon/BeginUrse.jfif');\" class=\"header-icon\"></div>";
            echo "<p style='position: absolute; left: 50px; top: 6px'>{$UrseName}</p>";
            echo "</button>";
            echo "</div>";
        } else {
            echo "<div class=\"menu-login\">";
            echo "<button style=\"left: 0px\" onclick=\"window.location.href = 'login.php';\">登录</button>";
            echo "<button style=\"left: 75px\" onclick=\"window.location.href = 'reslogin.php';\">注册</button>";
            echo "</div>";
        }
    ?>
    <button onclick="window.location.href ='main.php';">首页</button>
    <button onclick="window.location.href = 'news.php';">新闻</button>
    <button onclick="window.location.href = 'tings.php';">动态</button>
</div>

</header>