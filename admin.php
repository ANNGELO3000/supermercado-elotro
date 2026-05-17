<?php
session_start();
require_once 'conexion.php';

// Proteger la página
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

// Obtener estadísticas
$total_productos = $conn->query("SELECT COUNT(*) as total FROM productos WHERE estado = 1")->fetch_assoc()['total'];
$total_clientes  = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE estado = 1")->fetch_assoc()['total'];
$total_pedidos   = $conn->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['total'];
$total_ventas    = $conn->query("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['total'];
$stock_critico   = $conn->query("SELECT COUNT(*) as t FROM productos WHERE stock <= 5 AND estado=1")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <h2>🛒 El Otro</h2>
        <p>Supermercado Online</p>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?php $fp=($_SESSION['rol']=='administrador'?'imagenes/admin.jpeg':'imagenes/ventas.jpeg'); if(file_exists($fp)): ?><img src="<?php echo $fp; ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;"><?php else: ?><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?><?php endif; ?>
        </div>
        <div class="user-info">
            <small>Administrador</small>
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a href="admin.php" class="nav-item active">
            <i class="bi bi-grid-fill"></i> Dashboard
        </a>

        <div class="nav-section">Gestión</div>
        <a href="productos.php" class="nav-item">
            <i class="bi bi-box-seam"></i> Productos
        </a>
        <a href="clientes.php" class="nav-item">
            <i class="bi bi-people-fill"></i> Clientes
        </a>
        <a href="proveedores.php" class="nav-item">
            <i class="bi bi-truck"></i> Proveedores
        </a>
<a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
        <div class="nav-section">Operaciones</div>
        <a href="pedidos.php" class="nav-item">
            <i class="bi bi-cart-fill"></i> Pedidos
        </a>
        <a href="ventas.php" class="nav-item">
            <i class="bi bi-cash-coin"></i> Ventas
        </a>
        <a href="inventario.php" class="nav-item">
            <i class="bi bi-archive-fill"></i> Inventario
        </a>

        <div class="nav-section">Sistema</div>
        <a href="reportes.php" class="nav-item">
            <i class="bi bi-bar-chart-fill"></i> Reportes
        </a>
        <a href="horario.php" class="nav-item">
            <i class="bi bi-clock-fill"></i> Horario
        </a>

        <div class="sidebar-footer">
            <a href="inicio_cliente.php" class="nav-item" style="color:#aaa;"><i class="bi bi-shop"></i> Ver Tienda</a>
            <a href="logout.php" class="nav-item logout">
                <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
            </a>
        </div>
    </nav>

</aside>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <span class="topbar-title">Dashboard</span>
        </div>
        <div class="topbar-right">
            <?php if ($stock_critico > 0): ?>
            <a href="reportes.php" style="text-decoration:none;" title="<?php echo $stock_critico; ?> producto(s) con stock bajo">
                <span style="background:#E63946;color:#fff;border-radius:20px;padding:5px 12px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $stock_critico; ?> stock bajo
                </span>
            </a>
            <?php endif; ?>
            <span class="topbar-date">
                <i class="bi bi-calendar3"></i>
                <?php echo date('d/m/Y'); ?>
            </span>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon amarillo">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_productos; ?></h3>
                    <p>Productos activos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon azul">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_clientes; ?></h3>
                    <p>Clientes registrados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rojo">
                    <i class="bi bi-cart-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_pedidos; ?></h3>
                    <p>Pedidos hoy</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon verde">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-info">
                    <h3>Bs <?php echo number_format($total_ventas, 2); ?></h3>
                    <p>Ventas hoy</p>
                </div>
            </div>
        </div>

        <!-- Acceso rápido -->
        <div class="section-title">Acceso Rápido</div>
        <div class="quick-grid">
            <a href="productos.php" class="quick-card">
                <i class="bi bi-box-seam"></i>
                <span>Productos</span>
            </a>
            <a href="clientes.php" class="quick-card">
                <i class="bi bi-people-fill"></i>
                <span>Clientes</span>
            </a>
            <a href="proveedores.php" class="quick-card">
                <i class="bi bi-truck"></i>
                <span>Proveedores</span>
            </a>
            <a href="pedidos.php" class="quick-card">
                <i class="bi bi-cart-fill"></i>
                <span>Pedidos</span>
            </a>
            <a href="ventas.php" class="quick-card">
                <i class="bi bi-cash-coin"></i>
                <span>Ventas</span>
            </a>
            <a href="reportes.php" class="quick-card">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Reportes</span>
            </a>
            <a href="horario.php" class="quick-card">
                <i class="bi bi-clock-fill"></i>
                <span>Horario</span>
            </a>
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
