<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['administrador', 'encargado_ventas'])) {
    header("Location: login.php");
    exit();
}

// ========== FILTROS ==========
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$filtro_mes   = isset($_GET['mes']) ? $_GET['mes'] : '';

$sql = "SELECT v.*, c.nombres, c.apellidos, p.estado as estado_pedido 
        FROM ventas v 
        JOIN clientes c ON v.id_cliente = c.id
        JOIN pedidos p ON v.id_pedido = p.id
        WHERE 1=1";

if ($filtro_fecha) $sql .= " AND DATE(v.fecha) = '$filtro_fecha'";
if ($filtro_mes)   $sql .= " AND DATE_FORMAT(v.fecha, '%Y-%m') = '$filtro_mes'";

$sql .= " ORDER BY v.fecha DESC";
$ventas = $conn->query($sql);

$total_general = $conn->query("SELECT COALESCE(SUM(total),0) as total FROM ventas")->fetch_assoc()['total'];
$total_hoy     = $conn->query("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['total'];
$total_mes     = $conn->query("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-ventas { background: var(--gris); border-radius: 12px; overflow: hidden; }
        .tabla-ventas thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; padding: 14px 16px; }
        .tabla-ventas tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-ventas tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .modal-content { background: var(--negro2); border: 1px solid var(--amarillo); color: var(--texto); }
        .modal-header { border-bottom: 1px solid var(--gris2); }
        .modal-footer { border-top: 1px solid var(--gris2); }
        .modal-title { color: var(--amarillo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        .filtros-form { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; align-items: center; }
        .badge-qr { background: rgba(40,199,111,0.15); color: #28c76f; border: 1px solid rgba(40,199,111,0.3); font-size: 11px; padding: 4px 10px; border-radius: 20px; }
        .comprobante { font-size: 11px; color: #888; font-family: monospace; }
    </style>
</head>
<body>
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
        <a href="pedidos.php" class="nav-item"><i class="bi bi-cart-fill"></i> Pedidos</a>
        <a href="ventas.php" class="nav-item active"><i class="bi bi-cash-coin"></i> Ventas</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
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
            <span class="topbar-title">Ventas</span>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?php echo date('d/m/Y'); ?></span>
        </div>
    </div>

    <div class="page-content">

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon verde"><i class="bi bi-cash-coin"></i></div>
                <div class="stat-info"><h3>Bs <?php echo number_format($total_hoy, 2); ?></h3><p>Ventas hoy</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amarillo"><i class="bi bi-calendar-month"></i></div>
                <div class="stat-info"><h3>Bs <?php echo number_format($total_mes, 2); ?></h3><p>Ventas este mes</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon azul"><i class="bi bi-graph-up"></i></div>
                <div class="stat-info"><h3>Bs <?php echo number_format($total_general, 2); ?></h3><p>Total general</p></div>
            </div>
        </div>

        <div class="tabla-top">
            <div class="section-title" style="margin:0; flex:1;">Registro de Ventas</div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="filtros-form">
            <div>
                <label class="form-label mb-1">Por fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>" style="width:180px;">
            </div>
            <div>
                <label class="form-label mb-1">Por mes</label>
                <input type="month" name="mes" class="form-control" value="<?php echo $filtro_mes; ?>" style="width:180px;">
            </div>
            <div style="margin-top:22px; display:flex; gap:8px;">
                <button type="submit" class="btn btn-warning fw-bold px-4">Filtrar</button>
                <a href="ventas.php" class="btn btn-secondary px-4">Limpiar</a>
            </div>
        </form>

        <!-- Tabla -->
        <div class="tabla-ventas">
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
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($v = $ventas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><span class="comprobante"><?php echo $v['comprobante']; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($v['nombres'] . ' ' . $v['apellidos']); ?></strong></td>
                            <td><strong style="color:var(--amarillo)">Bs <?php echo number_format($v['total'], 2); ?></strong></td>
                            <td><span class="badge-qr"><i class="bi bi-qr-code me-1"></i>QR</span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?></td>
                            <td>
                                <a href="detalle_pedido.php?id=<?php echo $v['id_pedido']; ?>" class="btn btn-sm btn-info" title="Ver detalle"><i class="bi bi-eye-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($ventas->num_rows === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay ventas registradas.</td></tr>
                        <?php endif; ?>
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
