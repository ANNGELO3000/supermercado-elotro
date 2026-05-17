<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$filtro_mes   = isset($_GET['mes']) ? $_GET['mes'] : '';

// ========== REPORTE VENTAS ==========
$sql_ventas = "SELECT v.*, c.nombres, c.apellidos FROM ventas v JOIN clientes c ON v.id_cliente = c.id WHERE 1=1";
if ($filtro_fecha) $sql_ventas .= " AND DATE(v.fecha) = '$filtro_fecha'";
if ($filtro_mes)   $sql_ventas .= " AND DATE_FORMAT(v.fecha, '%Y-%m') = '$filtro_mes'";
$sql_ventas .= " ORDER BY v.fecha DESC";
$ventas = $conn->query($sql_ventas);

$total_ventas = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM ventas" . ($filtro_fecha ? " WHERE DATE(fecha)='$filtro_fecha'" : ($filtro_mes ? " WHERE DATE_FORMAT(fecha,'%Y-%m')='$filtro_mes'" : "")))->fetch_assoc()['t'];

// ========== REPORTE INVENTARIO ==========
$productos_ok      = $conn->query("SELECT COUNT(*) as t FROM productos WHERE stock > 5 AND estado=1")->fetch_assoc()['t'];
$productos_bajo    = $conn->query("SELECT COUNT(*) as t FROM productos WHERE stock > 0 AND stock <= 5 AND estado=1")->fetch_assoc()['t'];
$productos_critico = $conn->query("SELECT COUNT(*) as t FROM productos WHERE stock <= 0 AND estado=1")->fetch_assoc()['t'];
$inventario        = $conn->query("SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.estado=1 ORDER BY p.stock ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-rep { background: var(--gris); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        .tabla-rep thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; padding: 14px 16px; }
        .tabla-rep tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-rep tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .stock-ok { color: #28c76f; font-weight: 700; }
        .stock-bajo { color: var(--amarillo); font-weight: 700; }
        .stock-critico { color: var(--rojo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .filtros-form { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; align-items: flex-end; }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        .badge-qr { background: rgba(40,199,111,0.15); color: #28c76f; border: 1px solid rgba(40,199,111,0.3); font-size: 11px; padding: 4px 10px; border-radius: 20px; }
        .total-row td { color: var(--amarillo) !important; font-weight: 700 !important; background: rgba(255,195,0,0.08) !important; }
        @media print {
            .sidebar, .topbar, .filtros-form, .btn-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><h2>🛒 El Otro</h2><p>Supermercado Online</p></div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php $fp=($_SESSION['rol']=='administrador'?'imagenes/admin.jpeg':'imagenes/ventas.jpeg'); if(file_exists($fp)): ?><img src="<?php echo $fp; ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;"><?php else: ?><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?><?php endif; ?></div>
        <div class="user-info"><small>Administrador</small><span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a href="admin.php" class="nav-item"><i class="bi bi-grid-fill"></i> Dashboard</a>
        <div class="nav-section">Gestión</div>
        <a href="productos.php" class="nav-item"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="clientes.php" class="nav-item"><i class="bi bi-people-fill"></i> Clientes</a>
        <a href="proveedores.php" class="nav-item"><i class="bi bi-truck"></i> Proveedores</a>
        <div class="nav-section">Operaciones</div>
        <a href="pedidos.php" class="nav-item"><i class="bi bi-cart-fill"></i> Pedidos</a>
        <a href="ventas.php" class="nav-item"><i class="bi bi-cash-coin"></i> Ventas</a>
        <a href="inventario.php" class="nav-item"><i class="bi bi-archive-fill"></i> Inventario</a>
        <div class="nav-section">Sistema</div>
        <a href="reportes.php" class="nav-item active"><i class="bi bi-bar-chart-fill"></i> Reportes</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
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
            <span class="topbar-title">Reportes</span>
        </div>
        <div class="topbar-right">
            <button class="btn-print btn btn-sm btn-outline-warning" onclick="window.print()"><i class="bi bi-printer-fill me-1"></i>Imprimir</button>
        </div>
    </div>

    <div class="page-content">

        <!-- Stats inventario -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon verde"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info"><h3><?php echo $productos_ok; ?></h3><p>Productos disponibles</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amarillo"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="stat-info"><h3><?php echo $productos_bajo; ?></h3><p>Stock bajo (≤5)</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rojo"><i class="bi bi-x-circle-fill"></i></div>
                <div class="stat-info"><h3><?php echo $productos_critico; ?></h3><p>Sin stock</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon azul"><i class="bi bi-cash-coin"></i></div>
                <div class="stat-info"><h3>Bs <?php echo number_format($total_ventas, 2); ?></h3><p>Total ventas filtradas</p></div>
            </div>
        </div>

        <!-- ===== REPORTE VENTAS ===== -->
        <div class="tabla-top">
            <div class="section-title" style="margin:0; flex:1;">Reporte de Ventas</div>
        </div>

        <form method="GET" class="filtros-form">
            <div>
                <label class="form-label">Por fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>" style="width:180px;">
            </div>
            <div>
                <label class="form-label">Por mes</label>
                <input type="month" name="mes" class="form-control" value="<?php echo $filtro_mes; ?>" style="width:180px;">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-warning fw-bold px-4">Filtrar</button>
                <a href="reportes.php" class="btn btn-secondary px-4">Limpiar</a>
            </div>
        </form>

        <div class="tabla-rep">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Comprobante</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Método pago</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($v = $ventas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td style="font-family:monospace; font-size:11px; color:#888"><?php echo $v['comprobante']; ?></td>
                            <td><strong><?php echo htmlspecialchars($v['nombres'] . ' ' . $v['apellidos']); ?></strong></td>
                            <td><strong style="color:var(--amarillo)">Bs <?php echo number_format($v['total'], 2); ?></strong></td>
                            <td><span class="badge-qr"><i class="bi bi-qr-code me-1"></i>QR</span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="total-row">
                            <td colspan="3" class="text-end">TOTAL:</td>
                            <td>Bs <?php echo number_format($total_ventas, 2); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== REPORTE INVENTARIO ===== -->
        <div class="section-title">Reporte de Inventario</div>
        <div class="tabla-rep">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock actual</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($p = $inventario->fetch_assoc()):
                        if ($p['stock'] <= 0) { $class = 'stock-critico'; $estado = '⛔ Sin stock'; }
                        elseif ($p['stock'] <= 5) { $class = 'stock-bajo'; $estado = '⚠️ Stock bajo'; }
                        else { $class = 'stock-ok'; $estado = '✅ Disponible'; }
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                            <td><?php echo $p['categoria']; ?></td>
                            <td>Bs <?php echo number_format($p['precio'], 2); ?></td>
                            <td class="<?php echo $class; ?>"><?php echo $p['stock']; ?></td>
                            <td><?php echo $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '—'; ?></td>
                            <td style="font-size:12px; font-weight:600;"><?php echo $estado; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
