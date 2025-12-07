<?php
// 包含统一头部文件
include '../header.php';

// 使用统一的登录检查函数
check_login();

$username = $_SESSION['username'];
$user_data = null;
$customer_data = [
    'full_name' => '未更新',
    'phone' => '未更新',
    'address' => '未更新',
    'membership' => 'normal'
];

// 2. Lấy thông tin User và Customer (bao gồm Membership) bằng Prepared Statement
$stmt = $conn->prepare("
    SELECT 
        u.*, 
        c.full_name, 
        c.phone, 
        c.address, 
        c.membership 
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
        $user_data = $data;
        
        // Cập nhật thông tin khách hàng nếu tồn tại
        if ($data['full_name'] !== null) {
            $customer_data['full_name'] = $data['full_name'];
            $customer_data['phone'] = $data['phone'];
            $customer_data['address'] = $data['address'];
        }
        $customer_data['membership'] = $data['membership'] ?? 'normal';
    }
    $stmt->close();
}
?>
<?php 
// 更新SESSION中的购物车

$current_cart_items = 0;
// 获取当前购物车数量
if(isset($_SESSION['user_id'])){
    // 已登录用户逻辑
    $user_id = intval($_SESSION['user_id']);
    $cusQ = $conn->query("SELECT id FROM customers WHERE user_id=$user_id LIMIT 1");
    if($cusQ && $cusQ->num_rows){
        $customer_id=intval($cusQ->fetch_assoc()['id']);
        $cartQ = $conn->query("SELECT id FROM cart WHERE customer_id=$customer_id ORDER BY id DESC LIMIT 1");
        if($cartQ && $cartQ->num_rows){
            $cart_id=intval($cartQ->fetch_assoc()['id']);
            $totalItemsQ = $conn->query("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id=$cart_id");
            $current_cart_items = $totalItemsQ->fetch_assoc()['total'] ?? 0;
        }
    }
} else if (isset($_SESSION['cart'])) {
    // 访客逻辑
    foreach($_SESSION['cart'] as $item) $current_cart_items += $item['quantity'];
}
?>

<script>
    // Hàm cập nhật số lượng giỏ hàng trên Header
    function updateCartCount(count) {
        const countElement = document.getElementById('cart-item-count');
        if (countElement) {
            countElement.textContent = count > 0 ? `(${count})` : '';
        }
    }

    // Hàm hiển thị thông báo
    function showNotification(message, type = 'success') {
        // Có thể thay thế bằng thư viện thông báo (Toastr, SweetAlert)
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // Cập nhật số lượng giỏ hàng ban đầu khi trang tải
    document.addEventListener('DOMContentLoaded', () => {
        updateCartCount(<?= $current_cart_items ?>);

        // Lắng nghe sự kiện click cho nút "Thêm vào giỏ hàng"
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                const foodId = e.target.getAttribute('data-id');
                const quantity = parseInt(e.target.getAttribute('data-quantity') || 1);
                
                // Chuẩn bị dữ liệu gửi đi (JSON)
                const data = { food_id: foodId, quantity: quantity };

                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 更新头部的购物车数量
                        updateCartCount(data.cart_total_items);
                        // 成功通知
                        showNotification(`已将${data.food_name}添加到购物车！`);
                    } else {
                        showNotification(data.message || '添加到购物车时出错。', 'error');
                    }
                })
                .catch(error => {
                    console.error('连接错误:', error);
                    showNotification('服务器连接错误。', 'error');
                });
            });
        });
    });
</script>


<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>我的账户</title>
    <link rel="stylesheet" href="../main.css">
    <style>
        /* Tối ưu hóa CSS cho giao diện hiện đại */
        .account-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            border: none;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .account-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #5d4037; /* Màu nâu đậm hơn */
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-card {
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .info-card strong {
            display: block;
            color: #5d4037;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .info-card span {
            font-weight: 600;
            color: #333;
        }
        
        /* Style cho Membership Badge */
        .membership-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            font-size: 0.85em;
            display: inline-block;
            margin-top: 5px;
        }
        .membership-normal { background-color: #6c757d; }
        .membership-silver { background-color: #adb5bd; }
        .membership-gold { background-color: #ffc107; color: #343a40; } /* Vàng đậm cho dễ đọc */
        .membership-vip { background-color: #dc3545; }
        
        /* Style cho Action Buttons */
        .account-actions a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 15px;
            background-color: #701f1f;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .account-actions a:hover {
            background-color: #a83232;
            text-decoration: none;
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
        <nav class="menu">
          <div class="item"><a href="../index.php">首页</a></div>
          <div class="item"><a href="../store.php">商店</a></div>
          <div class="item"><a href="../view_cart.php">🛒 购物车 <span id="cart-item-count"></span></a></div>
          <div class="item"><a href="../logout.php">退出登录</a></div>
        </nav>
      </div>
    </header>

    <div class="account-container">
        <h2>👤 账户信息</h2>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>登录信息</h3>
                <p><strong>用户名:</strong> <span><?= htmlspecialchars($user_data['username']); ?></span></p>
                <p><strong>邮箱:</strong> <span><?= htmlspecialchars($user_data['email']); ?></span></p>
                <p><strong>创建日期:</strong> <span><?= date('d/m/Y', strtotime($user_data['created_at'])); ?></span></p>
            </div>
            
            <div class="info-card">
                <h3>客户信息</h3>
                <p>
                    <strong>会员等级:</strong> 
                    <span class="membership-badge membership-<?= strtolower($customer_data['membership']); ?>">
                        <?= ucfirst(htmlspecialchars($customer_data['membership'])); ?>
                    </span>
                </p>
                <p><strong>姓名:</strong> <span><?= htmlspecialchars($customer_data['full_name']); ?></span></p>
                <p><strong>电话:</strong> <span><?= htmlspecialchars($customer_data['phone']); ?></span></p>
                <p><strong>地址:</strong> <span><?= htmlspecialchars($customer_data['address']); ?></span></p>
            </div>
        </div>
        
        <hr style="border: 0; height: 1px; background: #eee; margin: 25px 0;">

        <div class="account-actions" style="text-align:center;">
            <a href="order.php">📦 当前订单</a>
            <a href="order_history.php">📜 订单历史</a>
            <a href="edit_profile.php">✏️ 更新信息</a>
        </div>
    </div>
</body>
</html>
