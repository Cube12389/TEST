<?php
include '../header.php';
include __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $price = floatval($_POST['price']);
    $category_input = trim($_POST['category_id'] ?? '');
    $type = $conn->real_escape_string($_POST['type'] ?? 'normal');
    $available = isset($_POST['available']) ? 1 : 0;
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $image = '';

    // ✅ 处理图片上传
    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetDir = dirname(__DIR__) . '/ảnh/foods/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imageName);
        $image = 'foods/' . $imageName; // Lưu tên file ảnh
    } else {
        $image = $conn->real_escape_string($_POST['image'] ?? '');
    }

    // ✅ 检查分类
    if ($category_input === '') {
        $category_value = 'NULL';
    } else {
        $cid = intval($category_input);
        $check = $conn->query("SELECT id FROM categories WHERE id=$cid LIMIT 1");
        if (!$check || $check->num_rows == 0) {
            echo "<script>alert('分类ID不存在，请检查。');history.back();</script>";
            exit;
        }
        $category_value = $cid;
    }

    // ✅ SQL语句与foods表匹配
    $sql = "INSERT INTO foods (category_id,name,description,price,image,available,type)
            VALUES ($category_value,'$name','$description',$price,'$image',$available,'$type')";
    $conn->query($sql);
    echo "<script>alert('✅ 添加菜品成功!');window.location='admin_products.php';</script>";
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $r = $conn->query("SELECT * FROM foods WHERE id=$id")->fetch_assoc();
    if ($r && $r['image']) {
        @unlink(dirname(__DIR__) . '/ảnh/' . $r['image']);
    }
    $conn->query("DELETE FROM foods WHERE id=$id");
    header("Location: admin_products.php");
    exit;
}

$products = $conn->query("SELECT * FROM foods ORDER BY created_at DESC");
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>产品管理</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php include __DIR__ . '/admin_header_small.php'; ?>
<div class="page-title">🍜 产品管理</div>
<div class="akd-card">
  <div class="akd-card-title">➕ 添加产品</div>
  <div class="akd-panel">
    <form method="post" enctype="multipart/form-data" class="form-grid">
      <input name="name" placeholder="菜品名称" required>
      <input name="price" type="number" placeholder="价格 (越南盾)" required>
      <select name="category_id">
        <option value="">-- 选择分类 (可选) --</option>
        <?php while($c = $categories->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endwhile; ?>
      </select>
      <select name="type">
        <option value="normal">normal</option>
        <option value="new">new</option>
        <option value="bestseller">bestseller</option>
      </select>
      <label><input type="checkbox" name="available" checked> 在售</label>
      <input type="file" name="image" accept="image/*">
      <textarea name="description" placeholder="描述"></textarea>
      <button name="add_product" class="akd-btn akd-btn-primary">➕ 添加菜品</button>
    </form>
  </div>

  <div class="akd-panel" style="margin-top:18px">
    <div class="small center">产品列表</div>
    <div class="table-wrap">
      <table class="styled-table">
        <thead>
          <tr>
            <th>图片</th><th>名称</th><th>价格</th><th>类型</th><th>状态</th><th>日期</th><th>操作</th>
          </tr>
        </thead>
        <tbody>
        <?php while($p = $products->fetch_assoc()): ?>
          <tr>
            <td>
              <?php if($p['image']): ?>
                <img src="../ảnh/<?= htmlspecialchars($p['image']) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:8px">
              <?php else: ?>
                <i>无图片</i>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= number_format($p['price'],0,',','.') ?>đ</td>
            <td><?= htmlspecialchars($p['type']) ?></td>
            <td><?= $p['available'] ? '✅' : '❌' ?></td>
            <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td>
              <a class="akd-btn akd-btn-edit" href="admin_products.php?edit=<?= $p['id'] ?>">编辑</a>
              <a class="akd-btn akd-btn-delete" href="?delete=<?= $p['id'] ?>" onclick="return confirm('删除此菜品?')">删除</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
