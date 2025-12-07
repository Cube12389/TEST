<?php
// === 强错误检测机制 ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 包含统一头部文件
include 'header.php'; 

// LẤY ID SẢN PHẨM TỪ URL (Ví dụ: ?id=1)
$food_id_hien_tai = intval($_GET['id'] ?? 0);
if ($food_id_hien_tai == 0) {
    header("Location: feedback.php"); // Hoặc store.php
    exit;
}

// 1. LẤY THÔNG TIN SẢN PHẨM HIỆN TẠI
$food_data = null;
$stmt_food = $conn->prepare("SELECT id, name, price, description, image FROM foods WHERE id = ? LIMIT 1"); 

if (!$stmt_food) {
    die("❌ Lỗi Prepare Statement FOODS: " . $conn->error);
}
$stmt_food->bind_param("i", $food_id_hien_tai);
$stmt_food->execute();
$result_food = $stmt_food->get_result();
if ($result_food->num_rows > 0) {
    $food_data = $result_food->fetch_assoc();
}
$stmt_food->close();

if (!$food_data) {
    die("❌ Không tìm thấy món ăn có ID = " . $food_id_hien_tai . ".");
}

// 2. TRUY VẤN TẤT CẢ FEEDBACK CHO MÓN ĂN HIỆN TẠI
$reviews_result = false;
$stmt_reviews = $conn->prepare("
    SELECT 
        r.rating, r.message, r.created_at, r.reviewer_name,
        r.response, r.responded_at, 
        c.full_name, u.username
    FROM feedback r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE r.food_id = ? 
    ORDER BY r.created_at DESC
");

if (!$stmt_reviews) {
    die("❌ Lỗi Prepare Statement FEEDBACK: " . $conn->error);
}

$stmt_reviews->bind_param("i", $food_id_hien_tai);
$stmt_reviews->execute();
$reviews_result = $stmt_reviews->get_result();
$stmt_reviews->close();

// 🚨 LOGIC TÍNH TOÁN SỐ LƯỢNG GIỎ HÀNG (Đưa lên đầu để dùng trong Header)
$current_cart_items = 0;
if(isset($_SESSION['user_id'])){
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
    foreach($_SESSION['cart'] as $item) $current_cart_items += $item['quantity'];
}
// -------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($food_data['name']) ?> - Chi tiết sản phẩm</title>
<link rel="stylesheet" href="main.css">
<style>
/* CSS ĐÃ CÓ */
.food-detail-container { max-width: 900px; margin: 40px auto; padding: 30px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.food-info { display: flex; gap: 30px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.food-image img { width: 300px; height: 300px; object-fit: cover; border-radius: 10px; }
.food-text h2 { color: #701f1f; margin-top: 0; }
.food-text .price { font-size: 1.5em; color: #dc3545; font-weight: bold; margin: 15px 0; }
.review-form-section { padding: 20px; border: 1px solid #ffb84d; border-radius: 8px; margin-bottom: 30px; background: #fff8e1;}
.review-item { border: 1px solid #eee; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #fff;}
.alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; font-weight: bold; }
.alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
.alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
/* CSS MỚI: PHẢN HỒI ADMIN */
.rating-stars-public {
    color: #ffc107; /* Màu vàng sao */
    font-size: 1.1em;
}
.admin-reply-box {
    margin-top: 15px;
    padding: 15px;
    background-color: #f7fcf7; 
    border-left: 4px solid #7a9b7a; 
    border-radius: 0 8px 8px 0;
    font-size: 0.95em;
}
.admin-reply-box .reply-header strong {
    color: #4b1313; 
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}
.admin-reply-box .reply-content {
    margin-left: 5px;
    padding-left: 10px;
    border-left: 1px dotted #ccc;
    color: #333;
    line-height: 1.5;
}
.admin-reply-box .reply-date {
    display: block;
    text-align: right;
    font-size: 0.8em;
    color: #888;
    margin-top: 10px;
}
.review-separator {
    border: 0;
    height: 1px;
    background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
    margin: 20px 0;
}

/* 🚨 CSS CHO NÚT MUA HÀNG TRÊN TRANG CHI TIẾT */
.add-to-cart-form button {
    /* Đảm bảo style mới được áp dụng và ghi đè inline style */
    background-color: #4A7E64 !important; 
    color: white !important; 
    padding: 10px 15px !important; 
    border: none !important; 
    border-radius: 5px !important; 
    cursor: pointer !important;
    font-weight: bold !important;
    text-transform: uppercase;
    transition: background-color 0.3s ease;
    box-shadow: 0 4px #3C6A53;
}
.add-to-cart-form button:hover {
    background-color: #5B8F77 !important;
}
.add-to-cart-form button:active {
    background-color: #3C6A53 !important;
    transform: translateY(2px); 
    box-shadow: 0 2px #3C6A53;
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

      <form action="search_results.php" method="get" class="search-form-header" style="display:flex; align-items:center;">
            <input type="search" name="q" placeholder="Tìm món ăn..." required 
                    style="padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" 
                    style="background: #701f1f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                Tìm
            </button>
        </form>

      <?php if(isset($_SESSION['username'])): ?>
        <a href="account/account.php" style="color: #3e2723; font-weight: bold;">
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

    <div class="food-detail-container">
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Gửi đánh giá thành công! Cảm ơn bạn đã chia sẻ cảm nhận.
            </div>
        <?php elseif(isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                ❌ Lỗi: <?= htmlspecialchars(urldecode($_GET['error'])) ?>
            </div>
        <?php endif; ?>

        <div class="food-info">
           <div class="food-image">
    <img src="ảnh/<?= htmlspecialchars($food_data['image'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($food_data['name']) ?>">
</div>
            <div class="food-text">
                <h2><?= htmlspecialchars($food_data['name']) ?></h2>
                <div class="price"><?= number_format($food_data['price'], 0, ",", ".") ?>đ</div>
                <p><strong>Mô tả:</strong> <?= nl2br(htmlspecialchars($food_data['description'])) ?></p>
                
                <form id="add-to-cart-form" class="add-to-cart-form">
                    <input type="number" id="quantity_input" value="1" min="1" style="width: 80px; padding: 5px; margin-right: 10px;">
                    <button type="submit">
                        🛒 Thêm vào Giỏ hàng
                    </button>
                </form>
                </div>
        </div>

        <div class="review-form-section">
    <h3 style="color:#701f1f; margin-top: 0;">🌟 Gửi Đánh giá về Món ăn này</h3>
    
    <form action="submit_review.php" method="POST">
        <input type="hidden" name="food_id" value="<?= $food_id_hien_tai ?>"> 
        
        <div style="margin-bottom: 15px;">
            <label for="reviewer_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Tên của bạn:</label>
            <input type="text" name="reviewer_name" id="reviewer_name" required 
                    value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>"
                    placeholder="Nhập tên của bạn hoặc Khách ẩn danh"
                    style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; width: 100%;">
            <?php if (!isset($_SESSION['username'])): ?>
            <small style="color:#701f1f;">*Nếu bạn đã đăng nhập, tên này sẽ được lưu cùng hồ sơ của bạn.</small>
            <?php endif; ?>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="rating" style="display: block; font-weight: bold; margin-bottom: 5px;">Đánh giá sao:</label>
            <select name="rating" id="rating" required style="padding: 8px; border-radius: 5px; border: 1px solid #ccc; width: 100%;">
                <option value="5">5 Sao - Tuyệt vời!</option>
                <option value="4">4 Sao - Rất ngon</option>
                <option value="3">3 Sao - Ngon</option>
                <option value="2">2 Sao - Tạm được</option>
                <option value="1">1 Sao - Không hài lòng</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="title" style="display: block; font-weight: bold; margin-bottom: 5px;">Tiêu đề (Tùy chọn):</label>
            <input type="text" name="title" id="title" maxlength="255" placeholder="Ví dụ: Món ăn này thật tuyệt vời!"
                    style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; width: 100%;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="content" style="display: block; font-weight: bold; margin-bottom: 5px;">Nội dung Feedback:</label>
            <textarea name="content" id="content" rows="4" required placeholder="Viết cảm nhận của bạn về món ăn..."
                      style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; width: 100%; resize: vertical;"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="background-color: #701f1f; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">
            Gửi Đánh giá
        </button>
    </form>
</div>

            <div class="reviews-section">
    <h3>Đánh giá từ Khách hàng</h3>
    
    <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
        <?php while ($review = $reviews_result->fetch_assoc()): 
            // Xác định tên người gửi
            $reviewer_display_name = htmlspecialchars($review['full_name'] ?: $review['username'] ?: $review['reviewer_name'] ?: 'Khách ẩn danh');
        ?>
            <div class="customer-review">
                
                <p><strong>Người gửi:</strong> <?= $reviewer_display_name ?></p>
                <p>
                    <strong>Đánh giá:</strong> 
                    <span class="rating-stars-public"><?= str_repeat('★', $review['rating'] ?? 0) ?></span>
                </p>
                <p class="review-date-public">Ngày gửi: <?= date('d/m/Y', strtotime($review['created_at'])) ?></p>
                <p class="review-message-public" style="padding: 5px 0;">
                    <?= nl2br(htmlspecialchars($review['message'])) ?>
                </p>
                
                <?php if (!empty($review['response'])): ?>
                    <div class="admin-reply-box">
                        <p class="reply-header">
                            <strong>Phản hồi từ Quản trị viên:</strong>
                        </p>
                        <p class="reply-content">
                            <?= nl2br(htmlspecialchars($review['response'])) ?>
                        </p>
                        <span class="reply-date">
                            Phản hồi lúc: <?= date('d/m/Y H:i', strtotime($review['responded_at'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
                </div>
            <hr class="review-separator">
        <?php endwhile; ?>
    <?php else: ?>
        <p>Chưa có đánh giá nào cho món ăn này. Hãy là người đầu tiên gửi đánh giá!</p>
    <?php endif; ?>
</div>

<script>
    const FOOD_ID = <?= $food_id_hien_tai ?>;

    // Hàm cập nhật số lượng giỏ hàng trên Header
    function updateCartCount(count) {
        const countElement = document.getElementById('cart-item-count');
        if (countElement) {
            // Nếu có hàng, hiển thị (N), nếu không thì để trống
            countElement.textContent = count > 0 ? `(${count})` : '';
        }
    }

    // Hàm hiển thị thông báo
    function showNotification(message, type = 'success') {
        // Tùy chỉnh: Dùng console.log/alert hoặc thư viện Toastr/SweetAlert
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // Cập nhật số lượng giỏ hàng ban đầu khi trang tải
    document.addEventListener('DOMContentLoaded', () => {
        // Cập nhật giá trị ban đầu (PHP đã tính toán)
        updateCartCount(<?= $current_cart_items ?>);

        const form = document.getElementById('add-to-cart-form');
        const quantityInput = document.getElementById('quantity_input');
        
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault(); // Ngăn chặn form submit (ngăn chặn tải lại trang)

                const quantity = parseInt(quantityInput.value) || 1;
                
                // Chuẩn bị dữ liệu gửi đi (JSON)
                const data = { food_id: FOOD_ID, quantity: quantity };

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
                        showNotification(`Đã thêm ${data.food_name} (x${quantity}) vào giỏ hàng!`);
                    } else {
                        showNotification(data.message || 'Lỗi khi thêm vào giỏ hàng.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Lỗi kết nối:', error);
                    showNotification('Lỗi kết nối máy chủ.', 'error');
                });
            });
        }
    });
</script>
</div>
</body>
</html>