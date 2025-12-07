<?php
// 包含统一头部文件
include 'header.php';

// Kiểm tra phương thức gửi lên phải là POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contact.php");
    exit;
}

// Lấy dữ liệu và làm sạch cơ bản
$name = trim($_POST['name'] ?? ''); 
$email = trim($_POST['email'] ?? ''); 
$phone = trim($_POST['phone'] ?? ''); // Lấy thêm trường phone
$message = trim($_POST['message'] ?? '');

// Kiểm tra dữ liệu bắt buộc (tối thiểu)
if (empty($name) || empty($email) || empty($message)) {
    // Chuyển hướng trở lại trang liên hệ với thông báo lỗi
    header("Location: contact.php?status=error&msg=Vui lòng điền đầy đủ các trường bắt buộc.");
    exit;
}

// SỬ DỤNG PREPARED STATEMENT ĐỂ NGĂN CHẶN SQL INJECTION
$sql = "INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // 'ssss' đại diện cho 4 tham số đều là kiểu chuỗi (string)
    $stmt->bind_param("ssss", $name, $email, $phone, $message); 
    
    if ($stmt->execute()) {
        // Gửi thành công
        header("Location: contact.php?status=success");
    } else {
        // Lỗi database
        // Tùy chọn: ghi log lỗi $conn->error
        header("Location: contact.php?status=error&msg=Có lỗi xảy ra khi lưu thông tin.");
    }
    $stmt->close();
} else {
    // Lỗi Prepared Statement
    header("Location: contact.php?status=error&msg=Lỗi hệ thống.");
}

$conn->close();
exit;
?>