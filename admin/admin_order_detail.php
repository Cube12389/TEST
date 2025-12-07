<?php
include '../header.php';
include __DIR__ . '/_auth.php'; // Đảm bảo Admin đã đăng nhập

// 1. Lấy và kiểm tra order_id
$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) { 
    header("Location: admin_orders.php"); 
    exit; 
}

// === POST处理逻辑: 更新状态（订单、支付、会员）===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_redirect = "admin_order_detail.php?id=" . $order_id;
    
    // A. 更新支付状态 (payments.status)
    if (isset($_POST['update_payment_status'])) {
        $new_payment_status = trim($_POST['payment_status']);
        $stmt_update = $conn->prepare("UPDATE payments SET status = ? WHERE order_id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_payment_status, $order_id);
            $stmt_update->execute();
            $stmt_update->close();
            header("Location: " . $success_redirect); // 重定向刷新数据
            exit;
        }
    }
    
    // B. 更新订单状态 (orders.status)
    if (isset($_POST['update_order_status'])) {
        $new_order_status = trim($_POST['order_status']);
        $stmt_update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_order_status, $order_id);
            $stmt_update->execute();
            $stmt_update->close();
            header("Location: " . $success_redirect); // 重定向刷新数据
            exit;
        }
    }
    
    // C. 更新客户等级 (customers.membership)
    if (isset($_POST['update_customer_level'])) {
        $new_level = trim($_POST['customer_level']);
        $customer_id = intval($_POST['customer_id']); 
        
        $stmt_update = $conn->prepare("UPDATE customers SET membership = ? WHERE id = ?");
        if ($stmt_update && $customer_id > 0) {
            $stmt_update->bind_param("si", $new_level, $customer_id);
            $stmt_update->execute();
            $stmt_update->close();
            header("Location: " . $success_redirect); // 重定向刷新数据
            exit;
        }
    }
}
// ===============================================================

