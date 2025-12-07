<?php
// === 强错误检测机制 ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 包含统一头部文件
include 'header.php';

// 使用统一的登录检查函数
check_login();

// Lấy user_id (Sử dụng Prepared Statement để bảo mật)
$user_id = intval($_SESSION['user_id'] ?? 0);
if ($user_id === 0) {
    $stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    if (!$stmt_user) die("Prepare user failed: " . $conn->error);
    $stmt_user->bind_param("s", $_SESSION['username']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($u = $result_user->fetch_assoc()) {
        $user_id = $u['id'];
        $_SESSION['user_id'] = $user_id;
    } else {
        die("❌ 未找到用户 (users).");
    }
    $stmt_user->close();
}

// Lấy customer_id của người dùng (Giả sử bảng customers có full_name, phone, address)
$stmt_cus = $conn->prepare("SELECT id, full_name, phone, address FROM customers WHERE user_id = ? LIMIT 1");
if (!$stmt_cus) die("Prepare customer failed: " . $conn->error);
$stmt_cus->bind_param("i", $user_id);
$stmt_cus->execute();
$cusQ = $stmt_cus->get_result();
if (!$cusQ || $cusQ->num_rows == 0) {
    die("❌ 未找到客户信息 (customers).");
}
$customer_data = $cusQ->fetch_assoc();
$customer_id = intval($customer_data['id']);
$stmt_cus->close();

// Lưu thông tin khách hàng hiện tại để pre-fill form
$customer_name = htmlspecialchars($customer_data['full_name'] ?? '');
$customer_phone = htmlspecialchars($customer_data['phone'] ?? '');
$customer_address = htmlspecialchars($customer_data['address'] ?? '');

// Lấy giỏ hàng gần nhất và tính tổng tiền (Sử dụng Prepared Statement)
$cartQ = $conn->query("SELECT id FROM cart WHERE customer_id = $customer_id ORDER BY id DESC LIMIT 1");
if (!$cartQ || $cartQ->num_rows == 0) {
    die("❌ 购物车为空.");
}
$cart_id = intval($cartQ->fetch_assoc()['id']);

$items = [];
$total = 0;
$stmt_items = $conn->prepare("SELECT ci.food_id, ci.quantity, f.price, f.name 
                             FROM cart_items ci 
                             JOIN foods f ON ci.food_id = f.id 
                             WHERE ci.cart_id = ?");
if (!$stmt_items) die("Prepare cart items failed: " . $conn->error);
$stmt_items->bind_param("i", $cart_id);
$stmt_items->execute();
$res = $stmt_items->get_result();

if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $r['subtotal'] = $r['price'] * $r['quantity'];
        $total += $r['subtotal'];
        $items[] = $r;
    }
} else {
    die("❌ 购物车中没有商品.");
}
$stmt_items->close();

