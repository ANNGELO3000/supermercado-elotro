<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

// ========== REGISTRAR ENTRADA ==========
if (isset($_POST['registrar_entrada'])) {
    $id_producto  = $_POST['id_producto'];
    $id_proveedor = $_POST['id_proveedor'];
    $cantidad     = $_POST['cantidad'];
    $costo_total  = $_POST['costo_total'];
    $fecha        = $_POST['fecha'];
    $hora         = $_POST['hora'];
    $observacion  = trim($_POST['observacion']);

    $stmt = $conn->prepare("INSERT INTO entradas_inventario (id_producto, id_proveedor, cantidad, costo_total, fecha, hora, observacion) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("iiidss s", $id_producto, $id_proveedor, $cantidad, $costo_total, $fecha, $hora, $observacion);
    if ($stmt->execute()) {
        $conn->query("UPDATE productos SET stock = stock + $cantidad WHERE id = $id_producto");
        $mensaje = "Entrada registrada y stock actualizado.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al registrar entrada.";
        $tipo_mensaje = "danger";
    }
}

// ========== DATOS ==========
$productos   = $conn->query("SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.estado = 1 ORDER BY p.stock ASC");
$proveedores = $conn->query("SELECT * FROM proveedores WHERE estado = 1");
$entradas    = $conn->query("SELECT e.*, p.nombre as producto, pr.nombre as proveedor FROM entradas_inventario e JOIN productos p ON e.id_producto = p.id JOIN proveedores pr ON e.id_proveedor = pr.id ORDER BY e.fecha DESC, e.hora DESC LIMIT 20");

$stock_bajo    = $conn->query("SELECT COUNT(*) as total FROM productos WHERE stock <= 5 AND estado = 1")->fetch_assoc()['total'];
$stock_critico = $conn->query("SELECT COUNT(*) as total FROM productos WHERE stock = 0 AND estado = 1")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-inv { background: var(--gris); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        .tabla-inv thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; padding: 14px 16px; }
        .tabla-inv tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-inv tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .stock-ok { color: #28c76f; font-weight: 700; }
        .stock-bajo { color: var(--amarillo); font-weight: 700; }
        .stock-critico { color: var(--rojo); font-weight: 700; }
        .modal-content { background: var(--negro2); border: 1px solid var(--amarillo); color: var(--texto); }
        .modal-header { border-bottom: 1px solid var(--gris2); }
        .modal-footer { border-top: 1px solid var(--gris2); }
        .modal-title { color: var(--amarillo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control, .form-select { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus, .form-select:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .form-select option { background: #111; }
        .btn-entrada { background: var(--amarillo); color: var(--negro); border: none; font-weight: 700; padding: 10px 20px; border-radius: 8px; }
        .btn-guardar { background: var(--amarillo); color: var(--negro); font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
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
        <a href="inventario.php" class="nav-item active"><i class="bi bi-archive-fill"></i> Inventario</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
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
            <span class="topbar-title">Inventario</span>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?php echo date('d/m/Y'); ?></span>
        </div>
    </div>

    <div class="page-content">

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" style="border-radius:10px;">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon amarillo"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="stat-info"><h3><?php echo $stock_bajo; ?></h3><p>Productos stock bajo (≤5)</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rojo"><i class="bi bi-x-circle-fill"></i></div>
                <div class="stat-info"><h3><?php echo $stock_critico; ?></h3><p>Productos sin stock</p></div>
            </div>
        </div>

        <!-- Stock actual -->
        <div class="tabla-top">
            <div class="section-title" style="margin:0; flex:1;">Stock Actual de Productos</div>
            <button class="btn-entrada" data-bs-toggle="modal" data-bs-target="#modalEntrada">
                <i class="bi bi-plus-lg"></i> Registrar Entrada
            </button>
        </div>

        <div class="tabla-inv">
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
                            <th>Estado stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $productos_arr = [];
                        while ($p = $productos->fetch_assoc()):
                            $productos_arr[] = $p;
                            if ($p['stock'] == 0) $class = 'stock-critico';
                            elseif ($p['stock'] <= 5) $class = 'stock-bajo';
                            else $class = 'stock-ok';
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                            <td><?php echo $p['categoria']; ?></td>
                            <td>Bs <?php echo number_format($p['precio'], 2); ?></td>
                            <td class="<?php echo $class; ?>">
                                <?php echo $p['stock']; ?>
                                <?php if ($p['stock'] <= 5): ?><i class="bi bi-exclamation-triangle-fill ms-1"></i><?php endif; ?>
                            </td>
                            <td><?php echo $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '—'; ?></td>
                            <td>
                                <?php if ($p['stock'] == 0): ?>
                                <span style="color:var(--rojo); font-size:12px; font-weight:600;">⛔ Sin stock</span>
                                <?php elseif ($p['stock'] <= 5): ?>
                                <span style="color:var(--amarillo); font-size:12px; font-weight:600;">⚠️ Stock bajo</span>
                                <?php else: ?>
                                <span style="color:#28c76f; font-size:12px; font-weight:600;">✅ Disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Historial entradas -->
        <div class="section-title">Últimas Entradas de Inventario</div>
        <div class="tabla-inv">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th>Cantidad</th>
                            <th>Costo total</th>
                            <th>Fecha</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($e = $entradas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($e['producto']); ?></strong></td>
                            <td><?php echo htmlspecialchars($e['proveedor']); ?></td>
                            <td style="color:#28c76f; font-weight:700;">+<?php echo $e['cantidad']; ?></td>
                            <td>Bs <?php echo number_format($e['costo_total'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($e['fecha'])); ?> <?php echo $e['hora']; ?></td>
                            <td><?php echo $e['observacion'] ?: '—'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($entradas->num_rows === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay entradas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- MODAL REGISTRAR ENTRADA -->
<div class="modal fade" id="modalEntrada" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Registrar Entrada de Inventario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Producto</label>
                            <select name="id_producto" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($productos_arr as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?> (Stock: <?php echo $p['stock']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="id_proveedor" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $proveedores->data_seek(0);
                                while ($pr = $proveedores->fetch_assoc()):
                                ?>
                                <option value="<?php echo $pr['id']; ?>"><?php echo $pr['nombre']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cantidad recibida</label>
                            <input type="number" name="cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Costo total (Bs)</label>
                            <input type="number" step="0.01" name="costo_total" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora" class="form-control" value="<?php echo date('H:i'); ?>" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Observación <span style="color:#666">(opcional)</span></label>
                            <input type="text" name="observacion" class="form-control" placeholder="Ej: Cantidad diferente a la solicitada">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar_entrada" class="btn-guardar btn"><i class="bi bi-check-lg me-1"></i>Registrar</button>
                </div>
            </form>
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
