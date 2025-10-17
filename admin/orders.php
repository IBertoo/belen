<?php

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF via Composer
use \TCPDF;

$pdo = db();

// Manejar actualización de pedidos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $items = json_decode($_POST['items_data'], true);

    // Verificar que el pedido esté en estado 'pending'
    $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id');
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($order['status'] !== 'pending') {
        die('Solo se pueden editar pedidos en estado pendiente.');
    }

    // Calcular total
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Actualizar pedido en la base de datos
    $pdo->beginTransaction();
    try {
        // Actualizar total del pedido
        $stmt = $pdo->prepare('UPDATE orders SET total = :total WHERE id = :id');
        $stmt->execute([':total' => $total, ':id' => $order_id]);

        // Eliminar items existentes
        $stmt = $pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id');
        $stmt->execute([':order_id' => $order_id]);

        // Insertar items actualizados
        foreach ($items as $item) {
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)');
            $stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
        }
        $pdo->commit();

        // Generar PDF actualizado
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tu Empresa');
        $pdf->SetTitle('Comprobante de Pedido Actualizado');
        $pdf->SetHeaderData('', 0, 'Comprobante de Pedido Actualizado', "ID: $order_id\nFecha: " . date('d/m/Y H:i') . "\nEstado: Pendiente");
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        $html = '<h1>Comprobante de Pedido Actualizado</h1>';
        $html .= '<p><strong>ID del Pedido:</strong> ' . htmlspecialchars($order_id) . '</p>';
        $html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</p>';
        $html .= '<p><strong>Método de Pago:</strong> Pago contra entrega</p>';
        $html .= '<p><strong>Estado:</strong> Pendiente</p>';
        $html .= '<h3>Detalles del Pedido</h3>';
        $html .= '<table border="1" cellpadding="4"><tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Total</th></tr>';
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
            $html .= '<td>' . $item['quantity'] . '</td>';
            $html .= '<td>$' . number_format($item['price'], 2) . '</td>';
            $html .= '<td>$' . number_format($subtotal, 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '<tr><td colspan="3"><strong>Total</strong></td><td><strong>$' . number_format($total, 2) . '</strong></td></tr>';
        $html .= '</table>';
        $html .= '<p><strong>Gracias por su compra!</strong></p>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output("order_$order_id_updated.pdf", 'D');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die('Error al actualizar el pedido: ' . $e->getMessage());
    }
}

// Obtener todos los pedidos
$stmt = $pdo->query('SELECT o.*, GROUP_CONCAT(p.name, "|", oi.quantity, "|", oi.price) as items
                     FROM orders o
                     LEFT JOIN order_items oi ON o.id = oi.order_id
                     LEFT JOIN products p ON oi.product_id = p.id
                     GROUP BY o.id
                     ORDER BY o.created_at DESC');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../partials/header.php';
?>
<style>
    .table th, .table td {
        vertical-align: middle;
    }
    .quantity-input {
        width: 60px;
        display: inline-block;
    }
    .order-details {
        margin-top: 10px;
    }
</style>

<div class="container my-4">
    <h1 class="mb-4">Mis Pedidos</h1>
    <?php if (empty($orders)): ?>
        <p>No hay pedidos registrados.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5>Pedido #<?= htmlspecialchars($order['id']) ?> (<?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>)</h5>
                    <p><strong>Estado:</strong> <?= $order['status'] === 'pending' ? 'Pendiente' : 'Completado' ?></p>
                </div>
                <div class="card-body">
                    <div class="order-details">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Total</th>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="order-items-<?= htmlspecialchars($order['id']) ?>">
                                <?php
                                $items = !empty($order['items']) ? explode(',', $order['items']) : [];
                                $total = 0;
                                foreach ($items as $item) {
                                    list($name, $quantity, $price) = explode('|', $item);
                                    $subtotal = $quantity * $price;
                                    $total += $subtotal;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($name) ?></td>
                                        <td>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <input type="number" class="form-control quantity-input" data-order-id="<?= htmlspecialchars($order['id']) ?>" data-product-name="<?= htmlspecialchars($name) ?>" value="<?= $quantity ?>" min="1">
                                            <?php else: ?>
                                                <?= $quantity ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?= number_format($price, 2) ?></td>
                                        <td>$<?= number_format($subtotal, 2) ?></td>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <td>
                                                <button class="btn btn-danger btn-sm remove-order-item" data-order-id="<?= htmlspecialchars($order['id']) ?>" data-product-name="<?= htmlspecialchars($name) ?>">Eliminar</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="<?= $order['status'] === 'pending' ? 4 : 3 ?>"><strong>Total</strong></td>
                                    <td><strong>$<?= number_format($total, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                        <?php if ($order['status'] === 'pending'): ?>
                            <form class="update-order-form" method="post">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <input type="hidden" name="items_data" id="items-data-<?= htmlspecialchars($order['id']) ?>">
                                <button type="submit" name="update_order" class="btn btn-primary">Actualizar Pedido</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <a href="/index.php" class="btn btn-outline-secondary">Volver al catálogo</a>
</div>

<script>
function updateOrderItems(orderId) {
    const tbody = document.getElementById(`order-items-${orderId}`);
    const itemsDataInput = document.getElementById(`items-data-${orderId}`);
    const items = [];
    
    tbody.querySelectorAll('tr').forEach(row => {
        const name = row.querySelector('.quantity-input')?.getAttribute('data-product-name');
        const quantity = parseInt(row.querySelector('.quantity-input')?.value || 0);
        const price = parseFloat(row.cells[2].textContent.replace('$', '')) || 0;
        const productId = row.querySelector('.remove-order-item')?.getAttribute('data-product-id');
        if (name && quantity > 0) {
            items.push({ product_id: productId || name, name, quantity, price });
        }
    });

    itemsDataInput.value = JSON.stringify(items);
}

document.addEventListener('input', (e) => {
    if (e.target.classList.contains('quantity-input')) {
        const orderId = e.target.getAttribute('data-order-id');
        updateOrderItems(orderId);
    }
});

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-order-item')) {
        const orderId = e.target.getAttribute('data-order-id');
        const row = e.target.closest('tr');
        row.remove();
        updateOrderItems(orderId);
    }
});

// Inicializar datos de items para cada formulario
document.querySelectorAll('.update-order-form').forEach(form => {
    const orderId = form.querySelector('input[name="order_id"]').value;
    updateOrderItems(orderId);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>