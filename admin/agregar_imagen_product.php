<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/middleware.php'; // contiene la función db()
$pdo = db();

$id = (int)($_GET['id'] ?? 0); // ID del producto al que se agregan imágenes
$errors = [];
$success = false;

if ($id <= 0) {
    die("❌ ID de producto no válido.");
}

// Crear carpeta de subida si no existe
$uploadDir = __DIR__ . '/../';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}




// --- Redimension de imagenes ---
function resizeImageGD($sourcePath, $destPath, $newWidth, $newHeight, $quality = 85) {
    $info = getimagesize($sourcePath);
    $mime = $info['mime'];

    // Crear imagen desde el tipo MIME
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $srcImg = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImg = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $srcImg = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $srcImg = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false; // Tipo no soportado
    }

    // Crear lienzo para la nueva imagen
    $dstImg = imagecreatetruecolor($newWidth, $newHeight);

    // Mantener transparencia para PNG y GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    // Redimensionar
    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($srcImg), imagesy($srcImg));

    // Guardar imagen
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            imagejpeg($dstImg, $destPath, $quality);
            break;
        case 'image/png':
            imagepng($dstImg, $destPath);
            break;
        case 'image/gif':
            imagegif($dstImg, $destPath);
            break;
        case 'image/webp':
            imagewebp($dstImg, $destPath, $quality);
            break;
    }

    // Liberar memoria
    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return true;
}







// 🧹 FUNCIÓN PARA ELIMINAR IMÁGENES
//
if (isset($_POST['delete_image_id'])) {
    $deleteId = (int)$_POST['delete_image_id'];

    try {
        // Buscar imagen
        $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = :id AND product_id = :pid");
        $stmt->execute([':id' => $deleteId, ':pid' => $id]);
        $image = $stmt->fetch();

        if ($image) {



            $filePath = $uploadDir . ltrim($image['image_url'], '/');
            if (is_file($filePath)) {
                unlink($filePath); // eliminar archivo físico
            }

            $info = pathinfo($image['image_url']);
            // Crear la nueva ruta con prefijo "medium_"
            $image2 = $info['dirname'] . '/medium_' . $info['filename'] . '.' . $info['extension'];

            $filePathMedium = $uploadDir . ltrim($image2, '/');
            if (is_file($filePathMedium)) {
                unlink($filePathMedium); // eliminar archivo físico
            }


            // Eliminar de la base de datos
            $delStmt = $pdo->prepare("DELETE FROM product_images WHERE id = :id");
            $delStmt->execute([':id' => $deleteId]);

            $success = true;
        } else {
            $errors[] = "Imagen no encontrada o no pertenece a este producto.";
        }
    } catch (PDOException $e) {
        $errors[] = "Error al eliminar: " . $e->getMessage();
    }
}



$uploadDir = __DIR__ . '/../';
// --- Manejo del formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maxImages = 5;
    $allowedTypes = ['image/jpeg', 'image/jpg','image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 3MB

    $uploadedFiles = $_FILES['images'] ?? null;

    if (!$uploadedFiles || empty($uploadedFiles['name'][0])) {
        $errors[] = "Debes seleccionar al menos una imagen.";
    } else {
        // Contar imágenes ya existentes
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = :id");
        $countStmt->execute([':id' => $id]);
        $existingCount = (int)$countStmt->fetchColumn();
        $total = $existingCount + count($uploadedFiles['name']);
        if ($total > $maxImages) {
            $errors[] = "No puedes subir más de $maxImages imágenes en total (ya tienes $existingCount).";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            foreach ($uploadedFiles['tmp_name'] as $i => $tmpName) {
                if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_OK) continue;

                $type = mime_content_type($tmpName);
                $size = $uploadedFiles['size'][$i];

                if (!in_array($type, $allowedTypes)) {
                    $errors[] = "El archivo {$uploadedFiles['name'][$i]} no es un tipo válido (JPEG, PNG, GIF, WEBP).";
                    continue;
                }
                if ($size > $maxSize) {
                    $errors[] = "El archivo {$uploadedFiles['name'][$i]} excede los 5MB.";
                    continue;
                }

                // Generar nombre único
                $ext = pathinfo($uploadedFiles['name'][$i], PATHINFO_EXTENSION);//Obtiene la extension
                $filename1 = uniqid('img_') . '.' . strtolower($ext);// genera un numero agregado a img_
                $filename ='/uploads/' . $filename1;
                $destination = rtrim($uploadDir, '/') . $filename;
                
                //MOVER ARCHIVO SUBIDO

                if (move_uploaded_file($tmpName, $destination)) {

                    // BASE DEL NOMBRE SIN EXTENSION
                    $baseName= pathinfo($filename1, PATHINFO_FILENAME);

                    // Crear versiones SMALL y MEDIUM
                    $dir = dirname($destination);

                    //$smallPath  = $dir . '/small_'  . $baseName . '.' . $ext;
                    $smallPath  = $dir . '/'  . $baseName . '.' . $ext;
                    $mediumPath = $dir . '/medium_' . $baseName . '.' . $ext;

                    // Redimensionar con GD. Se ve que el Orden importa.
                    //resizeImageGD($destination, $mediumPath, 800, 600);
                    resizeImageGD($destination, $mediumPath, 800, 600);
                    //resizeImageGD($destination, $mediumPath, 800, 600);
                    resizeImageGD($destination, $smallPath, 400, 300);

                    // Guardar ruta original (la principal) en DB
                    $stmt = $pdo->prepare("
                        INSERT INTO product_images (product_id, image_url)
                        VALUES (:pid, :fn)
                    ");
                    $stmt->execute([':pid' => (int)$id, ':fn' => $filename]);
                    
                } else {
                    echo "Error al mover el archivo subido.";
                }
            }
            if (empty($errors)) {
                $pdo->commit();
                $success = true;
            } else {
                $pdo->rollBack();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// --- Obtener imágenes existentes ---
$stmt = $pdo->prepare("SELECT id, image_url FROM product_images WHERE product_id = :id");
$stmt->execute([':id' => $id]);
$images = $stmt->fetchAll();

// Obtener el nombre

if (isset($id) && is_numeric($id)) {
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $nombreProducto = $stmt->fetchColumn() ?: "Producto no encontrado";
} else {
    $nombreProducto = "ID inválido";
}



?>



<?php include __DIR__ . '/../partials/header.php'; ?>

<h1 class="mb-3">Agregar imágenes al producto:  </h1>
<h3 class="mb-3"><?= htmlspecialchars($nombreProducto) ?></h3>

<?php if ($success): ?>
    <div class="alert alert-success">✅ Imágenes subidas correctamente.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <div class="mb-3">
        <label class="form-label">Seleccionar imágenes (máx. 5 en total)</label>
        <input type="file" name="images[]" multiple accept="image/*" class="form-control">
    </div>

    <div class="d-grid">
        <button class="btn btn-primary">Subir imágenes</button>
    </div>
</form>
<hr>

<h3>📷 Imágenes actuales</h3>
<div style="display:flex; flex-wrap:wrap; gap:10px;">
    <?php foreach ($images as $img): ?>
        <div style="border:1px solid #ccc; padding:5px; text-align:center;">
            <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="" width="120"><br>
            <form method="post" style="margin-top:5px;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="delete_image_id" value="<?= $img['id'] ?>">
                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta imagen?')">
                    🗑️ Eliminar
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>



<hr>
<ul>
  <li><a href="/admin/products.php">Administrar productos</a></li>
  <li><a href="/admin/orders.php">Administrar Pedidos</a></li>
</ul>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
