<?php
// 包含统一头部文件
include '../header.php';

// 使用统一的登录检查函数
check_login();

$order_id = intval($_GET['id'] ?? 0);
if (!$order_id) die("未找到订单。");

$orderQ = $conn->query("SELECT * FROM orders WHERE id=$order_id");
if (!$orderQ || !$orderQ->num_rows) die("订单不存在。");
$order = $orderQ->fetch_assoc();

$sql = "SELECT f.name, f.image, oi.quantity, oi.price
        FROM order_items oi
        JOIN foods f ON oi.food_id = f.id
        WHERE oi.order_id = $order_id";
$items = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>订单详情</title>
<link rel="stylesheet" href="../main.css">
<style>
<?php include 'order_style.css'; // hoặc dán đoạn CSS ở trên trực tiếp ?>
</style>
</head>
<body>
<header>
  <div class="container">
    <div class="logo"><h1>饿了就吃</h1><p>吃得好 – 身体棒</p></div>
    <nav class="menu">
      <div class="item"><a href="order.php">⬅ 返回订单</a></div>
      <div class="item"><a href="../index.php">首页</a></div>
    </nav>
  </div>
</header>

<div class="container order-detail-section">
  <h2>🧾 订单详情 #<?= $order['id'] ?></h2>
  <table>
    <tr><th>图片</th><th>菜品名称</th><th>数量</th><th>价格</th><th>小计</th></tr>
    <?php $total = 0; while($row = $items->fetch_assoc()): $subtotal = $row['quantity'] * $row['price']; $total += $subtotal; ?>
    <tr>
      <td><img src="../ảnh/<?= $row['image'] ?>" width="70"></td>
      <td><?= htmlspecialchars($row['name']) ?></td>
      <td><?= $row['quantity'] ?></td>
      <td><?= number_format($row['price'],0,",",".") ?>元</td>
      <td><?= number_format($subtotal,0,",",".") ?>元</td>
    </tr>
    <?php endwhile; ?>
  </table>
  
  <div class="total">总计: <?= number_format($total,0,",",".") ?>元</div>
</div>
</body>
</html>
