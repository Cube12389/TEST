<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<link rel="stylesheet" href="admin_style.css">
<header class="akd-admin-header">
  <div class="akd-header-inner">
    <div class="akd-brand">🍽️ <span>饿了就吃管理员</span></div>
    <nav class="akd-nav">
      <a href="admin_dashboard.php">仪表板</a>
      <a href="admin_products.php">产品</a>
      <a href="admin_orders.php">订单管理</a>
      <a href="admin_view_feedback.php">评价管理</a>
      <a href="admin_contacts.php">联系我们</a>
      <a href="admin_users.php">用户</a>
      <a class="akd-logout" href="admin_logout.php">退出登录</a>
    </nav>
  </div>
</header>