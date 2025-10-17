<?php
require_once __DIR__ . '/db.php';
$pdo = db();

// Búsqueda
$q = trim($_GET['q'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$precio_maximo = (int) ($_GET['max'] ?? 0);
$precio_minimo = (int) ($_GET['min'] ?? 0);

$whereParts = [];
$params = [];

// Búsqueda por Nombre
if ($q !== '') {
  $whereParts[] = 'p.name LIKE :name';
  $params[':name'] = "%$q%";
}

// Búsqueda por categorias
if ($category > 0) {
  $whereParts[] = 'p.category_id = :category';
  $params[':category'] = "$category";
}

// Búsqueda por Precio Maximo
if ($precio_maximo > 0) {
  $whereParts[] = 'p.price <= :pmax';
  $params[':pmax'] = "$precio_maximo";
}

// Búsqueda por Precio Minimo
if ($precio_minimo > 0) {
  $whereParts[] = 'p.price >= :pmin';
  $params[':pmin'] = "$precio_minimo";
}

// Combinar CONDICIONES si hay alguna
$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';





// Paginación
$perPage = 16;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Consulta para contar el total de productos
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$pages = ceil($total / $perPage);










// Preparar la consulta principal (obtener solo la primera imagen)
$query = "
SELECT p.id, p.name, p.price, c.name as category_name, 
                 SUBSTRING_INDEX(GROUP_CONCAT(pi.image_url), ',', 1) as first_image
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN product_images pi ON p.id = pi.product_id
          $where
          GROUP BY p.id
          ORDER BY p.fecha_creacion DESC, p.id DESC
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

// Vincular valores numericos de paginacion
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

//    VINCULAR FILTROS DINÁMICOS 
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $paramType);
}

// Ejecutar la consulta
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);



//xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

include __DIR__ . '/partials/header.php';
?>

<!-- AQUI ESTABA STYLE -->

<h1 class="mb-3 text-center">Catálogo de productos</h1>
<hr>
<div class="row">

  <!-- 🧭 BARRA LATERAL IZQUIERDA lateral izquierda -->
  <div class="col-md-2 mb-4">
    <form method="get" class="mb-3">
  <div class="input-group">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Buscar productos...">
    <button class="btn btn-outline-secondary">Buscar</button>
  </div>
</form>
  <!-- FILTRO POR CATEGORIA -->
  <div class="mb-3">
    <label for="categorySelect" class="form-label">Categorías</label>
    <select id="categorySelect" class="form-select" onchange="location = this.value;">
        <option value="">-- Todas las categorías --</option>
        <?php
            $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cats as $cat):
        ?>
            <option value="?category=<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<!-- FILTRO POR PRECIO MAXIMO y MINIMO -->
    <div class="mt-4 p-3 bg-light rounded shadow-sm">
      <h6 class="text-uppercase fw-bold mb-2">Filtros</h6>
      <form method="get">
        <label class="form-label small">Precio máximo:</label>
        <input type="number" step="0.01" min="0" name="max" class="form-control mb-2" placeholder="Ej: 100">
        <button class="btn btn-sm btn-outline-primary w-100">Aplicar</button>
      </form>
      <form method="get">
        <label class="form-label small">Precio mínimo:</label>
        <input type="number" step="0.01" min="0" name="min" class="form-control mb-2" placeholder="Ej: 200">
        <button class="btn btn-sm btn-outline-primary w-100">Aplicar</button>
      </form>
    </div>

  </div>

  <!-- 🛍️ Contenido principal -->
  <div class="col-md-10">
    <div class="row">
      <?php foreach ($products as $p): ?>
        <div class="col-md-3 mb-3">  <!-- AQUI SE DETERMINA LA CANTIDAD DE CUADRILLAS QUE OCUPAN CADA PRODUCTO -->
          <div class="card h-100">


            <?php if (!empty($p['first_image'])): ?>
              <a href="/product_detail.php?id=<?= $p['id'] ?>" class="card-img-top-link">
                <img src="<?= htmlspecialchars(trim($p['first_image'])) ?>" class="card-img-top" loading="lazy" alt="Producto <?= htmlspecialchars($p['name']) ?>">
              </a>
            <?php else: ?>
              <a href="/product_detail.php?id=<?= $p['id'] ?>" class="card-img-top-link">
                <img src="/img/default.webp" class="card-img-top" alt="Sin imagen" loading="lazy">
              </a>
            <?php endif; ?>





            
            <div class="card-body">
              <h5 class="card-title">
                <a href="/product_detail.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a>
              </h5>
              <p><b>Categoría:</b> <?= htmlspecialchars($p['category_name'] ?? 'Sin categoría') ?></p>
              <p class="card-text">$<?= number_format((float)$p['price'], 2) ?></p>
              <button class="btn btn-primary btn-sm add-to-cart"
                      data-id="<?= $p['id'] ?>"
                      data-name="<?= htmlspecialchars($p['name']) ?>"
                      data-price="<?= $p['price'] ?>">
                Añadir al carrito
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Paginador -->
    <nav>
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
</div>

<script>
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', () => {
        // Agregar clase de animación
        button.classList.add('animate');
        setTimeout(() => {
            button.classList.remove('animate');
        }, 800); // Duración de la animación

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













