<?php
// 包含统一头部文件
include 'header.php';

// 使用统一的登录检查函数
check_login();

$user_id = intval($_SESSION['user_id']);
$item_id = intval($_POST['item_id'] ?? 0);
$action = $_POST['action'] ?? ''; // "increase" / "decrease" / "set"
$quantity = intval($_POST['quantity'] ?? 1);

if ($item_id <= 0) {
    header("Location: view_cart.php");
    exit;
}

// Lấy current item của user để đảm bảo item hợp lệ
$itQ = $conn->query("SELECT id, quantity FROM cart_items WHERE id = $item_id AND user_id = $user_id LIMIT 1");
if (!$itQ || $itQ->num_rows == 0) {
    header("Location: view_cart.php");
    exit;
}
$row = $itQ->fetch_assoc();
$cur = intval($row['quantity']);

// Xử lý action
if ($action === 'increase') {
    $cur++;
} elseif ($action === 'decrease') {
    $cur--;
} else { // set trực tiếp
    $cur = max(1, $quantity);
}

// Cập nhật DB hoặc xóa nếu quantity <= 0
if ($cur <= 0) {
    $conn->query("DELETE FROM cart_items WHERE id = $item_id AND user_id = $user_id");
} else {
    $conn->query("UPDATE cart_items SET quantity = $cur WHERE id = $item_id AND user_id = $user_id");
}

header("Location: view_cart.php");
exit;
?>
