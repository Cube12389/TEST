<?php
// 包含统一头部文件
include '../header.php';

// 使用统一的登录检查函数
check_login();

$username = $_SESSION['username'];
$user_id = null;
$customer_id = null;

// 2. Lấy user_id và customer_id bằng Prepared Statements (Bảo mật)
$stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
if ($stmt_user) {
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user = $result_user->fetch_assoc()) {
        $user_id = $user['id'];
        
        $stmt_customer = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
        if ($stmt_customer) {
            $stmt_customer->bind_param("i", $user_id);
            $stmt_customer->execute();
            $result_customer = $stmt_customer->get_result();
            if ($customer = $result_customer->fetch_assoc()) {
                $customer_id = $customer['id'];
            }
            $stmt_customer->close();
        }
    }
    $stmt_user->close();
}

// 3. Lấy lịch sử đơn hàng
$orders = null;
if ($customer_id) {
    $stmt_orders = $conn->prepare("SELECT id, total, status, created_at FROM orders 
                                 WHERE customer_id = ? 
                                 AND status IN ('completed','cancelled')
                                 ORDER BY id DESC");
    if ($stmt_orders) {
        $stmt_orders->bind_param("i", $customer_id);
        $stmt_orders->execute();
        $orders = $stmt_orders->get_result();
        // Không đóng stmt_orders ở đây vì ta cần $orders->fetch_assoc()
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>购买历史</title>
<link rel="stylesheet" href="../main.css"> 
<style>
/* Nhúng CSS định dạng bảng: order_style.css nằm trong cùng thư mục */
<?php include 'order_style.css'; ?>
</style>
</head>
<body>
<header>
  <div class="container">
    <div class="logo"><h1>饿了就吃</h1><p>吃得好 – 身体棒</p></div>
    <nav class="menu">
      <a href="../index.php">首页</a>
      <a href="order.php">当前订单</a>
      <a href="../logout.php">退出登录</a>
    </nav>
  </div>
</header>

<div class="container order-detail-section">
  <h2>📜 购买历史</h2>
  
  <?php if (!$customer_id): ?>
    <p class="warning-message">该账户还没有客户信息或您需要重新登录。</p>
  <?php elseif (!$orders || $orders->num_rows === 0): ?>
    <p class="empty-message">您还没有已完成或已取消的订单。</p>
  <?php else: ?>
  <table>
    <tr><th>订单号</th><th>状态</th><th>总金额</th><th>购买日期</th></tr>
    <?php while($row = $orders->fetch_assoc()): ?>
    <tr>
      <td>#<?= htmlspecialchars($row['id']) ?></td>
      <td class="status <?= htmlspecialchars($row['status']) ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
      <td><?= number_format($row['total'],0,",",".") ?>元</td>
      <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <?php endif; ?>
</div>
</body>
</html>