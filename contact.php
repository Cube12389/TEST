<?php
// 包含统一头部文件
include 'header.php';

// 显示通知的逻辑
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = '<p style="color: green; font-weight: bold; text-align: center; margin-top: 15px;">✅ 联系发送成功! 我们将尽快回复您。</p>';
    } elseif ($_GET['status'] === 'error') {
        $error_msg = htmlspecialchars($_GET['msg'] ?? '发生未知错误。');
        $message = '<p style="color: red; font-weight: bold; text-align: center; margin-top: 15px;">❌ 错误: ' . $error_msg . '</p>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>联系我们</title>
  <link rel="stylesheet" href="main.css">
    <style>
        /* Thêm style cơ bản cho form và thông tin liên hệ */
        .contact-section {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: #fff;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .contact-info, .contact-form-wrapper {
            flex: 1;
            min-width: 300px;
        }
        .contact-info h3 {
            color: #701f1f;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .contact-info p, 
        .contact-info strong {
            color: #333333; /* Màu chữ chính */
        }
        .contact-info strong {
            color: #5d4037; /* Màu nâu đậm hơn cho các tiêu đề nhỏ */
        }
        
        /* === BỔ SUNG: Chỉnh màu cho tiêu đề form === */
        .contact-form-wrapper h2 {
            color: #701f1f; /* Màu nâu đậm chủ đạo */
            font-size: 1.8em;
            margin-top: 0;
            margin-bottom: 20px;
        }
        /* =========================================== */

        .contact-form input, .contact-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
        }
        .contact-form button {
            background-color: #701f1f;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            width: 100%;
            transition: background-color 0.3s;
        }
        .contact-form button:hover {
            background-color: #a83232;
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="logo">
            <h1>饿了就吃</h1>
            <p>吃得好 – 活得健康</p>
        </div>
        <nav>
            <a href="index.php">首页</a>
            <a href="store.php">关于我们</a>
            <a href="shop.php">产品</a>
            <a href="contact.php">联系我们</a>
            <a href="view_cart.php">🛒 购物车 <span id="cart-item-count"></span></a>
            <?php if(isset($_SESSION['username'])): ?>
                <a href="account/account.php" style="color: #ffb84d; font-weight: bold;">
                    您好, <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <a href="logout.php">退出登录</a>
            <?php else: ?>
                <a href="login.php">登录</a>
                <a href="register.php">注册</a>
            <?php endif; ?>

        </nav>
    </div>
</header>

<h1 style="text-align: center; margin-top: 30px; color: #701f1f;">联系我们</h1>

<?= $message ?>

<section class="contact-section" id="contact">
    <div class="contact-form-wrapper">
        <h2>📩 留下信息获取咨询</h2>
        <form class="contact-form" action="send_contact.php" method="POST">
            <input type="text" name="name" placeholder="您的姓名 *" required>
            <input type="email" name="email" placeholder="您的邮箱 *" required>
            <input type="tel" name="phone" placeholder="您的电话">
            <textarea name="message" placeholder="咨询内容 *" rows="5" required></textarea>
            <button type="submit">发送信息</button>
        </form>
    </div>
    
    <div class="contact-info">
        <h3>联系信息</h3>
        <p><strong>地址:</strong> 123 健康路, 美食区, 胡志明市</p>
        <p><strong>热线电话:</strong> 1900 6868 (免费)</p>
        <p><strong>邮箱:</strong> hotro@ankhidoi.vn</p>
        <p><strong>营业时间:</strong> 8:00 - 20:00 (周一 - 周六)</p>
        
        <h3 style="margin-top: 20px;">在地图上找到我们</h3>
        <div style="width: 100%; height: 200px; background-color: #e0e0e0; border: 1px solid #ccc; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #555;">
            地图显示区域 (Google Maps Embed)
        </div>
    </div>
</section>
<?php 
$current_cart_items = 0;
// 获取当前购物车数量
if(isset($_SESSION['user_id'])){
    // 已登录用户的逻辑
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
    // 访客的逻辑
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
                        // Cập nhật số lượng giỏ hàng trên Header
                        updateCartCount(data.cart_total_items);
                        // 成功通知
                        showNotification(`已将 ${data.food_name} 加入购物车!`);
                    } else {
                        showNotification(data.message || '添加到购物车时出错.', 'error');
                    }
                })
                .catch(error => {
                    console.error('连接错误:', error);
                    showNotification('服务器连接错误.', 'error');
                });
            });
        });
    });
</script>
<?php include_once "footer.php"; ?>
</body>
</html>