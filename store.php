<?php 
include 'header.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>关于我们 - 饿了就吃</title>
  <link rel="stylesheet" href="main.css">
  <style>
    .about-store {
      max-width: 1000px;
      margin: 40px auto;
      padding: 20px;
      line-height: 1.6;
    }
    .about-store h2 {
      text-align: center;
      font-size: 28px;
      margin-bottom: 20px;
      color: #701f1f;
    }
    .about-section {
      display: flex;
      align-items: center;
      margin-bottom: 40px;
      gap: 20px;
    }
    .about-section img {
      width: 50%;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .about-section .text {
      width: 50%;
    }
    .about-section .text h3 {
      color: #3b6944;
      margin-bottom: 10px;
    }
    .about-section .text p {
      font-size: 15px;
      color: #333;
    }
    .highlight {
      background: #f0e68c;
      padding: 10px;
      border-left: 5px solid #701f1f;
      margin-top: 10px;
    }
     /* Slideshow */
    .slideshow-container {
      position: relative;
      max-width: 100%;
      margin: 20px auto;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }
    .slides {
      display: none;
      width: 300px;
      animation: fade 2s;
    }
    @keyframes fade {
      from {opacity: .4} 
      to {opacity: 1}
    }
    .dots {
      text-align: center;
      margin-top: 10px;
    }
    .dot {
      height: 12px;
      width: 12px;
      margin: 0 4px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      transition: background-color 0.6s ease;
      cursor: pointer;
    }
    .active-dot {
      background-color: #701f1f;
    }
  </style>
</head>
<body>
<!-- Header -->
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

  <div class="about-store">
  <h2>✨ 关于饿了就吃</h2>

  <!-- 商店图片轮播 -->
  <div class="slideshow-container">
    <img class="slides" src="ảnh/quầy.jpg" alt="饿了就吃商店">
    <img class="slides" src="ảnh/cảnh.jpg" alt="商店环境">
    <img class="slides" src="ảnh/hình.jpg" alt="员工团队">
  </div>
  <div class="dots">
    <span class="dot"></span> 
    <span class="dot"></span> 
    <span class="dot"></span> 
  </div>

<div class="about-store">
  <h2>✨ 关于饿了就吃</h2>

  <div class="about-section">
    <div class="text">
      <h3>创业历程</h3>
      <p><strong>饿了就吃</strong>诞生的初衷是为人们提供美味、快速、便捷的食物。
      我们的厨师团队使用新鲜食材，创造出独特的口味，既保留传统又结合现代元素。</p>
    </div>
    <img src="ảnh/món.jpg" alt="饿了就吃商店">
  </div>

  <div class="about-section">
    <img src="ảnh/bếp.jpg" alt="Không gian cửa hàng">
    <div class="text">
      <h3>环境与服务</h3>
      <p>我们不仅提供美味的食物，还提供舒适的体验。
      友好的环境和周到的服务是顾客愿意再次光临的原因。</p>
      <div class="highlight">
        💡 使命: <em>"饿了就吃 – 吃得好，活得健康，快乐每一天!"</em>
      </div>
    </div>
  </div>

  <div class="about-section">
    <div class="text">
      <h3>未来愿景</h3>
      <p>未来，<strong>饿了就吃</strong>不仅是一家食品店，
      还将成为领先的食品品牌，与安心、品质和每一餐的快乐紧密相连。</p>
    </div>
    <img src="ảnh/staff.jpg" alt="员工团队">
  </div>
</div>
<script>
let slideIndex = 0;
showSlides();

function showSlides() {
  let i;
  let slides = document.getElementsByClassName("slides");
  let dots = document.getElementsByClassName("dot");

  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";  
  }

  slideIndex++;
  if (slideIndex > slides.length) {slideIndex = 1}    

  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active-dot", "");
  }

  slides[slideIndex-1].style.display = "block";  
  dots[slideIndex-1].className += " active-dot";

  setTimeout(showSlides, 4000); // đổi ảnh sau 4s
}
</script>
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
