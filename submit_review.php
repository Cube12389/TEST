<?php
// === 强错误检测机制 ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 包含统一头部文件
include 'header.php'; 

$food_id_on_error = intval($_POST['food_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: food_detail.php?id=" . $food_id_on_error);
    exit;
}

// ... (Dòng 1 đến Dòng 25) ...
$GUEST_CUSTOMER_ID = 1; 
$customer_id = $GUEST_CUSTOMER_ID; // Gán ID Guest mặc định
$reviewer_name = trim($_POST['reviewer_name'] ?? 'Khách ẩn danh');

// 1. XÁC ĐỊNH NGƯỜI ĐÁNH GIÁ (Customer hay Guest)
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    // A. NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP: Ghi đè $customer_id bằng ID thật
    $user_id = intval($_SESSION['user_id'] ?? 0);
    
    // TÌM customer_id (ID THẬT)
    $stmt_cus = $conn->prepare("SELECT id, full_name FROM customers WHERE user_id = ? LIMIT 1");

    // === BƯỚC KIỂM TRA QUAN TRỌNG 1: Kiểm tra lỗi Prepare ===
    if (!$stmt_cus) {
        header("Location: food_detail.php?id=" . $food_id_on_error . "&error=" . urlencode("Lỗi DB (Customer): Prepare failed: " . $conn->error));
        exit;
    }
    
    $stmt_cus->bind_param("i", $user_id);
    
    // === BƯỚC KIỂM TRA QUAN TRỌNG 2: Kiểm tra lỗi Execute ===
    if (!$stmt_cus->execute()) {
        header("Location: food_detail.php?id=" . $food_id_on_error . "&error=" . urlencode("Lỗi DB (Customer): Execute failed: " . $stmt_cus->error));
        $stmt_cus->close();
        exit;
    }

    $cusQ = $stmt_cus->get_result(); // Dòng này sẽ an toàn hơn
    
    // Kiểm tra xem có kết quả không, tránh lỗi Call to a member function fetch_assoc() on null
    if ($cusQ && $row = $cusQ->fetch_assoc()) { // <--- DÒNG 33 (Sau khi thêm kiểm tra $cusQ)
        $customer_id = $row['id']; // <--- Gán ID thật
        $reviewer_name = $row['full_name'];
    } else {
        // Có thể người dùng đã đăng nhập nhưng chưa có bản ghi trong bảng customers
        // Trong trường hợp này, ta sẽ dùng ID GUEST đã thiết lập
        $customer_id = $GUEST_CUSTOMER_ID;
    }

    $stmt_cus->close();
}
// Nếu chưa đăng nhập, $customer_id vẫn giữ giá trị $GUEST_CUSTOMER_ID (1)

// ... (Các dòng code tiếp theo để lấy rating, content, và insert vào feedback) ...

// 2. LẤY DỮ LIỆU TỪ FORM VÀ LÀM SẠCH
$food_id = $food_id_on_error;
$rating = intval($_POST['rating'] ?? 5);
$rating = max(1, min(5, $rating)); 
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? ''); 
$final_message = empty($title) ? $content : "Tiêu đề: " . $title . "\n\nNội dung: " . $content;

// Kiểm tra dữ liệu bắt buộc
if ($food_id <= 0 || empty($content)) {
    header("Location: food_detail.php?id=" . $food_id . "&error=" . urlencode("Thiếu ID món ăn hoặc Nội dung Feedback.")); 
    exit;
}

// 3. CHÈN DỮ LIỆU VÀO BẢNG FEEDBACK
// LƯU Ý: Phải thêm cột reviewer_name vào bảng feedback (xem Mục 3)
$stmt_insert = $conn->prepare("INSERT INTO feedback (customer_id, reviewer_name, food_id, message, rating) 
                                 VALUES (?, ?, ?, ?, ?)");

if (!$stmt_insert) {
    header("Location: food_detail.php?id=" . $food_id . "&error=" . urlencode("Lỗi Prepare Insert: " . $conn->error));
    exit;
}

// Tham số: customer_id (i), reviewer_name (s), food_id (i), message (s), rating (i)
$stmt_insert->bind_param("ssisi", $customer_id, $reviewer_name, $food_id, $final_message, $rating);

if ($stmt_insert->execute()) {
    header("Location: food_detail.php?id=" . $food_id . "&success=review_posted");
} else {
    $errorMessage = "Lỗi khi chèn feedback: " . $stmt_insert->error;
    header("Location: food_detail.php?id=" . $food_id . "&error=" . urlencode($errorMessage));
}

$stmt_insert->close();
?>