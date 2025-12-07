<?php
include '../header.php';
include __DIR__ . '/_auth.php';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM contacts WHERE id=$id");
    header("Location: admin_contacts.php"); exit;
}

$contacts = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>联系我们</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php include __DIR__ . '/admin_header_small.php'; ?>
<div class="page-title">💬 客户反馈</div>
<div class="table-wrap">
  <div class="akd-card">
    <table class="styled-table">
      <thead><tr><th>ID</th><th>姓名</th><th>邮箱</th><th>留言</th><th>日期</th><th>操作</th></tr></thead>
      <tbody>
      <?php while($c = $contacts->fetch_assoc()): ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td style="max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($c['message']) ?>"><?= htmlspecialchars($c['message']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
          <td><a class="akd-btn akd-btn-delete" href="?delete=<?= $c['id'] ?>" onclick="return confirm('删除反馈?')">删除</a></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>