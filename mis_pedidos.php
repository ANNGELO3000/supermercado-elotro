<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario'])) { header("Location: login_cliente.php"); exit(); }

$cliente = $conn->query("SELECT * FROM clientes WHERE usuario='{$_SESSION['usuario']}'")->fetch_assoc();
$pedidos = $conn->query("SELECT * FROM pedidos WHERE id_cliente={$cliente['id']} ORDER BY fecha DESC");

$estados_color = [
    'pendiente_pago' => '#aaa',
    'confirmado'     => '#007bff',
    'listo_recoger'  => '#FFC300',
    'en_camino'      => '#ff6b35',
    'entregado'      => '#28c76f',
    'cancelado'      => '#E63946'
];

$estados_icon = [
    'pendiente_pago' => 'bi-hourglass-split',
    'confirmado'     => 'bi-check-circle-fill',
    'listo_recoger'  => 'bi-bag-check-fill',
    'en_camino'      => 'bi-bicycle',
    'entregado'      => 'bi-check2-all',
    'cancelado'      => 'bi-x-circle-fill'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - El Otro</title>
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
        .contenido { padding: 24px; max-width: 800px; margin: 0 auto; }
        .pedido-card { background: var(--gris); border: 1px solid var(--gris2); border-radius: 14px; padding: 20px; margin-bottom: 16px; transition: all 0.2s; }
        .pedido-card:hover { border-color: var(--amarillo); }
        .pedido-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
        .pedido-num { font-size: 16px; font-weight: 700; color: #fff; }
        .pedido-fecha { font-size: 12px; color: #888; }
        .badge-estado { font-size: 12px; padding: 6px 14px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .pedido-info { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .pedido-total { font-size: 20px; font-weight: 700; color: var(--amarillo); }
        .pedido-dir { font-size: 13px; color: #888; }
        .btn-detalle { background: rgba(255,195,0,0.1); border: 1px solid rgba(255,195,0,0.3); color: var(--amarillo); border-radius: 8px; padding: 6px 14px; font-size: 13px; text-decoration: none; transition: all 0.2s; }
        .btn-detalle:hover { background: rgba(255,195,0,0.2); color: var(--amarillo); }
        .vacio { text-align: center; padding: 48px 24px; color: #666; }
        .vacio i { font-size: 48px; margin-bottom: 16px; display: block; }
        .alerta-nuevo { background: rgba(40,199,111,0.1); border: 1px solid rgba(40,199,111,0.3); border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; color: #28c76f; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        @media (max-width: 768px) { .contenido { padding: 16px; } }
    </style>
</head>
<body>
<nav class="navbar-cliente">
    <a href="inicio_cliente.php" class="navbar-brand">🛒 El Otro<span>Supermercado Online</span></a>
    <div style="display:flex; gap:10px;">
        <a href="inicio_cliente.php" class="btn-volver"><i class="bi bi-arrow-left me-1"></i>Catálogo</a>
        <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</nav>

<div class="contenido">

    <?php if (isset($_GET['nuevo'])): ?>
    <div class="alerta-nuevo">
        <i class="bi bi-check-circle-fill" style="font-size:20px;"></i>
        ¡Pedido realizado correctamente! Espera la confirmación del pago QR.
    </div>
    <?php endif; ?>

    <h4 style="color:#fff; margin-bottom:20px; font-weight:700;"><i class="bi bi-bag-fill me-2" style="color:var(--amarillo)"></i>Mis Pedidos</h4>

    <?php if ($pedidos->num_rows === 0): ?>
    <div class="vacio">
        <i class="bi bi-bag-x"></i>
        <p>No tienes pedidos aún</p>
        <a href="inicio_cliente.php" style="color:var(--amarillo)">Ir al catálogo</a>
    </div>
    <?php else: ?>
    <?php while ($p = $pedidos->fetch_assoc()):
    $color = $estados_color[$p['estado']] ?? '#aaa';
    $icon  = $estados_icon[$p['estado']] ?? 'bi-circle';
    $estado_label = str_replace('_', ' ', ucfirst($p['estado']));
    $detalles = $conn->query("SELECT dp.cantidad, pr.nombre FROM detalle_pedidos dp JOIN productos pr ON dp.id_producto = pr.id WHERE dp.id_pedido={$p['id']}");
    ?>
    <div class="pedido-card">
        <div class="pedido-header">
            <div>
                <div class="pedido-num">Pedido #<?php echo $p['id']; ?></div>
                <div class="pedido-fecha"><?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?></div>
            </div>
            <span class="badge-estado" style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>; border:1px solid <?php echo $color; ?>44">
                <i class="bi <?php echo $icon; ?>"></i>
                <?php echo $estado_label; ?>
            </span>
        </div>

        <!-- Productos del pedido -->
        <div style="margin-bottom:12px;">
            <?php while ($d = $detalles->fetch_assoc()): ?>
            <span style="background:#222; border-radius:6px; padding:3px 8px; font-size:12px; color:#ccc; margin-right:4px; margin-bottom:4px; display:inline-block;">
                <?php echo $d['cantidad']; ?>x <?php echo htmlspecialchars($d['nombre']); ?>
            </span>
            <?php endwhile; ?>
        </div>

        <div class="pedido-info">
            <div>
                <div class="pedido-total">Bs <?php echo number_format($p['total'], 2); ?></div>
                <div class="pedido-dir"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($p['domicilio_entrega']); ?></div>
            </div>
            <a href="detalle_pedido.php?id=<?php echo $p['id']; ?>" class="btn-detalle">
                <i class="bi bi-eye me-1"></i>Ver detalle
            </a>
        </div>
    </div>
    <?php endwhile; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
