<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login_cliente.php");
    exit();
}

$es_cliente = $_SESSION['rol'] === 'cliente';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header($es_cliente ? "Location: mis_pedidos.php" : "Location: pedidos.php"); exit(); }

$pedido   = $conn->query("SELECT p.*, c.nombres, c.apellidos, c.ci, c.celular, c.domicilio FROM pedidos p JOIN clientes c ON p.id_cliente = c.id WHERE p.id = $id")->fetch_assoc();
$detalles = $conn->query("SELECT dp.*, pr.nombre as producto, pr.precio FROM detalle_pedidos dp JOIN productos pr ON dp.id_producto = pr.id WHERE dp.id_pedido = $id");

if (!$pedido) { header($es_cliente ? "Location: mis_pedidos.php" : "Location: pedidos.php"); exit(); }

// Si es cliente verificar que el pedido le pertenece
if ($es_cliente) {
    $cliente_session = $conn->query("SELECT id FROM clientes WHERE usuario='{$_SESSION['usuario']}'")->fetch_assoc();
    if (!$cliente_session || $pedido['id_cliente'] != $cliente_session['id']) {
        header("Location: mis_pedidos.php"); exit();
    }
}

$estados_color = [
    'pendiente_pago' => '#aaa',
    'confirmado'     => '#007bff',
    'listo_recoger'  => '#FFC300',
    'en_camino'      => '#ff6b35',
    'entregado'      => '#28c76f',
    'cancelado'      => '#E63946'
];
$color  = $estados_color[$pedido['estado']] ?? '#aaa';
$estado = str_replace('_', ' ', ucfirst($pedido['estado']));

