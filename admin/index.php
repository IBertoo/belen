<?php
require_once __DIR__ . '/middleware.php';
include __DIR__ . '/../partials/header.php';
?>
<h1 class="mb-3">Panel de administración</h1>
<ul>
  <li><a href="/admin/products.php">Administrar productos</a></li>
  <li><a href="/admin/orders.php">Administrar Pedidos</a></li>
  <li><a href="/admin/categories.php">Administrar Categorías</a></li>
</ul>
<?php include __DIR__ . '/../partials/footer.php'; ?>