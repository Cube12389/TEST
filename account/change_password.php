<?php
// 包含统一头部文件
include '../header.php';

// 使用统一的登录检查函数
check_login();
$user_id = $_SESSION['user_id'];
$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $error = "新密码和确认密码不匹配。";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !password_verify($current, $row['password'])) {
            $error = "当前密码不正确。";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hash, $user_id);
            if ($upd->execute()) {
                $success = "密码修改成功。";
            } else {
                $error = "更新密码时出错。";
            }
        }
    }
}
?>


<main style="max-width:520px;margin:40px auto;">
  <h2>修改密码</h2>
  <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
  <?php if($success) echo "<p style='color:green'>$success</p>"; ?>
  <form method="POST">
    <input type="password" name="current_password" placeholder="当前密码" required>
    <input type="password" name="new_password" placeholder="新密码" required>
    <input type="password" name="confirm_password" placeholder="确认密码" required>
    <button type="submit">保存</button>
  </form>
  <p><a href="account.php">返回</a></p>
</main>


