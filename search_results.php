<?php
// 包含统一头部文件
include 'header.php'; 

$search_query = trim($_GET['q'] ?? '');
$products = [];

// Xử lý truy vấn nếu có từ khóa
if (!empty($search_query)) {
    // Sử dụng Prepared Statement để bảo mật
    $like_term = '%' . $search_query . '%';
    
    // Truy vấn để lấy foods và JOIN categories để lấy tên danh mục
    $sql = "
        SELECT 
            f.id, 
            f.name, 
            f.price, 
            f.image, 
            f.description, 
            c.name AS category 
        FROM foods f 
        JOIN categories c ON f.category_id = c.id
        WHERE f.name LIKE ? OR f.description LIKE ? 
        ORDER BY f.name ASC
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Tham số 'ss' cho 2 giá trị string
        $stmt->bind_param("ss", $like_term, $like_term); 
        $stmt->execute();
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
    }
}

// 🚨 LOGIC TÍNH TOÁN SỐ LƯỢNG GIỎ HÀNG (Đưa lên đầu để dùng trong Header)
$current_cart_items = 0;
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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm | Ăn Khi Đói</title>
    <link rel="stylesheet" href="main.css">
    <style>
        /* Đảm bảo style của sản phẩm khớp với shop.php (nếu cần, bạn nên đặt style này trong main.css) */
        #wp-products { max-width: 1200px; margin: 30px auto; padding: 20px; }
        #list-products { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .item { 
            width: 280px; 
            border: 1px solid #ccc; 
            padding: 10px; 
            border-radius: 8px; 
            text-align: center;
        }
        .item img { 
            width: 100%; 
            height: auto; 
            border-radius: 4px; 
            margin-bottom: 10px;
        }
        .item .name { font-weight: bold; margin: 5px 0; }
        .item .price { color: #dc3545; font-weight: bold; margin: 5px 0; }
        .item .desc { font-size: 0.9em; color: #666; margin-bottom: 10px; }
        
        /* 🚨 Style cho nút Thêm vào giỏ hàng (AJAX) */
        .add-to-cart { 
            background-color: #4A7E64; /* Màu Rêu đậm */
            color: white; 
            padding: 8px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            margin-bottom: 5px;
            width: 100%;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .add-to-cart:hover {
            background-color: #5B8F77;
        }

        /* 🚨 Style cho nút Mua Ngay (FORM POST) */
        .buy-now-form button { 
            background-color: #701f1f; /* Màu Nâu đậm */
            color: white; 
            padding: 8px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .buy-now-form button:hover {
            background-color: #8a3333;
        }
    </style>
</head>
<body>
    <header>
      <div class="container">
        <div class="logo">
          <h1>ĂN KHI ĐÓI</h1>
          <p>Ăn ngon – Sống khỏe</p>
        </div>
        <nav>
          <a href="index.php">TRANG CHỦ</a>
          <a href="store.php">CỬA HÀNG</a>
          <a href="shop.php">SẢN PHẨM</a>
          <a href="contact.php">LIÊN HỆ</a>
          <a href="view_cart.php">🛒 Giỏ hàng <span id="cart-item-count"><?= $current_cart_items ?></span></a>
          
          <form action="search_results.php" method="get" class="search-form-header" style="display:inline-flex; align-items:center; margin-left: 10px;">
              <input type="search" name="q" placeholder="Tìm món ăn..." required value="<?= htmlspecialchars($search_query) ?>"
                      style="padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; width: 150px;">
              <button type="submit" 
                      style="background: #701f1f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                  Tìm
              </button>
          </form>
          
          <?php if(isset($_SESSION['username'])): ?>
              <a href="account/account.php" style="color: #ffb84d; font-weight: bold;">
                  Xin chào, <?= htmlspecialchars($_SESSION['username']) ?>
              </a>
              <a href="logout.php">Đăng xuất</a>
          <?php else: ?>
              <a href="login.php">Đăng nhập</a>
              <a href="register.php">Đăng ký</a>
          <?php endif; ?>
        </nav>
      </div>
    </header>

    <div id="wp-products" class="store-page">
        <h2>🔍 Kết Quả Tìm Kiếm: "**<?= htmlspecialchars($search_query) ?>**"</h2>
        <p style="text-align: center; color: #5d4037; font-size: 1.1em; margin-bottom: 20px;">
            <?php if (!empty($search_query) && !empty($products)): ?>
                Tìm thấy **<?= count($products) ?>** sản phẩm phù hợp.
            <?php endif; ?>
        </p>
        
        <?php if (empty($products)): ?>
            <p style="text-align: center; color: #5d4037;">
                <?php if (!empty($search_query)): ?>
                    Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?= htmlspecialchars($search_query) ?>".
                <?php else: ?>
                    Vui lòng nhập từ khóa để tìm kiếm.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div id="list-products">
                <?php foreach ($products as $product): 
                    $food_id = intval($product['id']);
                ?>
                <div class="item">
                    <a href="food_detail.php?id=<?= $food_id ?>" style="text-decoration: none; color: inherit;">
                        <img src="ảnh/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="desc"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</div>
                        <div class="price"><?= number_format($product['price'], 0, ",", ".") ?>đ</div>
                    </a>
                    
                    <button class="add-to-cart" data-id="<?= $food_id ?>" data-quantity="1">
                        🛒 Thêm vào giỏ hàng
                    </button>

                    <form action="add_to_cart.php" method="POST" class="buy-now-form">
                        <input type="hidden" name="food_id" value="<?= $food_id ?>">
                        <input type="hidden" name="buy_now" value="1"> 
                        <button type="submit">💳 Mua Ngay</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
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
                        // Thông báo thành công
                        showNotification(`Đã thêm ${data.food_name} vào giỏ hàng!`);
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