<?php
require __DIR__ . '/middleware.php';
$pdo = db(); // si tu db.php define una función db()





if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: categories.php"); // recargar para limpiar la URL
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>


<?php include __DIR__ . '/../partials/header.php'; ?>

<h1 class="mb-4">Categorías</h1>
<div class="card p-4 mb-4">
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <label class="form-label">Nueva categoría</label>
      <input type="text" class="form-control" name="name" required>
    </div>
    <button type="submit" class="btn btn-success">Añadir</button>
  </form>
</div>

<table class="table table-bordered">
  <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
  <tbody>
    <?php foreach ($categories as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td>
          <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar categoría?')">Eliminar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../partials/footer.php'; ?>