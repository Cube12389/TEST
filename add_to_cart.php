<?php
// 包含统一头部文件
include 'header.php';

// Kiểm tra xem request là JSON (AJAX) hay form POST bình thường (Mua ngay)
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
$isAjax = strpos($contentType, 'application/json') !== false;

// Lấy dữ liệu
if ($isAjax) {
    $data = json_decode(file_get_contents('php://input'), true);
    $food_id = intval($data['food_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);
    $buy_now = 0;
} else {
    $food_id = intval($_POST['food_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $buy_now = intval($_POST['buy_now'] ?? 0);
}

if ($food_id <= 0 || $quantity <= 0) {
    if ($isAjax) {
        echo json_encode(['success'=>false,'message'=>'数据无效']);
    } else {
        header("Location: shop.php");
    }
    exit;
}

// 检查产品
$fQ = $conn->query("SELECT id, name FROM foods WHERE id = $food_id LIMIT 1");
if (!$fQ || $fQ->num_rows==0) {
    if ($isAjax) echo json_encode(['success'=>false,'message'=>'产品不存在']);
    else header("Location: shop.php");
    exit;
}
$food = $fQ->fetch_assoc();
$food_name = $food['name'];

// XỬ LÝ GIỎ HÀNG (giữ nguyên logic của bạn)
if(isset($_SESSION['user_id'])){
    $user_id = intval($_SESSION['user_id']);
    $cusQ = $conn->query("SELECT id FROM customers WHERE user_id=$user_id LIMIT 1");
    if($cusQ && $cusQ->num_rows) $customer_id=intval($cusQ->fetch_assoc()['id']);
    else {
        $conn->query("INSERT INTO customers (user_id) VALUES ($user_id)");
        $customer_id = $conn->insert_id;
    }

    $cartQ = $conn->query("SELECT id FROM cart WHERE customer_id=$customer_id ORDER BY id DESC LIMIT 1");
    if($cartQ && $cartQ->num_rows) $cart_id=intval($cartQ->fetch_assoc()['id']);
    else {
        $conn->query("INSERT INTO cart (customer_id) VALUES ($customer_id)");
        $cart_id=$conn->insert_id;
    }

    $check = $conn->query("SELECT id, quantity FROM cart_items WHERE cart_id=$cart_id AND food_id=$food_id LIMIT 1");
    if($check && $check->num_rows){
        $r = $check->fetch_assoc();
        $newQty = intval($r['quantity']) + $quantity;
        $conn->query("UPDATE cart_items SET quantity=$newQty WHERE id=".$r['id']);
    } else {
        $conn->query("INSERT INTO cart_items (cart_id, food_id, quantity) VALUES ($cart_id, $food_id, $quantity)");
    }

    $totalItemsQ = $conn->query("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id=$cart_id");
    $totalItems = $totalItemsQ->fetch_assoc()['total'] ?? 0;

} else {
    // Khách vãng lai
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $product_exists=false;
    foreach($_SESSION['cart'] as $key=>$item){
        if($item['food_id']==$food_id){
            $_SESSION['cart'][$key]['quantity']+=$quantity;
            $product_exists=true;
            break;
        }
    }
    if(!$product_exists) $_SESSION['cart'][]=['food_id'=>$food_id,'quantity'=>$quantity];

    $totalItems=0;
    foreach($_SESSION['cart'] as $item) $totalItems+=$item['quantity'];
}

// Nếu là Mua ngay (form POST bình thường) → redirect thẳng giỏ hàng
if(!$isAjax && $buy_now){
    header("Location: view_cart.php");
    exit;
}

// Nếu là AJAX → trả JSON
if($isAjax){
    echo json_encode([
        'success'=>true,
        'food_name'=>$food_name,
        'cart_total_items'=>$totalItems
    ]);
}
exit;