// Verificar si tiene venta/comprobante
$venta = $conn->query("SELECT * FROM ventas WHERE id_pedido = $id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Pedido #<?php echo $id; ?> - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .detalle-card { background: var(--gris); border-radius: 14px; padding: 24px; border: 1px solid var(--gris2); margin-bottom: 20px; }
        .detalle-card h6 { color: var(--amarillo); font-weight: 700; margin-bottom: 16px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--gris2); font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #888; }
        .info-value { color: #fff; font-weight: 500; }
        .tabla-det { background: var(--gris); border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .tabla-det thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; padding: 12px 16px; }
        .tabla-det tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .badge-estado { font-size: 12px; padding: 6px 14px; border-radius: 20px; font-weight: 600; }
        .total-box { background: rgba(255,195,0,0.1); border: 1px solid rgba(255,195,0,0.3); border-radius: 12px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
        .total-box span { color: #888; font-size: 14px; }
        .total-box strong { color: var(--amarillo); font-size: 24px; font-weight: 700; }
        .comprobante-box { background: rgba(40,199,111,0.1); border: 1px solid rgba(40,199,111,0.3); border-radius: 12px; padding: 14px 20px; display: flex; align-items: center; gap: 12px; }
        .btn-volver { background: var(--gris); border: 1px solid var(--gris2); color: #aaa; border-radius: 8px; padding: 10px 20px; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .btn-volver:hover { border-color: var(--amarillo); color: var(--amarillo); }
        .navbar-cliente { background: var(--negro2); border-bottom: 2px solid var(--amarillo); padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .navbar-brand { color: var(--amarillo); font-size: 22px; font-weight: 700; text-decoration: none; }
        .navbar-brand span { color: #fff; font-size: 13px; font-weight: 400; display: block; }
        .btn-logout { background: transparent; border: 1px solid #444; color: #888; border-radius: 8px; padding: 8px 12px; font-size: 13px; text-decoration: none; }
        @media print {
            .sidebar, .topbar, .btn-volver, .btn-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
<?php if ($es_cliente): ?>

<nav class="navbar-cliente">
    <a href="inicio_cliente.php" class="navbar-brand">🛒 El Otro <span>Supermercado Online</span></a>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="mis_pedidos.php" class="btn-volver"><i class="bi bi-arrow-left me-1"></i> Mis Pedidos</a>
        <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i></a>
    </div>
</nav>

<div style="padding:24px;max-width:900px;margin:0 auto;">

<?php else: ?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><h2>🛒 El Otro</h2><p>Supermercado Online</p></div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php $fp=($_SESSION['rol']=='administrador'?'imagenes/admin.jpeg':'imagenes/ventas.jpeg'); if(file_exists($fp)): ?><img src="<?php echo $fp; ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;"><?php else: ?><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?><?php endif; ?></div>
        <div class="user-info"><small><?php echo ucfirst($_SESSION['rol']); ?></small><span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a href="admin.php" class="nav-item"><i class="bi bi-grid-fill"></i> Dashboard</a>
        <div class="nav-section">Gestión</div>
        <a href="productos.php" class="nav-item"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="clientes.php" class="nav-item"><i class="bi bi-people-fill"></i> Clientes</a>
        <a href="proveedores.php" class="nav-item"><i class="bi bi-truck"></i> Proveedores</a>
        <div class="nav-section">Operaciones</div>
        <a href="pedidos.php" class="nav-item active"><i class="bi bi-cart-fill"></i> Pedidos</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
        <a href="ventas.php" class="nav-item"><i class="bi bi-cash-coin"></i> Ventas</a>
        <a href="inventario.php" class="nav-item"><i class="bi bi-archive-fill"></i> Inventario</a>
        <div class="nav-section">Sistema</div>
        <a href="reportes.php" class="nav-item"><i class="bi bi-bar-chart-fill"></i> Reportes</a>
        <a href="horario.php" class="nav-item"><i class="bi bi-clock-fill"></i> Horario</a>
        <div class="sidebar-footer">
            <a href="inicio_cliente.php" class="nav-item" style="color:#aaa;"><i class="bi bi-shop"></i> Ver Tienda</a>
            <a href="logout.php" class="nav-item logout"><i class="bi bi-box-arrow-left"></i> Cerrar Sesión</a>
        </div>
    </nav>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <span class="topbar-title">Detalle Pedido #<?php echo $id; ?></span>
        </div>
        <div class="topbar-right">
            <button class="btn-print btn btn-sm btn-outline-warning me-2" onclick="window.print()"><i class="bi bi-printer-fill me-1"></i>Imprimir</button>
        </div>
    </div>

    <div class="page-content">

        <div class="mb-3">
            <a href="pedidos.php" class="btn-volver"><i class="bi bi-arrow-left me-2"></i>Volver a Pedidos</a>
        </div>

<?php endif; ?>

        <div class="row">
            <!-- Info pedido -->
            <div class="col-md-6">
                <div class="detalle-card">
                    <h6><i class="bi bi-cart-fill me-2"></i>Información del Pedido</h6>
                    <div class="info-row">
                        <span class="info-label">Número de pedido</span>
                        <span class="info-value">#<?php echo $pedido['id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado</span>
                        <span class="badge-estado" style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>; border:1px solid <?php echo $color; ?>44"><?php echo $estado; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Método de pago</span>
                        <span class="info-value"><i class="bi bi-qr-code me-1" style="color:#28c76f"></i>QR</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección entrega</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['domicilio_entrega']); ?></span>
                    </div>
                    <?php if ($pedido['referencia_entrega']): ?>
                    <div class="info-row">
                        <span class="info-label">Referencia</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['referencia_entrega']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info cliente -->
            <div class="col-md-6">
                <div class="detalle-card">
                    <h6><i class="bi bi-person-fill me-2"></i>Datos del Cliente</h6>
                    <div class="info-row">
                        <span class="info-label">Nombre</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['nombres'] . ' ' . $pedido['apellidos']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">CI</span>
                        <span class="info-value"><?php echo $pedido['ci']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Celular</span>
                        <span class="info-value"><?php echo $pedido['celular']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Domicilio</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['domicilio']); ?></span>
                    </div>
                </div>

                <!-- Repartidor si tiene -->
                <?php if ($pedido['repartidor_nombre']): ?>
                <div class="detalle-card">
                    <h6><i class="bi bi-bicycle me-2"></i>Datos del Repartidor</h6>
                    <div class="info-row">
                        <span class="info-label">Nombre</span>
                        <span class="info-value"><?php echo $pedido['repartidor_nombre'] . ' ' . $pedido['repartidor_apellidos']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">CI</span>
                        <span class="info-value"><?php echo $pedido['repartidor_ci']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Vehículo</span>
                        <span class="info-value"><?php echo $pedido['repartidor_vehiculo']; ?> — <?php echo $pedido['repartidor_matricula']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Celular</span>
                        <span class="info-value"><?php echo $pedido['repartidor_celular']; ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Productos -->
        <div class="section-title">Productos del Pedido</div>
        <div class="tabla-det">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Precio unitario</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($d = $detalles->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($d['producto']); ?></strong></td>
                            <td>Bs <?php echo number_format($d['precio_unitario'], 2); ?></td>
                            <td><?php echo $d['cantidad']; ?></td>
                            <td><strong style="color:var(--amarillo)">Bs <?php echo number_format($d['subtotal'], 2); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Total -->
        <div class="total-box mb-4">
            <span>Total del pedido</span>
            <strong>Bs <?php echo number_format($pedido['total'], 2); ?></strong>
        </div>

        <!-- Comprobante -->
        <?php if ($venta): ?>
        <div class="comprobante-box">
            <i class="bi bi-receipt" style="color:#28c76f; font-size:24px;"></i>
            <div>
                <div style="color:#28c76f; font-weight:700; font-size:14px;">Comprobante de Pago</div>
                <div style="font-family:monospace; color:#888; font-size:13px;"><?php echo $venta['comprobante']; ?></div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    if(document.getElementById('sidebar')) {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
}
</script>
</body>
</html>
