<?php
$token = "8201020051:AAGZbW-_cEk_utKS4vfsCRjsDsXEA1dUYCI";
 $chat_id = "1582052556";



require_once __DIR__ . '/db.php';
$pdo = db(); // Si usas base de datos para otros procesos, mantenlo

// 🔹 Función: Convertir HTML a texto legible
function htmlToText($html) {
    // Initialize output
    $output = "";

    // Try parsing HTML with DOMDocument for structured table handling
    $doc = new DOMDocument();
    // Suppress warnings for malformed HTML and ensure UTF-8
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $tables = $doc->getElementsByTagName('table');

    if ($tables->length > 0) {
        $rows = $tables->item(0)->getElementsByTagName('tr');
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            // Check for "Total" row (colspan or contains "Total:")
            if ($cells->length === 1 || stripos($row->textContent, 'Total:') !== false) {
                $total = trim($row->textContent);
                $total = preg_replace('/\s+/', ' ', html_entity_decode($total, ENT_QUOTES, 'UTF-8'));
                $output .= "💰 $total\n";
                continue;
            }
            // Normal item row: expect 3 cells (name, quantity x price, subtotal)
            if ($cells->length >= 3) {
                $name = trim($cells->item(0)->textContent);
                $quantity_price = trim($cells->item(1)->textContent);
                $subtotal = trim($cells->item(2)->textContent);
                // Decode HTML entities and clean whitespace
                $name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
                $quantity_price = html_entity_decode($quantity_price, ENT_QUOTES, 'UTF-8');
                $subtotal = html_entity_decode($subtotal, ENT_QUOTES, 'UTF-8');
                $output .= "- $name: $quantity_price $subtotal\n";
            }
        }
    }

    // Append any non-table text (e.g., <p>Gracias por su compra!</p>)
    $paragraphs = $doc->getElementsByTagName('p');
    foreach ($paragraphs as $p) {
        $text = trim($p->textContent);
        if ($text) {
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $output .= "$text\n";
        }
    }

    // Fallback: if no table found, strip tags and clean as before
    if ($tables->length === 0) {
        $reemplazos = [
            '@<br\s*/?>@i' => "\n",
            '@</tr>@i'     => "\n",
            '@</p>@i'      => "\n",
            '@</div>@i'    => "\n",
            '@</li>@i'     => "\n",
            '@<ul>@i'      => "\n",
            '@<ol>@i'      => "\n",
            '@</table>@i'  => "\n"
        ];
        $text = preg_replace(array_keys($reemplazos), array_values($reemplazos), $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $output .= trim($text) . "\n";
    }

    // Final cleanup: ensure single newlines and trim
    $output = preg_replace('/\n\s*\n+/', "\n", $output);
    return trim($output);
}

// 🔹 Función: Enviar mensaje por Telegram (sin Markdown)
function enviarTelegram($mensaje, $token, $chat_id) {
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text'    => $mensaje,
        // 'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("❌ Error Telegram: $response");
        return false;
    }
    return true;
}

// 🔹 Si se envió el formulario, genera el resumen y notifica por T  E  L  E  G  R  A  M
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    //VALIDACION EN reCAPTCHA
    $secretKey = "6Lc-5-wrAAAAAB8n6m4UgetLuWtoCuN0m35FBJsK";
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';

if (empty($captchaResponse)) {
    echo "<p style='color:red;'>Por favor, confirma que no eres un robot.</p>";
    exit;
}

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captchaResponse}");
    $responseData = json_decode($verify);

