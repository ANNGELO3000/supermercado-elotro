<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['administrador', 'encargado_ventas'])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

// ========== CAMBIAR ESTADO ==========
if (isset($_POST['cambiar_estado'])) {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $stmt = $conn->prepare("UPDATE pedidos SET estado=? WHERE id=?");
    $stmt->bind_param("si", $estado, $id);
    if ($stmt->execute()) { $mensaje = "Estado actualizado correctamente."; $tipo_mensaje = "success"; }
}

// ========== ASIGNAR REPARTIDOR ==========
if (isset($_POST['asignar_repartidor'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['repartidor_nombre']);
    $apellidos = trim($_POST['repartidor_apellidos']);
    $ci = trim($_POST['repartidor_ci']);
    $vehiculo = trim($_POST['repartidor_vehiculo']);
    $matricula = trim($_POST['repartidor_matricula']);
    $celular = trim($_POST['repartidor_celular']);
    $stmt = $conn->prepare("UPDATE pedidos SET repartidor_nombre=?, repartidor_apellidos=?, repartidor_ci=?, repartidor_vehiculo=?, repartidor_matricula=?, repartidor_celular=?, estado='listo_recoger' WHERE id=?");
    $stmt->bind_param("ssssssi", $nombre, $apellidos, $ci, $vehiculo, $matricula, $celular, $id);
    if ($stmt->execute()) { $mensaje = "Repartidor asignado correctamente."; $tipo_mensaje = "success"; }
}

// ========== CONFIRMAR PAGO Y DESCONTAR STOCK ==========
if (isset($_POST['confirmar_pago'])) {
    $id = $_POST['id'];
    // Cambiar estado a confirmado
    $conn->query("UPDATE pedidos SET estado='confirmado' WHERE id=$id");
    // Descontar stock
    $detalles = $conn->query("SELECT id_producto, cantidad FROM detalle_pedidos WHERE id_pedido=$id");
    while ($d = $detalles->fetch_assoc()) {
        $conn->query("UPDATE productos SET stock = stock - {$d['cantidad']} WHERE id = {$d['id_producto']}");
    }
    // Registrar venta
    $pedido = $conn->query("SELECT * FROM pedidos WHERE id=$id")->fetch_assoc();
    $comprobante = 'COMP-' . strtoupper(uniqid());
    $stmt = $conn->prepare("INSERT INTO ventas (id_pedido, id_cliente, total, metodo_pago, comprobante) VALUES (?,?,?,'QR',?)");
    $stmt->bind_param("iids", $id, $pedido['id_cliente'], $pedido['total'], $comprobante);
    $stmt->execute();
    $mensaje = "Pago confirmado. Comprobante: $comprobante"; $tipo_mensaje = "success";
}

// ========== FILTRO ==========
$filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$sql = "SELECT p.*, c.nombres, c.apellidos, c.celular FROM pedidos p 
        JOIN clientes c ON p.id_cliente = c.id";
if ($filtro) $sql .= " WHERE p.estado = '$filtro'";
$sql .= " ORDER BY p.fecha DESC";
$pedidos = $conn->query($sql);

$estados_color = [
    'pendiente_pago' => '#aaa',
    'confirmado' => '#007bff',
    'listo_recoger' => '#FFC300',
    'en_camino' => '#ff6b35',
    'entregado' => '#28c76f',
    'cancelado' => '#E63946'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-pedidos { background: var(--gris); border-radius: 12px; overflow: hidden; }
        .tabla-pedidos thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; padding: 14px 16px; }
        .tabla-pedidos tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-pedidos tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .badge-estado { font-size: 11px; padding: 5px 12px; border-radius: 20px; font-weight: 600; }
        .modal-content { background: var(--negro2); border: 1px solid var(--amarillo); color: var(--texto); }
        .modal-header { border-bottom: 1px solid var(--gris2); }
        .modal-footer { border-top: 1px solid var(--gris2); }
        .modal-title { color: var(--amarillo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control, .form-select { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus, .form-select:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .form-select option { background: #111; }
        .btn-guardar { background: var(--amarillo); color: var(--negro); font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; }
        .filtros { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .btn-filtro { background: var(--gris); border: 1px solid var(--gris2); color: #aaa; border-radius: 20px; padding: 6px 14px; font-size: 12px; text-decoration: none; transition: all 0.2s; }
        .btn-filtro:hover, .btn-filtro.active { background: rgba(255,195,0,0.15); border-color: var(--amarillo); color: var(--amarillo); }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
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
            <span class="topbar-title">Pedidos</span>
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

        <div class="tabla-top">
            <div class="section-title" style="margin:0; flex:1;">Gestión de Pedidos</div>
        </div>

        <div class="filtros">
            <a href="pedidos.php" class="btn-filtro <?php echo !$filtro ? 'active' : ''; ?>">Todos</a>
            <a href="?estado=pendiente_pago" class="btn-filtro <?php echo $filtro=='pendiente_pago' ? 'active' : ''; ?>">Pendiente pago</a>
            <a href="?estado=confirmado" class="btn-filtro <?php echo $filtro=='confirmado' ? 'active' : ''; ?>">Confirmado</a>
            <a href="?estado=listo_recoger" class="btn-filtro <?php echo $filtro=='listo_recoger' ? 'active' : ''; ?>">Listo recoger</a>
            <a href="?estado=en_camino" class="btn-filtro <?php echo $filtro=='en_camino' ? 'active' : ''; ?>">En camino</a>
            <a href="?estado=entregado" class="btn-filtro <?php echo $filtro=='entregado' ? 'active' : ''; ?>">Entregado</a>
            <a href="?estado=cancelado" class="btn-filtro <?php echo $filtro=='cancelado' ? 'active' : ''; ?>">Cancelado</a>
        </div>

        <div class="tabla-pedidos">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $pedidos->fetch_assoc()):
                        $color = $estados_color[$p['estado']] ?? '#aaa';
                        $estado_label = str_replace('_', ' ', ucfirst($p['estado']));
                        ?>
                        <tr>
                            <td><strong>#<?php echo $p['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']); ?></strong><br>
                                <small style="color:#888"><?php echo $p['celular']; ?></small>
                            </td>
                            <td><strong style="color:var(--amarillo)">Bs <?php echo number_format($p['total'], 2); ?></strong></td>
                            <td><span class="badge-estado" style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>; border:1px solid <?php echo $color; ?>44"><?php echo $estado_label; ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info me-1" onclick="verDetalle(<?php echo $p['id']; ?>)" title="Ver detalle"><i class="bi bi-eye-fill"></i></button>
                                <?php if ($p['estado'] == 'pendiente_pago'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="confirmar_pago" class="btn btn-sm btn-success me-1" onclick="return confirm('¿Confirmar pago QR?')" title="Confirmar pago"><i class="bi bi-check-circle-fill"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($p['estado'] == 'confirmado'): ?>
                                <button class="btn btn-sm btn-warning me-1" onclick="abrirRepartidor(<?php echo htmlspecialchars(json_encode($p)); ?>)" title="Asignar repartidor"><i class="bi bi-bicycle"></i></button>
                                <?php endif; ?>
                                <?php if (!in_array($p['estado'], ['entregado', 'cancelado'])): ?>
                                <button class="btn btn-sm btn-secondary" onclick="abrirEstado(<?php echo $p['id']; ?>, '<?php echo $p['estado']; ?>')" title="Cambiar estado"><i class="bi bi-arrow-repeat"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($pedidos->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay pedidos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CAMBIAR ESTADO -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Cambiar Estado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="estado_id">
                <div class="modal-body">
                    <label class="form-label">Nuevo estado</label>
                    <select name="estado" id="estado_select" class="form-select">
                        <option value="pendiente_pago">Pendiente pago</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="listo_recoger">Listo para recoger</option>
                        <option value="en_camino">En camino</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="cambiar_estado" class="btn-guardar btn"><i class="bi bi-check-lg me-1"></i>Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ASIGNAR REPARTIDOR -->
<div class="modal fade" id="modalRepartidor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bicycle me-2"></i>Asignar Repartidor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="rep_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="repartidor_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="repartidor_apellidos" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CI</label>
                            <input type="text" name="repartidor_ci" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo vehículo</label>
                            <input type="text" name="repartidor_vehiculo" class="form-control" placeholder="Ej: Moto" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Matrícula</label>
                            <input type="text" name="repartidor_matricula" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Celular</label>
                        <input type="text" name="repartidor_celular" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="asignar_repartidor" class="btn-guardar btn"><i class="bi bi-check-lg me-1"></i>Asignar</button>
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
function abrirEstado(id, estadoActual) {
    document.getElementById('estado_id').value = id;
    document.getElementById('estado_select').value = estadoActual;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}
function abrirRepartidor(p) {
    document.getElementById('rep_id').value = p.id;
    new bootstrap.Modal(document.getElementById('modalRepartidor')).show();
}
function verDetalle(id) {
    window.location.href = 'detalle_pedido.php?id=' + id;
}
</script>
</body>
</html>
