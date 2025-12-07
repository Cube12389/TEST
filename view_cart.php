<?php
// 包含统一头部文件
include 'header.php';

// 使用统一的登录检查函数
check_login();

// Lấy user_id từ session hoặc lookup username
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} else {
    $username = $conn->real_escape_string($_SESSION['username']);
    $u = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
    if ($u && $u->num_rows) {
        $user_id = intval($u->fetch_assoc()['id']);
    } else {
        die("未找到用户。");
    }
}

// Lấy customer_id
$cusQ = $conn->query("SELECT id FROM customers WHERE user_id = $user_id LIMIT 1");
if ($cusQ && $cusQ->num_rows) {
    $customer_id = intval($cusQ->fetch_assoc()['id']);
} else {
    // nếu chưa có customers -> giỏ trống
    $customer_id = 0;
}

// Lấy cart_id mới nhất (nếu có)
$cart_id = 0;
if ($customer_id) {
    $cartQ = $conn->query("SELECT id FROM cart WHERE customer_id = $customer_id ORDER BY id DESC LIMIT 1");
    if ($cartQ && $cartQ->num_rows) $cart_id = intval($cartQ->fetch_assoc()['id']);
}

// Xử lý xóa (nếu có param remove)
if (isset($_GET['remove'])) {
    $rem = intval($_GET['remove']);
    if ($rem > 0) {
        $conn->query("DELETE FROM cart_items WHERE id = $rem AND cart_id = $cart_id");
    }
    header("Location: view_cart.php");
    exit;
}

// Lấy danh sách items
$items = [];
$total = 0;
if ($cart_id) {
    $sql = "SELECT ci.id AS item_id, f.id AS food_id, f.name, f.price, f.image, ci.quantity
            FROM cart_items ci
            JOIN foods f ON ci.food_id = f.id
            WHERE ci.cart_id = $cart_id";
    $res = $conn->query($sql);
    if ($res && $res->num_rows) {
        while ($r = $res->fetch_assoc()) {
            $r['subtotal'] = floatval($r['price']) * intval($r['quantity']);
            $total += $r['subtotal'];
            $items[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>购物车</title>
<link rel="stylesheet" href="main.css">
</head>
<body>
<!-- Header -->
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
      <a href="view_cart.php">🛒 购物车 <span id="cart-item-count"></span></a>

      <?php if(isset($_SESSION['username'])): ?>
  <a href="account/account.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
  <a href="logout.php">退出登录</a>
<?php else: ?>
  <a href="login.php">登录</a>
  <a href="register.php">注册</a>
<?php endif; ?>

    </nav>
  </div>
</header>

<div class="cart-container">
  <h2>🛒 您的购物车</h2>

  <?php if (empty($items)): ?>
    <p style="text-align:center;color:#f0e68c;">购物车为空。 <a href="index.php" class="btn btn-continue">继续购物</a></p>
  <?php else: ?>
    <table class="cart-table">
      <tr>
        <th>图片</th>
        <th>菜品名称</th>
        <th>价格</th>
        <th>数量</th>
        <th>小计</th>
        <th>操作</th>
      </tr>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><img src="ảnh/<?= htmlspecialchars($it['image']) ?>" alt="<?= htmlspecialchars($it['name']) ?>"></td>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td><?= number_format($it['price'], 0, ',', '.') ?>元</td>
        <td>
          <form method="POST" action="update_cart.php" class="quantity-form">
            <input type="hidden" name="item_id" value="<?= intval($it['item_id']) ?>">
            <button type="submit" name="action" value="decrease" class="qty-btn">➖</button>
            <input class="qty-input" type="number" name="quantity" value="<?= intval($it['quantity']) ?>" min="1">
            <button type="submit" name="action" value="increase" class="qty-btn">➕</button>
          </form>
        </td>
        <td><?= number_format($it['subtotal'], 0, ',', '.') ?>元</td>
        <td><a class="btn btn-remove" href="view_cart.php?remove=<?= intval($it['item_id']) ?>" onclick="return confirm('从购物车中删除商品？')">❌ 删除</a></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <div class="total">总计: <?= number_format($total, 0, ',', '.') ?>元</div>
    <div style="text-align:right;">
      <a class="btn btn-checkout" href="checkout.php">✅ 结账</a>
      <a class="btn btn-continue" href="index.php">⬅ 继续购物</a>
    </div>
  <?php endif; ?>
</div>
<?php 
$current_cart_items = 0;
// Lấy số lượng giỏ hàng hiện tại
if(isset($_SESSION['user_id'])){
    // Logic cho người dùng đã đăng nhập
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
    // Logic cho khách vãng lai
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
                        showNotification(`已将 ${data.food_name} 添加到购物车！`);
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
</body>
</html>