if (!$responseData->success) {
    echo "<p style='color:red;'>Error al verificar el reCAPTCHA. Intenta nuevamente.</p>";
    exit;
}













    // 🧩 Recibir y limpiar datos del formulario
    $nombreCliente   = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $telefonoCliente = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $correoCliente   = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $cartData        = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];

    $errores = [];

    // 🔹 Validación del nombre
    if (empty($nombreCliente)) {
        $errores[] = "El nombre del cliente es obligatorio.";
    } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{3,}$/', $nombreCliente)) {
        $errores[] = "El nombre solo puede contener letras y espacios, y debe tener al menos 3 caracteres.";
    }

    // 🔹 Validación del teléfono
    if (empty($telefonoCliente)) {
        $errores[] = "El número de celular es obligatorio.";
    } elseif (!preg_match('/^[0-9]{8,12}$/', $telefonoCliente)) {
        $errores[] = "El número de celular debe tener entre 8 y 12 dígitos.";
    }

    // 🔹 Validación del correo (solo si no está vacío)
    if (!empty($correoCliente) && !filter_var($correoCliente, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico ingresado no es válido.";
    }

    // 🔹 Validación del carrito
    if (empty($cartData) || !is_array($cartData) || count($cartData) === 0) {
        $errores[] = "El carrito está vacío o los datos son inválidos.";
    }

    // 🔹 Si hay errores, mostrar mensaje y detener ejecución
    if (!empty($errores)) {
        echo "<div style='padding:20px; background:#ffe5e5; border:1px solid #ff0000; border-radius:8px; margin:20px;'>";
        echo "<h4>❌ Se encontraron los siguientes errores:</h4><ul>";
        foreach ($errores as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul><a href='/index.php' style='color:#007bff;'>Volver a la tienda</a></div>";
        exit;
    }

    // ✅ Si todo está correcto, procesar el pedido

    // Aquí podrías guardar en BD o enviar por correo/WhatsApp

if (!empty($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);

    $nombreCliente   = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $telefonoCliente = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $correoCliente   = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';

    if (!empty($cart)) {

        // Calcular total
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Generar ID único para el pedido
        $order_id = uniqid('order_', true);

        // Guardar pedido en la BASE DE DATOS
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders (id, total, payment_method, status) VALUES (:id, :total, :payment_method, :status)');
            $stmt->execute([
                ':id' => $order_id,
                ':total' => $total,
                ':payment_method' => 'cash_on_delivery',
                ':status' => 'pending'
            ]);

            foreach ($cart as $item) {
                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)');
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['id'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price']
                ]);
            }
            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            ob_end_clean();
            error_log('Error al guardar el pedido: ' . $e->getMessage());
            die('Error al procesar el pedido. Por favor, intenta de nuevo.');
        }

$total = 0;



            $html0 = 'Comprobante de Pedido\n';
            $html0 .= 'ID del Pedido: ' . htmlspecialchars($order_id) . '\n';
            $html0 .= 'Fecha: ' . date('d/m/Y H:i') . '\n';
            $html0 .= 'Método de Pago: Pago contra entrega\n';
            $html0 .= 'Estado: Pendiente\n';
            $html0 .= 'Detalles del Pedido\n';
            $html0 .= 'Id  Producto  Cantidad  Precio Unitario  Total\n';


            $html = '<table border="1" cellpadding="4"><tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Total</th></tr>';

        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            $html .= "<tr><td>{$item['id']}</td><td>{$item['name']}</td><td>{$item['quantity']} x \${$item['price']}</td><td>= $" . number_format($subtotal, 2) . "</td></tr>";
        }

            $html .= '</table>';
            $html2 = 'Total:...  ' . number_format($total, 2) . '\n Gracias por su compra2! ';
            $htmlCliente= '\nDATOS DEL CLIENTE\n Nombre: '. $nombreCliente.'\nCelular: '. $telefonoCliente.'\nEmail: '. $correoCliente;

        // Token y chat_id de tu bot de Telegram

        $mensaje=$html0;
        $mensaje= $mensaje ."\n". htmlToText($html);
        $mensaje= $mensaje ."\n". $html2.$htmlCliente;
        $mensaje = str_replace("\\n", "\n", $mensaje);
        //enviarTelegram("Linea1\nLinea2\nLinea3", $token, $chat_id);
        if (enviarTelegram($mensaje, $token, $chat_id)) {
        //echo "✅ Mensaje enviado correctamente a Telegram.";
        } else {
            //echo "❌ Error al enviar mensaje. Revisa el log.";
            }

    }
}
}


include __DIR__ . '/partials/header.php';
?>

<div class="container my-4">
    <h1 class="mb-4">Carrito de Compras</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            <div id="cart-content">
                <table class="table table-bordered" id="cart-table" style="display: none;">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items"></tbody>
                </table>

                <div id="empty-cart-message" style="display: none; text-align: center; padding: 20px;">
                    <h4>No tiene productos añadidos</h4>
                    <a href="/index.php" class="btn btn-outline-secondary">Volver a la tienda</a>
                </div>
            </div>

            <h4 id="cart-total" style="display: none;">Total: $0.00</h4>

<form id="checkout-form" method="post" action="/cart.php">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="cart_data" id="cart-data">

    <div class="mb-3">
        <label for="customer_name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="customer_name" name="customer_name" required placeholder="Ej. Juan Pérez">
    </div>

    <div class="mb-3">
        <label for="customer_phone" class="form-label">Número de celular <span class="text-danger">*</span></label>
        <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required placeholder="Ej. 71234567" pattern="[0-9]{8,12}" title="Introduce un número válido">
    </div>

    <div class="mb-3">
        <label for="customer_email" class="form-label">Correo electrónico (opcional)</label>
        <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="Ej. correo@ejemplo.com">
    </div>
    <!-- SITIO DE RECAPTCHA -->
    <div class="mb-3 g-recaptcha" data-sitekey="6Lc-5-wrAAAAADaJUNm_TPyTTWP9kfMCHIgEmlHm"></div>

    <button type="button" id="checkout-button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal" style="display: none;">Finalizar Compra</button>
    <a href="/index.php" class="btn btn-outline-secondary">Seguir comprando</a>
