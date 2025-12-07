<?php
// 包含统一头部文件
include '../header.php';

// 使用统一的登录检查函数
check_login();

$user_id = $_SESSION['user_id'];

$customer = $conn->query("SELECT id FROM customers WHERE user_id=$user_id LIMIT 1")->fetch_assoc();
$customer_id = $customer['id'];

$sql = "SELECT * FROM orders 
        WHERE customer_id=$customer_id 
        AND status IN ('pending','processing')
        ORDER BY id DESC";
$orders = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>我的订单</title>
<link rel="stylesheet" href="../main.css">
<style>
/* === Giao diện nền tổng thể === */
body {
  background-color: #F6F2E9;
  font-family: 'Segoe UI', sans-serif;
  color: #2C1A12;
}
/* === Bảng === */
table {
  width: 100%;
  border-collapse: collapse;
  background: #FCFCFA;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  border-radius: 12px;
  overflow: hidden;
}
/* === Tiêu đề bảng === */
th {
  background-color: #4E6C3A;
  color: #FDF8F5;
  text-align: left;
  padding: 12px;
  font-weight: 600;
  letter-spacing: 0.5px;
}
/* === Nội dung bảng === */
td {
  padding: 12px;
  border-top: 1px solid #ddd;
  color: #2C1A12;
  background-color: #FFF;
}
/* === Hiệu ứng khi rê chuột === */
tr:hover td {
  background-color: #EFE9D9;
}
/* === Tiêu đề trang === */
h2 {
  text-align: center;
  color: #4B3B2B;
  margin-bottom: 20px;
}
/* === Nút === */
.btn {
  padding: 8px 16px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  color: #fff;
  margin: 0 10px;
  transition: 0.3s;
}
.btn-view, .btn-back { background-color: #607D3B; }
.btn-view:hover, .btn-back:hover { background-color: #7EA94B; }
/* === Trạng thái === */
.status.pending { color: #C99200; }
.status.processing { color: #3C91E6; }
/* === Section === */
.order-detail-section {
  background-color: #E9D8C0;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  margin-top: 20px;
}
</style>
</head>
<body>
<header>
  <div class="container">
    <div class="logo"><h1>饿了就吃</h1><p>吃得好 – 身体棒</p></div>
    <nav class="menu">
      <div class="item"><a href="../index.php">首页</a></div>
      <div class="item"><a href="order_history.php">购买历史</a></div>
      <div class="item"><a href="../view_cart.php">🛒 购物车</a></div>
      <div class="item"><a href="../logout.php">退出登录</a></div>
    </nav>
  </div>
</header>

<div class="container order-detail-section">
  <h2>📦 当前订单</h2>
  <table>
    <tr><th>订单号</th><th>状态</th><th>总金额</th><th>创建日期</th><th>操作</th></tr>
    <?php while($row = $orders->fetch_assoc()): ?>
    <tr>
      <td>#<?= $row['id'] ?></td>
      <td class="status <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
      <td><?= number_format($row['total'],0,",",".") ?>元</td>
      <td><?= $row['created_at'] ?></td>
      <td><a class="btn btn-view" href="order_detail.php?id=<?= $row['id'] ?>">👁️ 查看详情</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
</body>
</html>

