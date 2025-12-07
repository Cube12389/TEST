<?php
// 包含统一头部文件
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE username=? OR email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        $error = "用户名或密码错误!";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>登录</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="auth.css">

</head>
<body>
  
  <div class="auth-container">
  <h2>🔑 登录</h2>
  <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <form method="POST" action="">
    <input type="text" name="username" placeholder="用户名或邮箱" required>
    <input type="password" name="password" placeholder="密码" required>
    <button type="submit">登录</button>
  </form>
  <p>您还没有账户？ <a href="register.php">立即注册</a></p>
</div>
</body>
</html>