</form>

        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas finalizar la compra?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="confirm-checkout" class="btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de Notificación -->
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="toast-header">
            <strong class="me-auto">Compra Finalizada</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">¡Tu pedido ha sido procesado con éxito!</div>
    </div>
</div>






<script>
// 🔹 Actualiza el contenido del carrito en pantalla
function updateCartDisplay() {
    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    const tbody = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const cartTable = document.getElementById('cart-table');
    const emptyMsg = document.getElementById('empty-cart-message');
    const checkoutBtn = document.getElementById('checkout-button');

    tbody.innerHTML = '';
    let total = 0;

    if (Object.keys(cart).length === 0) {
        cartTable.style.display = 'none';
        emptyMsg.style.display = 'block';
        totalEl.style.display = 'none';
        checkoutBtn.style.display = 'none';
        document.getElementById('cart-data').value = JSON.stringify(cart);
        return;
    }

    cartTable.style.display = 'table';
    emptyMsg.style.display = 'none';
    totalEl.style.display = 'block';
    checkoutBtn.style.display = 'inline-block';

    for (let id in cart) {
        const item = cart[id];
        const subtotal = item.price * item.quantity;
        total += subtotal;
        tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>${item.price.toFixed(2)}</td>
                <td><input type="number" class="form-control quantity-input" data-id="${id}" value="${item.quantity}" min="1"></td>
                <td>${subtotal.toFixed(2)}</td>
                <td><button class="btn btn-danger btn-sm remove-item" data-id="${id}">Eliminar</button></td>
            </tr>`;
    }

    totalEl.textContent = `Total: $${total.toFixed(2)}`;
    document.getElementById('cart-data').value = JSON.stringify(cart);
}

// 🔹 Eventos de actualización y eliminación de productos
document.addEventListener('input', e => {
    if (e.target.classList.contains('quantity-input')) {
        const id = e.target.dataset.id;
        const quantity = parseInt(e.target.value);
        if (quantity >= 1) {
            let cart = JSON.parse(localStorage.getItem('cart') || '{}');
            cart[id].quantity = quantity;
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
        }
    }
});

document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-item')) {
        const id = e.target.dataset.id;
        let cart = JSON.parse(localStorage.getItem('cart') || '{}');
        delete cart[id];
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartDisplay();
    }
});


// 🔹 Validación personalizada de los campos
function validarFormulario() {
    const name = document.getElementById('customer_name').value.trim();
    const phone = document.getElementById('customer_phone').value.trim();
    const email = document.getElementById('customer_email').value.trim();

    const nameRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{3,}$/;
    const phoneRegex = /^[0-9]{8,12}$/;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!nameRegex.test(name)) {
        alert("⚠️ El nombre debe tener al menos 3 letras y solo puede contener caracteres alfabéticos y espacios.");
        return false;
    }

    if (!phoneRegex.test(phone)) {
        alert("⚠️ El número de celular debe tener entre 8 y 12 dígitos numéricos.");
        return false;
    }

    if (email && !emailRegex.test(email)) {
        alert("⚠️ Ingresa un correo electrónico válido o deja el campo vacío.");
        return false;
    }
    // 🧩 Validación del reCAPTCHA (IMPORTANTE)
    const response = grecaptcha.getResponse();
    if (response.length === 0) {
        alert("⚠️ Por favor, confirma que no eres un robot antes de enviar el formulario.");
        return false;
    }

    return true; // ✅ Si todo está correcto
}
// 🔹 Validar antes de mostrar el modal
document.getElementById('checkout-button').addEventListener('click', (e) => {
    const form = document.getElementById('checkout-form');
    if (!validarFormulario() || !form.checkValidity()) {
        e.preventDefault();
        return;
    }
});

// 🔹 Confirmar compra y enviar datos
document.getElementById('confirm-checkout').addEventListener('click', () => {
    const form = document.getElementById('checkout-form');

    // Validación combinada (personalizada + HTML5)
    if (!validarFormulario() || !form.checkValidity()) {
        return;
    }

    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    if (Object.keys(cart).length === 0) {
        const toast = new bootstrap.Toast(document.querySelector('.toast'));
        document.querySelector('.toast-body').textContent = 'El carrito está vacío.';
        toast.show();
        return;
    }

    // Cierra el modal y limpia el carrito
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
    modal.hide();

    document.getElementById('cart-data').value = JSON.stringify(cart);
    localStorage.removeItem('cart');

    // Notificación
    const toast = new bootstrap.Toast(document.querySelector('.toast'));
    document.querySelector('.toast-body').textContent = '¡Tu pedido ha sido procesado con éxito!';
    toast.show();

    // Enviar formulario
    form.submit();

    // Redirigir tras un segundo
    setTimeout(() => window.location.href = '/index.php', 1000);
});

// 🔹 Inicializar vista del carrito
updateCartDisplay();
</script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?php include __DIR__ . '/partials/footer.php'; ?>
