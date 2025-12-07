<?php
// 包含统一头部文件
include '../header.php';

// 使用统一的登录检查函数
check_login();

$username = $_SESSION['username'];
$user = null;
$customer_data = [
    'full_name' => '', // Giá trị mặc định rỗng nếu chưa có record customer
    'phone' => '',
    'address' => '',
];

// 2. LẤY TẤT CẢ THÔNG TIN USER VÀ CUSTOMER
$stmt = $conn->prepare("
    SELECT 
        u.*, 
        c.full_name, 
        c.phone, 
        c.address 
    FROM users u
    LEFT JOIN customers c ON u.id = c.user_id 
    WHERE u.username = ? LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $user = $data; // Chứa dữ liệu users (id, username, email, password)
        
        // Gán dữ liệu customer (nếu tồn tại)
        if ($data['full_name'] !== null) {
            $customer_data['full_name'] = $data['full_name'];
            $customer_data['phone'] = $data['phone'];
            $customer_data['address'] = $data['address'];
        }
    } else {
        // Lỗi: Không tìm thấy user
        header("Location: ../logout.php"); 
        exit;
    }
    $stmt->close();
}

// Lấy user_id hiện tại sau khi đã fetch
$current_user_id = $user['id'] ?? 0;

// =========================================================
// 🧩 POST处理逻辑: 更新（不更改）
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && $current_user_id > 0) {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password']; 
    
    $new_full_name = $_POST['full_name'];
    $new_phone = $_POST['phone'];
    $new_address = $_POST['address'];

    // Bắt đầu Transaction
    $conn->begin_transaction();

    try {
        // 1. CẬP NHẬT BẢNG USERS 
        $update_user = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        if (!$update_user) throw new Exception("Prepare user update failed: " . $conn->error);
        $update_user->bind_param("sssi", $new_username, $new_email, $new_password, $current_user_id);
        if (!$update_user->execute()) throw new Exception("Execute user update failed: " . $update_user->error);
        $update_user->close();

        // 2. CẬP NHẬT/TẠO MỚI BẢNG CUSTOMERS
        // Kiểm tra xem record customer đã tồn tại chưa
        $check_customer = $conn->query("SELECT id FROM customers WHERE user_id = {$current_user_id}");
        
        if ($check_customer->num_rows > 0) {
            // Cập nhật record đã tồn tại
            $sql_customer = "UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE user_id = ?";
        } else {
            // Tạo record mới nếu chưa tồn tại (cho user mới đăng ký)
            $sql_customer = "INSERT INTO customers (full_name, phone, address, user_id) VALUES (?, ?, ?, ?)";
        }

        $update_customer = $conn->prepare($sql_customer);
        if (!$update_customer) throw new Exception("Prepare customer update failed: " . $conn->error);
        $update_customer->bind_param("sssi", $new_full_name, $new_phone, $new_address, $current_user_id);
        if (!$update_customer->execute()) throw new Exception("Execute customer update failed: " . $update_customer->error);
        $update_customer->close();

        // Hoàn tất Transaction
        $conn->commit();
        
        // 更新session并跳转
        $_SESSION['username'] = $new_username;
        echo "<script>alert('更新信息成功！'); window.location='account.php';</script>";
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        // 处理错误
        die("更新错误: " . $e->getMessage());
    }
}
// =========================================================
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>编辑个人信息</title>
  <link rel="stylesheet" href="../main.css">
  <style>
    body {
      background-color: #fffaf4;
      font-family: 'Segoe UI', sans-serif;
    }
    .profile-edit {
      max-width: 500px;
      margin: 60px auto;
      padding: 30px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .profile-edit h2 {
      text-align: center;
      color: #701f1f;
      margin-bottom: 25px;
    }
    .profile-edit label {
      display: block;
      font-weight: 600;
      margin-bottom: 6px;
      color: #444;
    }
    .profile-edit input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 10px;
      transition: 0.2s;
    }
    .profile-edit input:focus {
      border-color: #701f1f;
      outline: none;
      box-shadow: 0 0 5px rgba(112,31,31,0.3);
    }
    .profile-edit button {
      width: 100%;
      background: #701f1f;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s;
    }
    .profile-edit button:hover {
      background: #913333;
    }
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: #701f1f;
      text-decoration: none;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="profile-edit">
    <h2>✏️ 编辑个人信息</h2>
    <form method="POST">
      <label for="username">用户名</label>
      <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" value="<?= htmlspecialchars($user['password'] ?? '') ?>" required>
      
      <hr style="margin: 20px 0;">
      
      <label for="full_name">姓名</label>
      <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($customer_data['full_name']) ?>" required>
      
      <label for="phone">电话</label>
      <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($customer_data['phone']) ?>" required>
      
      <label for="address">默认地址</label>
      <input type="text" id="address" name="address" value="<?= htmlspecialchars($customer_data['address']) ?>" required>

      <button type="submit"> 保存更改</button>
    </form>

    <div class="back-link">
      <a href="account.php">← 返回账户</a>
    </div>
  </div>
</body>
</html>