// 2. 查询订单详情和所有客户信息
$stmt = $conn->prepare("
    SELECT 
        o.*, 
        o.shipping_address, /* <<< Lấy địa chỉ GIAO HÀNG */
        u.username,
        c.id AS customer_id, 
        c.full_name, 
        c.phone, 
        c.address AS default_address, /* Lấy địa chỉ MẶC ĐỊNH (dùng cho tham khảo, tránh lỗi) */
        c.membership AS customer_level, 
        p.method AS payment_method,
        p.status AS payment_status
    FROM orders o 
    JOIN customers c ON o.customer_id=c.id 
    JOIN users u ON c.user_id=u.id 
    LEFT JOIN payments p ON p.order_id=o.id 
    WHERE o.id = ? LIMIT 1
");

if (!$stmt) { die("Lỗi Prepare Statement: " . $conn->error); }

$stmt->bind_param("i", $order_id);
$stmt->execute();
$orderQ = $stmt->get_result();

if (!$orderQ || $orderQ->num_rows == 0) { 
    echo "Không tìm thấy đơn hàng."; 
    exit; 
}
$order = $orderQ->fetch_assoc();
$stmt->close();

// Gán giá trị mặc định nếu không có payment (tránh lỗi)
if (!isset($order['payment_status'])) {
    $order['payment_method'] = 'Chưa có thông tin';
    $order['payment_status'] = ''; 
}

// 3. TRUY VẤN CHI TIẾT MÓN HÀNG
// 注意：此查询应使用Prepared Statement以提高安全性，但保持原有的逻辑
$items = $conn->query("SELECT oi.*, f.name FROM order_items oi JOIN foods f ON oi.food_id=f.id WHERE oi.order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>订单详情 #<?= $order_id ?></title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php include __DIR__ . '/admin_header_small.php'; ?>
<div class="page-title">🧾 订单详情 #<?= $order['id'] ?></div>
<div class="table-wrap">
  <div class="akd-card">
    <div class="akd-panel">
      <p>
        <strong style="color: #5d4037;">订单号:</strong> <span style="color: #000;">#<?= $order['id'] ?>
        — </span><strong style="color: #5d4037;">下单日期:</strong> <span style="color: #000;"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
      </p>
      
      <form method="post" style="margin-bottom: 20px; display:flex; gap: 8px; align-items:center; background:#fff3cd; padding: 10px; border: 1px solid #ffeeba; border-radius: 4px;">
          <strong style="color: #5d4037;">订单状态:</strong> 
          <span style="font-weight:bold; margin-right: 15px; color:<?= $order['status']=='completed'?'green':($order['status']=='pending'?'#ffb84d':'#007bff') ?>">
              <?= ucfirst($order['status']) ?>
          </span>
          
          <label for="o_status" style="font-weight: bold; color: #5d4037;">更新订单:</label>
          <select name="order_status" id="o_status">
              <?php $order_statuses = ['pending', 'processing', 'completed', 'canceled']; ?>
              <?php foreach ($order_statuses as $status): ?>
                  <option value="<?= $status ?>" <?= $order['status']==$status?'selected':'' ?>><?= ucfirst($status) ?></option>
              <?php endforeach; ?>
          </select>
          <button name="update_order_status" class="akd-btn akd-btn-primary" style="padding: 8px 12px; font-size: 0.9em; background-color: #007bff;">保存订单状态</button>
      </form>
      <hr>
      
      <div style="display:flex; gap: 40px;">
          <div>
              <h3 style="color: #5d4037;">客户信息</h3>
              <p><strong style="color: #5d4037;">用户名:</strong> <span style="color: #000;"><?= htmlspecialchars($order['username']) ?></p>
              <p><strong style="color: #5d4037;">姓名:</strong> <span style="color: #000;"><?= htmlspecialchars($order['full_name']) ?></p>
              <p><strong style="color: #5d4037;">电话:</strong> <span style="color: #000;"><?= htmlspecialchars($order['phone']) ?></p>
              
              <h4 style="color: #701f1f; margin-top: 20px; margin-bottom: 5px; border-bottom: 2px solid #701f1f; padding-bottom: 5px;">
                  📍 配送地址（此订单）
              </h4>
              <p style="font-weight: bold; color: #3e2723; background: #fff8e1; padding: 10px; border-left: 5px solid #701f1f; border-radius: 4px;">
                  <span style="color: #000;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
              </p>
              
              <h4 style="margin-top: 20px; margin-bottom: 5px; color: #5d4037;">会员等级: 
                  <span style="color:green; font-weight: bold; text-transform: capitalize;"><?= htmlspecialchars($order['customer_level'] ?? 'normal') ?></span>
              </h4>
          </div>
          
          <div>
              <h3 style="color: #5d4037;">支付信息</h3>
              <p><strong style="color: #5d4037;">总金额:</strong> <span style="color:red; font-size:1.1em;"><?= number_format($order['total'],0,',','.') ?>đ</span></p>
              <p><strong style="color: #5d4037;">支付方式:</strong>  <span style="color: #000;"><?= htmlspecialchars($order['payment_method']) ?></p>

              <form method="post" style="margin-top: 10px; display:flex; gap: 8px; align-items:center;">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>"> 
                  <label for="p_status" style="font-weight: bold; color: #5d4037;">支付状态:</label>
                  <select name="payment_status" id="p_status">
                      <option value="pending" <?= $order['payment_status']=='pending'?'selected':'' ?>>待处理</option>
                      <option value="paid" <?= $order['payment_status']=='paid'?'selected':'' ?>>成功</option>
                      <option value="failed" <?= $order['payment_status']=='failed'?'selected':'' ?>>失败</option>
                      <option value="refunded" <?= $order['payment_status']=='refunded'?'selected':'' ?>>已退款</option>
                      <option value="" <?= $order['payment_status']==''?'selected':'' ?>>-- 选择状态 --</option>
                  </select>
                  <button name="update_payment_status" class="akd-btn akd-btn-primary" style="padding: 8px 12px; font-size: 0.9em;">更新支付状态</button>
              </form>
          </div>
      </div>
      <hr>
      
      <h3 style="color: #5d4037;">菜品详情</h3>
      <table class="styled-table">
        <thead><tr><th>名称</th><th>数量</th><th>单价</th><th>小计</th></tr></thead>
        <tbody>
        <?php while($it = $items->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($it['name']) ?></td>
            <td><?= $it['quantity'] ?></td>
            <td><?= number_format($it['price'],0,',','.') ?>đ</td>
            <td><?= number_format($it['price']*$it['quantity'],0,',','.') ?>đ</td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <div style="text-align:right;font-weight:bold;margin-top:10px;color:#3e2723">订单总额: <?= number_format($order['total'],0,',','.') ?>đ</div>
      
      <div style="margin-top:20px">
          <a class="akd-btn akd-btn-primary" href="admin_orders.php">⬅ 返回列表</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>