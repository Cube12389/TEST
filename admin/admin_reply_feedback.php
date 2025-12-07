<?php
// 包含统一头部文件
include '../header.php';
include __DIR__ . '/_auth.php';

// 1. Lấy ID Feedback
$feedback_id = $_GET['id'] ?? null;

if (!$feedback_id || !is_numeric($feedback_id)) {
    header("Location: admin_view_feedback.php");
    exit;
}

$message = '';
$message_type = '';

// 2. Xử lý Form khi Admin Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response_content = trim($_POST['response_content']);
    $current_time = date('Y-m-d H:i:s');
    
    // Đã sử dụng Prepared Statement, nên an toàn.
    if (empty($response_content)) {
        // 删除回复
        $stmt = $conn->prepare("UPDATE feedback SET response = NULL, responded_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $feedback_id);
        
        if ($stmt->execute()) {
            $message = "已成功删除回复！";
            $message_type = 'success';
        } else {
            $message = "删除回复时出错：" . $conn->error;
            $message_type = 'error';
        }
    } else {
        // 保存/更新回复
        $stmt = $conn->prepare("UPDATE feedback SET response = ?, responded_at = ? WHERE id = ?");
        $stmt->bind_param("ssi", $response_content, $current_time, $feedback_id);
        
        if ($stmt->execute()) {
            $message = "已成功保存回复！";
            $message_type = 'success';
        } else {
            $message = "保存回复时出错：" . $conn->error;
            $message_type = 'error';
        }
    }
    $stmt->close();
}

// 3. Truy vấn thông tin Feedback để hiển thị
$stmt = $conn->prepare("
    SELECT 
        f.id, f.rating, f.message, f.created_at, f.response, f.reviewer_name,
        fd.name AS food_name,
        c.full_name
    FROM feedback f
    JOIN foods fd ON f.food_id = fd.id
    LEFT JOIN customers c ON f.customer_id = c.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$feedback) {
    header("Location: admin_view_feedback.php");
    exit;
}

$sender_name = htmlspecialchars($feedback['full_name'] ?: $feedback['reviewer_name'] ?: '匿名访客');

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>回复反馈 #<?= $feedback_id ?></title>
    <link rel="stylesheet" href="admin_style.css"> 
</head>
<body>
    <header class="akd-admin-header">
        <div class="akd-header-inner">
            <div class="akd-brand">ADMIN <span>FOOD</span></div>
            <nav class="akd-nav">
                <a href="admin_dashboard.php">仪表板</a>
                <a href="admin_view_feedback.php" style="background:rgba(112,128,96,0.12); font-weight: bold;">管理反馈</a>
                <a href="admin_logout.php">退出登录</a>
            </nav>
        </div>
    </header>

    <div class="page-title" style="color: var(--dark-brown);">✉️ 回复评价 #<?= $feedback_id ?></div>
    
    <section class="akd-card" style="background: transparent; box-shadow: none; padding: 0;">
        <div class="akd-panel">
            
            <?php if (!empty($message)): ?>
                <div class="message-<?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="feedback-details">
                <h3>评价信息</h3>
                <p><strong>ID:</strong> <?= $feedback['id'] ?></p>
                <p><strong>菜品:</strong> <?= htmlspecialchars($feedback['food_name']) ?></p>
                <p><strong>发送者:</strong> <?= $sender_name ?></p>
                <p><strong>发送日期:</strong> <?= date('d/m/Y H:i', strtotime($feedback['created_at'])) ?></p>
                <p><strong>评分（星）:</strong> <span class="rating-stars"><?= str_repeat('★', $feedback['rating'] ?? 0) ?></span></p>
                <p><strong>内容:</strong></p>
                <p style="padding: 10px; border: 1px dashed #ccc; background: #fff; border-radius: 4px; color: var(--text);"><?= nl2br(htmlspecialchars($feedback['message'])) ?></p>
            </div>

            <div class="response-form-area">
                <h3>管理员回复</h3>
                <form method="POST">
                    <textarea name="response_content" class="form-control" placeholder="在此输入回复内容...（留空并保存以删除旧回复）"><?= htmlspecialchars($feedback['response'] ?? '') ?></textarea>
                    
                    <div style="margin-top: 15px;">
                        <button type="submit" class="akd-btn akd-btn-primary">
                            保存回复
                        </button>
                        <a href="admin_view_feedback.php" class="akd-btn akd-btn-back">
                            返回
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>