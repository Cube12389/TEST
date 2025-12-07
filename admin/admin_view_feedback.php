<?php
// 包含统一头部文件
include '../header.php';
include __DIR__ . '/_auth.php';

// Lấy tham số lọc từ URL (dùng cho việc lọc feedback Mới)
$filter_status = $_GET['status'] ?? ''; 

$where_clause = '';
if ($filter_status === 'new') {
    $where_clause = " WHERE f.response IS NULL OR f.response = ''";
} 

// 1. Truy vấn tất cả Feedback và JOIN với foods, customers, users
$feedback_query = "
    SELECT 
        f.id, f.rating, f.message, f.created_at, f.response, f.reviewer_name,
        fd.name AS food_name,
        c.full_name, 
        u.username
    FROM feedback f
    JOIN foods fd ON f.food_id = fd.id
    LEFT JOIN customers c ON f.customer_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
    {$where_clause} 
    ORDER BY f.created_at DESC
";

$result = $conn->query($feedback_query);

$title_suffix = ($filter_status === 'new') ? " Mới" : "";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Feedback<?= $title_suffix ?> - Admin</title>
    <link rel="stylesheet" href="admin_style.css"> 
</head>
<body>
    
    <header class="akd-admin-header">
        <div class="akd-header-inner">
            <div class="akd-brand">ADMIN <span>ĂN KHI ĐÓI</span></div>
            <nav class="akd-nav">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="admin_view_feedback.php" style="background:rgba(112,128,96,0.12); font-weight: bold;">Quản lý Feedback</a>
                <a href="admin_logout.php">Đăng xuất</a>
            </nav>
        </div>
    </header>

    <div class="page-title">💌 Quản lý Đánh giá & Feedback<?= $title_suffix ?></div>
    
    <div class="table-wrap">
        <?php if (!$result || $result->num_rows == 0): ?>
            <div class="akd-panel center">
                <p>Hiện chưa có đánh giá nào.</p>
            </div>
        <?php else: ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 15%;">Món ăn</th>
                        <th style="width: 12%;">Người gửi</th>
                        <th style="width: 8%;">Sao</th>
                        <th style="width: 35%;">Nội dung & Phản hồi</th>
                        <th style="width: 10%;">Ngày gửi</th>
                        <th style="width: 10%;">Trạng thái</th>
                        <th style="width: 5%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <?php
                        // Xác định tên người gửi
                        $sender_name = htmlspecialchars($row['full_name'] ?: $row['username'] ?: $row['reviewer_name'] ?: 'Khách ẩn danh');
                        $is_replied = !empty($row['response']);
                        // Sửa class thành status-tag-new/replied
                        $status_class = $is_replied ? 'status-replied-tag' : 'status-new-tag'; 
                        $status_text = $is_replied ? 'Đã trả lời' : 'Mới';
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['food_name']) ?></td>
                        <td><?= $sender_name ?></td>
                        <td><div class="rating-stars"><?= str_repeat('★', $row['rating'] ?? 0) ?></div></td>
                        <td>
                            <?= nl2br(htmlspecialchars($row['message'])) ?>
                            <?php if ($is_replied): ?>
                                <div class="admin-response">
                                    <strong>Phản hồi Admin:</strong><br>
                                    <?= nl2br(htmlspecialchars($row['response'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                        <td><span class="status-tag <?= $status_class ?>"><?= $status_text ?></span></td>
                        <td class="actions-links">
                            <a href="admin_reply_feedback.php?id=<?= $row['id'] ?>" class="reply-btn">
                                <?= $is_replied ? 'Sửa' : 'Phản hồi' ?>
                            </a>
                            <a href="delete_feedback.php?id=<?= $row['id'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa?');" class="delete-btn">
                                Xóa
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>