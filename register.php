<?php
// 包含统一头部文件
include 'header.php';

// PHPMailer 特定包含
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. 基本服务器端验证
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "请填写所有必填字段。";
    } elseif ($password !== $confirm_password) {
        $error = "两次输入的密码不一致!";
    } elseif (strlen($password) < 6) {
        $error = "密码长度至少为6个字符。";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 2. 检查用户名或邮箱是否已存在
        $check_sql = "SELECT username, email FROM users WHERE username=? OR email=?";
        $stmt = $conn->prepare($check_sql);
        if (!$stmt) {
            $error = "数据库查询准备失败: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // 检查具体是用户名还是邮箱已存在
                $row = $result->fetch_assoc();
                if ($row['username'] === $username) {
                    $error = "用户名已存在!";
                } else {
                    $error = "邮箱已存在!";
                }
            } else {
                // 3. 修复SQL逻辑错误：在bind_param前声明token
                $token = bin2hex(random_bytes(16));
                
                // 添加token字段用于激活账户
                $sql = "INSERT INTO users (username, email, password, token) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $error = "数据库查询准备失败: " . $conn->error;
                } else {
                    $stmt->bind_param("ssss", $username, $email, $hashed_password, $token);

                    if ($stmt->execute()) {
                        
                        // === 发送激活邮件 ===
                        $mail = new PHPMailer(true);
                        try {
                            // 配置SMTP
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.163.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'm13160816175@163.com'; 
                            $mail->Password   = 'RYsCfQFHEM75LtWi'; 
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            // 配置邮件内容
                            $mail->setFrom('m13160816175@163.com', '饿了就吃 - 验证');
                            $mail->addAddress($email, $username);
                            $mail->isHTML(true);
                            $mail->CharSet = 'UTF-8';
                            $mail->Subject = '激活您在饿了就吃的账户!';
                            
                            // 验证链接
                            $verification_link = "http://localhost:8000/verify.php?token=$token";
                            
                            $mail->Body = "<h3>您好 $username!</h3>
                                <p>您已在 <b>饿了就吃</b> 注册了账户。</p>                         
                                <p>请点击下方链接激活您的账户：</p>
                                <p><a href='$verification_link'>立即激活账户</a></p>
                                <p>如果您没有注册，请忽略此邮件。</p>";

                            $mail->send();
                            
                            // 4. 更改流程：显示成功消息并要求检查邮件
                            $success_message = "注册成功! 请检查您的邮箱(包括垃圾邮件文件夹)以激活账户。"; 

                        } catch (Exception $e) {
                            $error = "注册成功但无法发送验证邮件。邮件发送错误: {$mail->ErrorInfo}";
                            error_log("Mailer Error: {$mail->ErrorInfo}");
                        }

                    } else {
                        $error = "注册失败: " . $conn->error;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>注册账户</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="auth.css">
</head>
<body>
  <header>
    <div class="container">
      <div class="logo"><h1>饿了就吃</h1></div>
      <nav class="menu">
        <div class="item"><a href="index.php">首页</a></div>
        <div class="item"><a href="login.php">登录</a></div>
      </nav>
    </div>
  </header>

  <div class="auth-container">
    <h2>注册账户</h2>
    
    <?php 
    // 显示错误或成功消息
    if (!empty($error)) {
        echo "<p style='color:red; text-align:center;'>$error</p>";
    } elseif (!empty($success_message)) {
        echo "<p style='color:green; text-align:center;'>$success_message</p>";
    }
    ?>

    <?php if (empty($success_message)): ?>
        <form action="register.php" method="POST">
            <input type="text" name="username" placeholder="用户名" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
            <input type="email" name="email" placeholder="邮箱" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            <input type="password" name="password" placeholder="密码" required>
            <input type="password" name="confirm_password" placeholder="确认密码" required>
            <button type="submit">注册</button>
        </form>
    <?php endif; ?>
    
    <p>已有账户？ <a href="login.php">立即登录</a></p>
  </div>
</body>
</html>