// =========================================================
// === XỬ LÝ KHI NGƯỜI DÙNG NHẤN NÚT "Xác nhận thanh toán" ===
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Lấy dữ liệu từ form
    // Đã thay đổi cách lấy dữ liệu radio button
    $payment_method_code = trim($_POST['payment_method'] ?? 'cash'); 
    $post_address = trim($_POST['address'] ?? ''); // Chỉ lấy địa chỉ giao hàng
    
    // Kiểm tra trường bắt buộc: Địa chỉ giao hàng
    if (empty($post_address)) {
        echo "<script>alert('请填写配送地址。');window.location='checkout.php';</script>";
        exit;
    }
    
    // -----------------------------------------------------------------
    // === LOGIC TÍCH HỢP VNPAY: CHUYỂN HƯỚNG SANG CỔNG THANH TOÁN ===
    // -----------------------------------------------------------------
    if ($payment_method_code === 'vnpay' || $payment_method_code === 'momo' || $payment_method_code === 'zalo_pay') {
        
        // **QUAN TRỌNG:** Lưu TỔNG TIỀN và ĐỊA CHỈ GIAO HÀNG vào SESSION
        // để chúng ta có thể sử dụng chúng trong vnpay_process.php và vnpay_return.php sau này.
        $_SESSION['checkout_temp'] = [
            'total_amount' => $total, // Tổng tiền cần thanh toán
            'customer_id' => $customer_id, // ID khách hàng
            'cart_id' => $cart_id, // ID giỏ hàng hiện tại
            'shipping_address' => $post_address, // Địa chỉ giao hàng
            'payment_method' => $payment_method_code // Lưu phương thức thanh toán
        ];

        // Tạo Form ẩn để chuyển dữ liệu đến vnpay_process.php (hoặc cổng tương ứng)
        // Hiện tại chỉ có VNPay được tích hợp sẵn, các cổng khác sẽ dùng chung logic chuyển hướng.
        ?>
       <form id="paymentForm" action="vnpay_process.php" method="POST">
    <input type="hidden" name="total_amount" value="<?= $total ?>">
    <input type="hidden" name="address" value="<?= htmlspecialchars($post_address) ?>">
    <input type="hidden" name="payment_method" value="<?= $payment_method_code ?>">
</form>
<script>document.getElementById('paymentForm').submit();</script>

        <?php
        exit; // Ngăn chặn việc tạo đơn hàng ngay lập tức
    }
    // -----------------------------------------------------------------
    
    // === LOGIC TẠO ĐƠN HÀNG (DÀNH CHO COD - Thanh toán khi nhận hàng) ===
    
    $order_status = 'pending';
    $payment_status = 'pending'; // COD: Thanh toán chưa hoàn tất

    // Bắt đầu Transaction 
    $conn->begin_transaction();

    try {
        // 5. TẠO ĐƠN HÀNG MỚI (ORDERS) - Thêm cột shipping_address
        $stmt_order = $conn->prepare("INSERT INTO orders (customer_id, shipping_address, total, status) 
                                     VALUES (?, ?, ?, ?)");
        if (!$stmt_order) throw new Exception("Prepare order failed: " . $conn->error);
        
        // Sử dụng $post_address từ form để lưu địa chỉ GIAO HÀNG cho đơn hàng này
        $stmt_order->bind_param("isds", $customer_id, $post_address, $total, $order_status);
        if (!$stmt_order->execute()) throw new Exception("Execute order failed: " . $stmt_order->error);
        $order_id = $conn->insert_id;
        $stmt_order->close();
        
        // 5B. TẠO THÔNG TIN THANH TOÁN VÀO BẢNG PAYMENTS
        // Lưu ý: payment_status vẫn là 'pending' cho COD
        $stmt_payment = $conn->prepare("INSERT INTO payments (order_id, amount, method, status)
                                         VALUES (?, ?, ?, ?)");
        if (!$stmt_payment) throw new Exception("Prepare payment failed: " . $conn->error);
        
        $stmt_payment->bind_param("idss", $order_id, $total, $payment_method_code, $payment_status);
        if (!$stmt_payment->execute()) throw new Exception("Execute payment failed: " . $stmt_payment->error);
        $stmt_payment->close();

        // 6. LƯU CHI TIẾT TỪNG MÓN HÀNG (order_items) - Logic không đổi
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, food_id, quantity, price)
                                     VALUES (?, ?, ?, ?)");
        if (!$stmt_item) throw new Exception("Prepare item failed: " . $conn->error);

        foreach ($items as $it) {
            $fid = intval($it['food_id']);
            $qty = intval($it['quantity']);
            $price = $it['price'];
            
            $stmt_item->bind_param("iiid", $order_id, $fid, $qty, $price);
            if (!$stmt_item->execute()) throw new Exception("Execute item failed: " . $stmt_item->error);
        }
        $stmt_item->close();
        
        // 7. XÓA GIỎ HÀNG SAU KHI ĐẶT HÀNG THÀNH CÔNG
        $stmt_delete_cart = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        if (!$stmt_delete_cart) throw new Exception("Prepare delete cart failed: " . $conn->error);
        $stmt_delete_cart->bind_param("i", $cart_id);
        if (!$stmt_delete_cart->execute()) throw new Exception("Execute delete cart failed: " . $stmt_delete_cart->error);
        $stmt_delete_cart->close();
        
        // Hoàn tất Transaction
        $conn->commit();
        
        echo "<script>alert('✅ 下单成功！您的订单 #" . $order_id . " 正在处理中。您已选择货到付款 (COD)。');window.location='index.php';</script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "❌ 下单时出错: " . $e->getMessage();
        echo "<script>alert('" . addslashes($errorMessage) . "');window.location='view_cart.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>结账</title>
<link rel="stylesheet" href="main.css">
<style>
/* Thêm CSS cho giao diện mới */
.payment-group {
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 15px;
}
.payment-group-header {
    font-weight: bold;
    font-size: 1.1em;
    color: #3e2723;
    margin-bottom: 10px;
    border-bottom: 2px solid #ffb84d;
    padding-bottom: 5px;
}
.payment-option {
    display: flex;
    align-items: center;
    padding: 10px 0;
    cursor: pointer;
    transition: background-color 0.2s;
}
.payment-option:hover {
    background-color: #f7f7f7;
}
.payment-option input[type="radio"] {
    margin-right: 15px;
    transform: scale(1.2); /* Phóng to radio button */
}
.payment-option label {
    font-weight: 500;
    flex-grow: 1;
}
.payment-option-logo {
    font-size: 1.2em;
    margin-right: 8px;
    color: #ffb84d;
}
</style>
</head>
<body>
<header>
  <div class="container">
    <div class="logo">
      <h1>饿了就吃</h1>
      <p>吃得好 – 身体棒</p>
    </div>
    <nav>
      <a href="index.php">首页</a>
      <a href="store.php">商店</a>
      <a href="shop.php">产品</a>
      <a href="contact.php">联系我们</a>
      

      <?php if(isset($_SESSION['username'])): ?>
        <a href="account/account.php" style="color: #ffb84d; font-weight: bold;">
          👤 <?= htmlspecialchars($_SESSION['username']) ?>
        </a>
        <a href="logout.php">退出登录</a>
      <?php else: ?>
        <a href="login.php">登录</a>
        <a href="register.php">注册</a>
      <?php endif; ?>

    </nav>
  </div>
</header>

<div class="cart-container">
  <h2>🧾 确认订单</h2>

  <table class="cart-table">
    <tr>
      <th>菜品名称</th>
      <th>价格</th>
      <th>数量</th>
      <th>小计</th>
    </tr>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td><?= number_format($it['price'], 0, ",", ".") ?>元</td>
        <td><?= $it['quantity'] ?></td>
        <td><?= number_format($it['subtotal'], 0, ",", ".") ?>元</td>
      </tr>
    <?php endforeach; ?>
  </table>

  <form method="POST">
    
    <div class="delivery-info" style="margin: 25px 0; padding: 15px; border: 1px solid #ffb84d; border-radius: 8px; background: #fff8e1;">
        <h3 style="margin-top: 0; color: #3e2723;">🚚 收件信息 (请检查并更新)</h3>
        
        <p style="color: #701f1f; font-weight: 500;">
            *Họ tên và SĐT là thông tin cố định trong hồ sơ. Vui lòng vào 
            <a href="account/edit_profile.php" style="color: blue; text-decoration: underline;">更新信息</a> 来修改。
        </p>

        <label for="name" style="display: block; font-weight: bold; margin-bottom: 5px;">姓名:</label>
        <input type="text" id="name" name="name_static" value="<?= $customer_name ?>" required 
                readonly disabled
                style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #aaa; background-color: #e9ecef; color: #555;" placeholder="全名">

        <label for="phone" style="display: block; font-weight: bold; margin-bottom: 5px;">电话号码:</label>
        <input type="tel" id="phone" name="phone_static" value="<?= $customer_phone ?>" required 
                readonly disabled
                style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #aaa; background-color: #e9ecef; color: #555;" placeholder="电话号码">

        <label for="address" style="display: block; font-weight: bold; margin-bottom: 5px;">配送地址:</label>
        <input type="text" id="address" name="address" value="<?= $customer_address ?>" required 
                style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #aaa;" placeholder="输入具体地址">
    </div>
    
    <div class="payment-selection">
        <h3 style="margin-top: 0; color: #5d4037;">💳 选择支付方式:</h3>
        
        <!-- NHÓM THANH TOÁN TẠI CHỖ (OFFLINE) -->
        <div class="payment-group">
            <div class="payment-group-header">1. 货到付款 (COD)</div>
            <div class="payment-option">
                <input type="radio" id="method_cash" name="payment_method" value="cash" checked>
                <span class="payment-option-logo">💵</span>
                <label for="method_cash">现金支付 (COD - Cash on Delivery)</label>
            </div>
        </div>
        
        <!-- NHÓM THANH TOÁN ONLINE -->
        <div class="payment-group">
            <div class="payment-group-header">2. 在线支付</div>
            
            <div class="payment-option">
                <input type="radio" id="method_vnpay" name="payment_method" value="vnpay">
                <span class="payment-option-logo">VN</span>
                <label for="method_vnpay">通过 VNPay 支付</label>
            </div>
            
            <div class="payment-option">
                <input type="radio" id="method_momo" name="payment_method" value="momo">
                <span class="payment-option-logo">MM</span>
                <label for="method_momo">通过 MoMo 支付 (未集成)</label>
            </div>
            
            <div class="payment-option">
                <input type="radio" id="method_zalo_pay" name="payment_method" value="zalo_pay">
                <span class="payment-option-logo">Z</span>
                <label for="method_zalo_pay">通过 ZaloPay 支付 (未集成)</label>
            </div>
        </div>
        
    </div>
    
    <div class="total">总计: <?= number_format($total, 0, ",", ".") ?>元</div>

    <button type="submit" class="btn btn-checkout" style="margin-top: 20px;">✅ 确认支付</button>
    <a href="view_cart.php" class="btn btn-continue">⬅ 返回购物车</a>
  </form>
</div>
</body>
</html>