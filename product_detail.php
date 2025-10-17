<?php
require_once __DIR__ . '/db.php';
$pdo = db();

// Obtener ID del producto
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /index.php');
    exit;
}

// Incrementar visitas
$stmt = $pdo->prepare('UPDATE products SET visitas = visitas + 1 WHERE id = :id');
$stmt->execute([':id' => $id]);

// Obtener datos del producto
$stmt = $pdo->prepare('SELECT p.*, c.name as category_name, GROUP_CONCAT(pi.image_url) as images
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       LEFT JOIN product_images pi ON p.id = pi.product_id
                       WHERE p.id = :id
                       GROUP BY p.id');
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: /index.php');
    exit;
}

include __DIR__ . '/partials/header.php';
?>


<div class="container my-4">
    <h1 class="mb-4"><?= htmlspecialchars($product['name']) ?></h1>
    <div class="row">
        <div class="col-md-7">
            <?php
            $images = !empty($product['images']) ? explode(',', $product['images']) : [];
            if (!empty($images)):
            ?>
                <div id="carousel-<?= $product['id'] ?>" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-indicators">
                        <?php foreach ($images as $index => $image): ?>
                            <button type="button" data-bs-target="#carousel-<?= $product['id'] ?>" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">

                            <!-- SE HACE LA MODIFICACION -->

                            <!-- Convertir: $image=/uploads/img.webp a $image2=/uploads/medium_img.webp -->
                            <!-- // Descomponer la ruta -->
                            <?php 
                            $info = pathinfo($image);
                            // Crear la nueva ruta con prefijo "medium_"
                            $image2 = $info['dirname'] . '/medium_' . $info['filename'] . '.' . $info['extension'];
                            ?>

                                <img src="<?= htmlspecialchars(trim($image2)) ?>" class="d-block w-100" alt="Producto <?= htmlspecialchars($product['name']) ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $product['id'] ?>" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $product['id'] ?>" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <img src="/img/default.webp" class="d-block w-100" alt="Producto" loading="lazy">
                
            <?php endif; ?>
        </div>
        <div class="col-md-5">
            <div class="card shadow-sm p-4">
                <div class="product-details">
                    <p><b>Marca:</b> <?= htmlspecialchars($product['marca'] ?? '') ?></p>
                    <p><b>Categoría:</b> <?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?></p>
                    <p><b>Precio:</b> $<?= number_format((float)$product['price'], 2) ?></p>
                    <p><b>Descripción corta:</b> <?= htmlspecialchars($product['descripcion_corta'] ?? '') ?></p>
                    <p><b>Descripción:</b> <?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
                    <p><b>Tags:</b> <?= htmlspecialchars($product['tags'] ?? '') ?></p>
                    <p><b>Visitas:</b> <?= $product['visitas'] ?></p>
                    <p><b>Fecha de creación:</b> <?= date('d/m/Y H:i', strtotime($product['fecha_creacion'])) ?></p>
                    <p><b>Última actualización:</b> <?= date('d/m/Y H:i', strtotime($product['fecha_actualizacion'])) ?></p>
                    <button class="btn btn-primary mt-3 add-to-cart" data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= $product['price'] ?>">Añadir al carrito</button>
                    <a href="/index.php" class="btn btn-outline-secondary mt-3">Volver al catálogo</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', () => {
        // Agregar clase de animación
        button.classList.add('animate');
        setTimeout(() => {
            button.classList.remove('animate');
        }, 300); // Duración de la animación

        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const price = parseFloat(button.getAttribute('data-price'));

        let cart = JSON.parse(localStorage.getItem('cart') || '{}');
        if (cart[id]) {
            cart[id].quantity += 1;
        } else {
            cart[id] = { id, name, price, quantity: 1 };
        }
        localStorage.setItem('cart', JSON.stringify(cart));
    });
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>