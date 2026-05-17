<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario'])) { header("Location: login_cliente.php"); exit(); }

$cliente = $conn->query("SELECT * FROM clientes WHERE usuario='{$_SESSION['usuario']}'")->fetch_assoc();
if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

$mensaje = ""; $tipo_mensaje = "";

// Eliminar del carrito
if (isset($_GET['eliminar'])) {
    unset($_SESSION['carrito'][$_GET['eliminar']]);
    header("Location: carrito.php"); exit();
}

// Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    header("Location: carrito.php"); exit();
}

// Actualizar cantidad
if (isset($_POST['actualizar'])) {
    foreach ($_POST['cantidad'] as $id => $cant) {
        if ($cant > 0 && isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad'] = intval($cant);
        }
    }
    header("Location: carrito.php"); exit();
}

// Realizar pedido
if (isset($_POST['realizar_pedido'])) {
    $domicilio  = trim($_POST['domicilio']);
    $referencia = trim($_POST['referencia']);
    $total = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    $stmt = $conn->prepare("INSERT INTO pedidos (id_cliente, domicilio_entrega, referencia_entrega, total, metodo_pago, estado) VALUES (?,?,?,?,'QR','pendiente_pago')");
    $stmt->bind_param("issd", $cliente['id'], $domicilio, $referencia, $total);
    if ($stmt->execute()) {
        $id_pedido = $conn->insert_id;
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            $stmt2 = $conn->prepare("INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
            $stmt2->bind_param("iiidd", $id_pedido, $item['id'], $item['cantidad'], $item['precio'], $subtotal);
            $stmt2->execute();
        }
        $_SESSION['carrito'] = [];
        header("Location: mis_pedidos.php?nuevo=1"); exit();
    }
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --amarillo: #FFC300; --rojo: #E63946; --negro: #1A1A1A; --negro2: #111; --gris: #2a2a2a; --gris2: #333; --texto: #e0e0e0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0f0f0f; color: var(--texto); font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .navbar-cliente { background: var(--negro2); border-bottom: 2px solid var(--amarillo); padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .navbar-brand { color: var(--amarillo); font-size: 22px; font-weight: 700; text-decoration: none; }
        .navbar-brand span { color: #fff; font-size: 13px; font-weight: 400; display: block; }
        .btn-volver { background: var(--gris); border: 1px solid var(--gris2); color: #aaa; border-radius: 8px; padding: 8px 16px; font-size: 13px; text-decoration: none; }
        .btn-volver:hover { border-color: var(--amarillo); color: var(--amarillo); }
        .btn-logout { background: transparent; border: 1px solid #444; color: #888; border-radius: 8px; padding: 8px 12px; font-size: 13px; text-decoration: none; }
        .contenido { padding: 24px; max-width: 900px; margin: 0 auto; }
        .carrito-card { background: var(--gris); border-radius: 14px; padding: 24px; border: 1px solid var(--gris2); margin-bottom: 20px; }
        .carrito-card h5 { color: var(--amarillo); font-weight: 700; margin-bottom: 16px; }
        .item-carrito { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--gris2); gap: 12px; flex-wrap: wrap; }
        .item-carrito:last-child { border-bottom: none; }
        .item-nombre { font-weight: 600; color: #fff; font-size: 14px; }
        .item-precio { color: var(--amarillo); font-weight: 700; }
        .qty-input { background: #111; border: 1px solid #444; color: #fff; border-radius: 6px; padding: 4px 8px; width: 60px; text-align: center; }
        .btn-eliminar { background: rgba(230,57,70,0.15); border: 1px solid rgba(230,57,70,0.3); color: var(--rojo); border-radius: 6px; padding: 4px 10px; font-size: 12px; text-decoration: none; }
        .total-box { background: rgba(255,195,0,0.1); border: 1px solid rgba(255,195,0,0.3); border-radius: 12px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .total-box span { color: #888; }
        .total-box strong { color: var(--amarillo); font-size: 24px; font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; padding: 10px 14px !important; }
        .form-control:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .btn-pedido { background: var(--amarillo); color: var(--negro); border: none; border-radius: 10px; padding: 14px; width: 100%; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-pedido:hover { background: #e6b000; }
        .vacio { text-align: center; padding: 48px 24px; color: #666; }
        .vacio i { font-size: 48px; margin-bottom: 16px; display: block; }
        @media (max-width: 768px) { .contenido { padding: 16px; } }
    </style>
</head>
<body>
<nav class="navbar-cliente">
    <a href="inicio_cliente.php" class="navbar-brand">🛒 El Otro<span>Supermercado Online</span></a>
    <div style="display:flex; gap:10px;">
        <a href="inicio_cliente.php" class="btn-volver"><i class="bi bi-arrow-left me-1"></i>Seguir comprando</a>
        <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</nav>

<div class="contenido">
    <h4 style="color:#fff; margin-bottom:20px; font-weight:700;"><i class="bi bi-cart-fill me-2" style="color:var(--amarillo)"></i>Mi Carrito</h4>

    <?php if (empty($_SESSION['carrito'])): ?>
    <div class="vacio">
        <i class="bi bi-cart-x"></i>
        <p>Tu carrito está vacío</p>
        <a href="inicio_cliente.php" style="color:var(--amarillo)">Ver productos</a>
    </div>
    <?php else: ?>

    <form method="POST">
        <div class="carrito-card">
            <h5><i class="bi bi-bag me-2"></i>Productos</h5>
            <?php foreach ($_SESSION['carrito'] as $id => $item): ?>
            <div class="item-carrito">
                <div>
                    <div class="item-nombre"><?php echo htmlspecialchars($item['nombre']); ?></div>
                    <div class="item-precio">Bs <?php echo number_format($item['precio'], 2); ?> c/u</div>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="number" name="cantidad[<?php echo $id; ?>]" value="<?php echo $item['cantidad']; ?>" min="1" class="qty-input">
                    <span style="color:var(--amarillo); font-weight:700;">Bs <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                    <a href="?eliminar=<?php echo $id; ?>" class="btn-eliminar" onclick="return confirm('¿Quitar este producto?')"><i class="bi bi-trash-fill"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:12px; display:flex; gap:10px;">
                <button type="submit" name="actualizar" class="btn btn-sm btn-outline-warning">Actualizar cantidades</button>
                <a href="?vaciar=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Vaciar carrito?')">Vaciar carrito</a>
            </div>
        </div>

        <div class="total-box">
            <span>Total del pedido</span>
            <strong>Bs <?php echo number_format($total, 2); ?></strong>
        </div>

        <div class="carrito-card">
            <h5><i class="bi bi-geo-alt-fill me-2"></i>Dirección de entrega</h5>
            <div class="mb-3">
                <label class="form-label">Domicilio</label>
                <input type="text" name="domicilio" class="form-control" value="<?php echo htmlspecialchars($cliente['domicilio']); ?>" placeholder="Ingresa tu dirección" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Referencia <span style="color:#666">(opcional)</span></label>
                <input type="text" name="referencia" class="form-control" value="<?php echo htmlspecialchars($cliente['referencia'] ?? ''); ?>" placeholder="Ej: Frente a la cancha">
            </div>
            <div style="background:rgba(255,195,0,0.08); border:1px solid rgba(255,195,0,0.2); border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#aaa;">
                <i class="bi bi-qr-code me-2" style="color:var(--amarillo)"></i>El pago se realizará por <strong style="color:var(--amarillo)">código QR</strong> al momento de confirmar tu pedido.
            </div>
            <button type="submit" name="realizar_pedido" class="btn-pedido">
                <i class="bi bi-check-circle-fill me-2"></i>Realizar Pedido
            </button>
        </div>
    </form>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
