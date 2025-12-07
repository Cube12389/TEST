<?php
include 'header.php';

// --- 分类过滤处理 ---
$selected_category_id = intval($_GET['category_id'] ?? 0);
$current_category_name = '所有产品';

// 1. 获取所有分类列表（用于创建选择导航）
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat;
        if ($cat['id'] == $selected_category_id) {
            $current_category_name = $cat['name'];
        }
    }
}

// 2. 根据选择获取产品列表
$sql = "SELECT f.id, f.name, f.price, f.image, f.description, c.name AS category 
        FROM foods f
        JOIN categories c ON f.category_id = c.id";

// 如果用户选择了分类，则添加过滤条件
if ($selected_category_id > 0) {
    // 使用Prepared Statement进行主查询（更安全）
    $sql .= " WHERE f.category_id = ?";
    $stmt = $conn->prepare($sql . " ORDER BY f.created_at DESC");
    $stmt->bind_param("i", $selected_category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // 不过滤：获取所有产品
    $result = $conn->query($sql . " ORDER BY f.created_at DESC");
}

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
// --- KẾT THÚC PHẦN XỬ LÝ LỌC ---

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>产品 - 饿了就吃</title>
    <link rel="stylesheet" href="main.css">
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
            <a href="store.php">商店</a>
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

<div id="wp-products" class="store-page">
    <h2>🍔 <?= $current_category_name ?></h2>
    
    <div class="category-selector" style="text-align: center; margin-bottom: 30px;">
        <label for="category_select" style="font-size: 18px; font-weight: bold; margin-right: 15px;">按分类过滤:</label>
        <select id="category_select" onchange="window.location.href=this.value" 
            style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px;">
            
            <option value="shop.php" <?= $selected_category_id == 0 ? 'selected' : '' ?>>所有产品</option>
            
            <?php foreach ($categories as $cat): ?>
                <option value="shop.php?category_id=<?= $cat['id'] ?>" 
                    <?= $selected_category_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (empty($products)): ?>
    <p style="text-align: center; color: #5d4037;">此分类下没有产品。</p>
<?php else: ?>
    <div id="list-products" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
        <?php foreach ($products as $product): 
            // Tạo URL động tới trang chi tiết sản phẩm
            $detail_url = "food_detail.php?id=" . intval($product['id']);
        ?>
        <div class="item">
            
            <a href="<?= $detail_url ?>" style="text-decoration: none; color: inherit;">
                <img src="ảnh/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="name"><?= htmlspecialchars($product['name']) ?></div>
                <div class="desc"><?= htmlspecialchars($product['description']) ?></div>
                <div class="price"><?= number_format($product['price'], 0, ",", ".") ?>đ</div>
            </a>
             <!-- 加入购物车按钮 -->
    <button class="add-to-cart" data-id="<?= intval($product['id']) ?>" data-quantity="1">
        🛒 加入购物车
    </button> 
            <!-- 立即购买按钮 -->
    <form action="add_to_cart.php" method="POST" style="display:inline;">
        <input type="hidden" name="food_id" value="<?= intval($product['id']) ?>">
        <input type="hidden" name="buy_now" value="1">
        <button type="submit">💳 立即购买</button>
    </form>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<!--// 更新购物车中的商品数量 -->
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
                        showNotification(data.message || 'Lỗi khi thêm vào giỏ hàng.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Lỗi kết nối:', error);
                    showNotification('Lỗi kết nối máy chủ.', 'error');
                });
            });
        });
    });
</script>
<?php include_once "footer.php"; ?>

</body>
</html>