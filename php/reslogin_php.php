<?php
// 引入数据库工具类
require_once 'usuall/mySQLtools.php';

// 获取POST参数
$name = $_POST["name"] ?? '';
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';
$password2 = $_POST["password2"] ?? '';
$verificationCode = $_POST["verification_code"] ?? '';

// 数据库配置
$servername = "localhost";
$dbUsername = "Urse";
$dbPassword = "lOb1sccJLEToPDDW";
$dbname = "urse";

// 生成随机验证码
function generateVerificationCode($length = 6) {
    $digits = '0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $digits[rand(0, strlen($digits) - 1)];
    }
    return $code;
}

// 生成随机token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// 发送验证邮件（简化版本，实际应用需要配置邮件服务器）
function sendVerificationEmail($to, $code) {
    include '../EmialSrc/PHPMailer.php';
    include '../EmialSrc/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // 服务器配置
        $mail->isSMTP();
        $mail->Host = 'smtp.163.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'm13160816175@163.com';
        $mail->Password = 'RYsCfQFHEM75LtWi';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // 收件人设置
        $mail->setFrom($email, '网站注册验证');
        $mail->addAddress($to);
        
        // 邮件内容
        $mail->isHTML(true);
        $mail->Subject = '邮箱验证 - 美林湖广附八00';
        $mail->Body = "<p>请使用以下验证码完成注册验证：</p><h2>{$code}</h2><p>验证码将在15分钟后过期</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
    
    // 模拟邮件发送成功
    return true;
}

// 初始化数据库连接
$db = new myConn();
$connectResult = $db->connect($servername, $dbUsername, $dbPassword, $dbname);

if ($connectResult['status'] === false) {
    die("<script>alert('数据库连接失败，请刷新页面重试'); window.location.href ='../index.php';</script>");
}

// 验证请求类型
if (empty($verificationCode)) {
    // 第一步：验证用户输入并发送验证码
    // 验证输入
    if (empty($name) || empty($email) || empty($password) || empty($password2)) {
        echo "301";
        $db->close();
        return;
    }
    
    if ($password !== $password2) {
        echo "302";
        $db->close();
        return;
    }
    
    // 验证用户名格式
    $usernameRegex = '/^[a-zA-Z0-9]{2,15}$/';
    if (!preg_match($usernameRegex, $name)) {
        echo "303";
        $db->close();
        return; 
    }
    
    // 验证密码格式
    $passwordRegex = '/^[a-zA-Z0-9%+\-=*]{6,15}$/';
    if (!preg_match($passwordRegex, $password)) {
        echo "304";
        $db->close();
        return;
    }
    
    // 验证邮箱格式
    $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    if (!preg_match($emailRegex, $email)) {
        echo "305";
        $db->close();
        return;
    }
    
    // 检查用户名是否已存在
    $usernameCheck = $db->select('urselogin', ['where' => ['Name' => $name]]);
    if ($usernameCheck['status'] && $usernameCheck['count'] > 0) {
        echo "400";
        $db->close();
        return;
    }
    
    // 检查邮箱是否已存在
    $emailCheck = $db->select('urselogin', ['where' => ['Email' => $email]]);
    if ($emailCheck['status'] && $emailCheck['count'] > 0) {
        echo "401";
        $db->close();
        return;
    }
    
    // 生成验证码
    $code = generateVerificationCode();
    $token = generateToken();
    $expireTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // 哈希密码
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 开始事务
    $transactionResult = $db->beginTransaction();
    if (!$transactionResult['status']) {
        echo "500";
        $db->close();
        return;
    }
    
    // 存储预注册信息
    $preRegisterData = [
        'Name' => $name,
        'Password' => $hashedPassword,
        'Email' => $email,
        'VerificationCode' => $code,
        'Token' => $token,
        'ExpireTime' => $expireTime,
        'CreatedAt' => date('Y-m-d H:i:s')
    ];
    
    // 先删除可能存在的相同邮箱的预注册记录
    $db->delete('preregister', ['Email' => $email]);
    
    // 插入新的预注册记录
    $insertResult = $db->insert('preregister', $preRegisterData);
    
    if (!$insertResult['status']) {
        $db->rollback();
        echo "500";
        $db->close();
        return;
    }
    
    // 提交事务
    $db->commit();
    
    // 发送验证邮件
    $emailSent = sendVerificationEmail($email, $code);
    
    if (!$emailSent) {
        echo "501";
        $db->close();
        return;
    }
    
    // 返回成功状态和token
    echo json_encode(['status' => '200', 'token' => $token, 'email' => $email]);
} else {
    // 第二步：验证用户提供的验证码
    $token = $_POST["token"] ?? '';
    
    if (empty($token)) {
        echo "402";
        $db->close();
        return;
    }
    
    // 查询预注册信息
    $preRegister = $db->select('preregister', ['where' => ['Token' => $token]]);
    
    if (!$preRegister['status'] || $preRegister['count'] == 0) {
        echo "403";
        $db->close();
        return;
    }
    
    $userData = $preRegister['data'][0];
    
    // 检查是否过期
    if (time() > strtotime($userData['ExpireTime'])) {
        echo "404";
        $db->close();
        return;
    }
    
    // 验证验证码
    if ($userData['VerificationCode'] !== $verificationCode) {
        echo "405";
        $db->close();
        return;
    }
    
    // 开始事务
    $transactionResult = $db->beginTransaction();
    if (!$transactionResult['status']) {
        echo "500";
        $db->close();
        return;
    }
    
    // 插入正式用户记录
    $userRecord = [
        'Name' => $userData['Name'],
        'Password' => $userData['Password'],
        'Email' => $userData['Email'],
        'CreatedAt' => date('Y-m-d H:i:s'),
        'Status' => 1 // 已验证
    ];
    
    $insertResult = $db->insert('urselogin', $userRecord);
    
    if (!$insertResult['status']) {
        $db->rollback();
        echo "500";
        $db->close();
        return;
    }
    
    // 删除预注册记录
    $deleteResult = $db->delete('preregister', ['Token' => $token]);
    
    if (!$deleteResult['status']) {
        $db->rollback();
        echo "500";
        $db->close();
        return;
    }
    
    // 提交事务
    $db->commit();
    
    // 注册成功
    echo "201";
}

// 关闭数据库连接
$db->close();

?>