<?php
require_once __DIR__ . '/middleware.php';
$pdo = db();
$search = trim($_GET['search'] ?? '');
$uploadDir = __DIR__ . '/../';




// Borrar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $productId = (int)$_POST['delete_id'];

    // 1️⃣ Obtener las imágenes del producto
    $stmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = :id');
    $stmt->execute([':id' => $productId]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
// 1️⃣ Eliminar order_items asociados
$stmt = $pdo->prepare('DELETE FROM order_items WHERE product_id = :id');
$stmt->execute([':id' => $productId]);
    // 2️⃣ Eliminar los archivos físicos
    foreach ($imagenes as $image) {


        $filePath = $uploadDir . ltrim($image, '/');
        if (is_file($filePath)) {
            unlink($filePath);
        }

        $info = pathinfo($image);
            // Crear la nueva ruta con prefijo "medium_"
            $image2 = $info['dirname'] . '/medium_' . $info['filename'] . '.' . $info['extension'];

            $filePathMedium = $uploadDir . ltrim($image2, '/');
            if (is_file($filePathMedium)) {
                unlink($filePathMedium); // eliminar archivo físico
            }


    }

    // 3️⃣ Borrar los registros de imágenes
    $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = :id');
    $stmt->execute([':id' => $productId]);

    // 4️⃣ Borrar el producto
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);

    // 5️⃣ Redirigir
    header('Location: /admin/products.php');
    exit;
}




// Paginación
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Consulta para contar el total de productos
$where = $search ? 'WHERE p.name LIKE :search' : '';
$params = $search ? [':search' => "%$search%"] : [];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$pages = ceil($total / $perPage);

// Consulta principal
$query = "SELECT p.*, c.name as category_name, GROUP_CONCAT(pi.image_url) as images
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN product_images pi ON p.id = pi.product_id
          $where
          GROUP BY p.id
          ORDER BY p.fecha_creacion DESC, p.id DESC
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


include __DIR__ . '/../partials/header.php';
?>
<h1 class="mb-3">Productos</h1>
<a href="/admin/product_form.php" class="btn btn-success mb-3">+ Nuevo producto</a>
<form method="get" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Buscar productos...">
        <button class="btn btn-outline-secondary">Buscar</button>
    </div>
</form>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Categoría</th>
            <th>Imágenes</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td>$<?= number_format((float)$p['price'], 2) ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? 'Sin categoría') ?></td>
                <td>
                    <?php
                    $images = !empty($p['images']) ? explode(',', $p['images']) : [];
                    if (!empty($images)):
                    ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($images as $image): ?>
                                <img src="<?= htmlspecialchars($image) ?>" alt="img" style="width:60px;height:auto;">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span>Sin imágenes</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/admin/agregar_imagen_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">ActualizarFoto</a>
                    <a href="/admin/product_formEditar.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                    

                    <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este producto?');">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Paginador -->
<nav>
    <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>

<?php include __DIR__ . '/../partials/footer.php'; ?>