<?php 
// 包含统一头部文件
include 'header.php';

// 计算购物车总数量
$cart_total = 0;

if(isset($_SESSION['user_id'])){
    // 用户已登录
    $user_id = intval($_SESSION['user_id']);
    $cusQ = $conn->query("SELECT id FROM customers WHERE user_id=$user_id LIMIT 1");
    if($cusQ && $cusQ->num_rows){
        $customer_id = intval($cusQ->fetch_assoc()['id']);
        // 计算最新购物车中所有商品的总数量
        $cartQ = $conn->query("SELECT SUM(quantity) as total 
                             FROM cart_items 
                             WHERE cart_id=(SELECT id FROM cart WHERE customer_id=$customer_id ORDER BY id DESC LIMIT 1)");
        $cart_total = $cartQ ? intval($cartQ->fetch_assoc()['total']) : 0;
    }
}else{
    // 游客
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $item){
            // 会话中的购物车存储: ['food_id'=>ID, 'quantity'=>数量]
            $cart_total += $item['quantity'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>饿了就吃</title>
    <link rel="stylesheet" href="main.css">
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
            
            <a href="view_cart.php">🛒 购物车 (<span id="cart-count"><?= $cart_total ?></span>)</a> 

            <form action="search_results.php" method="get" class="search-form-header" style="display:flex; align-items:center;">
                <input type="search" name="q" placeholder="搜索美食..." required 
                        style="padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="submit" 
                        style="background: #701f1f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                    搜索
                </button>
            </form>

            <?php if(isset($_SESSION['username'])): ?>
                <a href="account/account.php" style="color: #3e2723; font-weight: bold;">
                    你好, <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <a href="logout.php">退出登录</a>
            <?php else: ?>
                <a href="login.php">登录</a>
                <a href="register.php">注册</a>
            <?php endif; ?>

        </nav>
    </div>
</header>

<div id="banner">
    <div class="box-left">
        <h2>
            <span>美食</span><br />
            <span>超好吃</span>
        </h2>
        <p>送货上门，快速便捷</p>
        <p>随叫随到，满足需求</p>
        <button>立即体验</button>
    </div>  
</div>

<div id="wp-products">
    <h2>新品推荐</h2>
    <ul id="list-products">
        <?php
        $result = $conn->query("SELECT * FROM foods WHERE type='new' LIMIT 6");
        while($row = $result->fetch_assoc()) {
            $food_id = intval($row['id']);
            echo '<div class="item">';
            echo '<img src="ảnh/'.$row['image'].'" alt="">';
            echo '<div class="name">'.$row['name'].'</div>';
            echo '<div class="desc">'.$row['description'].'</div>';
            echo '<div class="price">'.number_format($row['price'],0,",",".").'元</div>';
            
            // 🚨 添加到购物车按钮 (使用AJAX)
            echo '<button class="add-to-cart" data-id="'.$food_id.'" data-quantity="1">';
            echo '    🛒 添加到购物车';
            echo '</button>';

            // 🚨 立即购买按钮 (使用Form POST重定向)
            echo '<form action="add_to_cart.php" method="POST" style="display:inline;">';
            echo '    <input type="hidden" name="food_id" value="'.$food_id.'">';
            echo '    <input type="hidden" name="buy_now" value="1">'; // 指示add_to_cart.php重定向
            echo '    <button type="submit">💳 立即购买</button>';
            echo '</form>';
            
            echo '</div>';
        }
        ?>
    </ul>

    <div id="view-more">
        <h2>热销产品</h2>
        <ul id="list-products">
            <?php
            $result = $conn->query("SELECT * FROM foods WHERE type='bestseller' LIMIT 6");
            while($row = $result->fetch_assoc()) {
                $food_id = intval($row['id']);
                echo '<div class="item">';
                echo '<img src="ảnh/'.$row['image'].'" alt="">';
                echo '<div class="name">'.$row['name'].'</div>';
                echo '<div class="desc">'.$row['description'].'</div>';
                echo '<div class="price">'.number_format($row['price'],0,",",".").'元</div>';
                
                // 🚨 添加到购物车按钮 (使用AJAX)
                echo '<button class="add-to-cart" data-id="'.$food_id.'" data-quantity="1">';
                echo '    🛒 添加到购物车';
                echo '</button>';

                // 🚨 立即购买按钮 (使用Form POST重定向)
                echo '<form action="add_to_cart.php" method="POST" style="display:inline;">';
                echo '    <input type="hidden" name="food_id" value="'.$food_id.'">';
                echo '    <input type="hidden" name="buy_now" value="1">'; // 指示add_to_cart.php重定向
                echo '    <button type="submit">💳 立即购买</button>';
                echo '</form>';

                echo '</div>';
            }
            ?>
        </ul>
    </div>
</div>
<?php include_once "footer.php"; ?>

<script>
    // 更新头部购物车数量的函数
    function updateCartCount(count) {
        const countElement = document.getElementById('cart-count'); // 使用ID: cart-count
        if (countElement) {
            // 如果有商品，显示数量，否则显示0
            countElement.textContent = count > 0 ? count : 0; 
        }
    }

    // 显示通知的函数
    function showNotification(message, type = 'success') {
        // 自定义：使用console.log/alert或Toastr/SweetAlert库
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // Chạy khi trang tải xong
    document.addEventListener('DOMContentLoaded', () => {
        // Lắng nghe sự kiện click cho tất cả các nút có class "add-to-cart"
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
                        // 使用服务器返回的数据更新头部购物车数量
                        updateCartCount(data.cart_total_items);
                        // 成功通知
                        showNotification(`已将 ${data.food_name} 添加到购物车!`);
                    } else {
                        showNotification(data.message || '添加到购物车时出错.', 'error');
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
<script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/6909b2e623927319492bd62e/1j96u5lrb';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
</body>
</html>
