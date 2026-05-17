<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $productos_ofrecidos = trim($_POST['productos_ofrecidos']);
    $stmt = $conn->prepare("INSERT INTO proveedores (nombre, telefono, productos_ofrecidos) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $telefono, $productos_ofrecidos);
    if ($stmt->execute()) { $mensaje = "Proveedor registrado correctamente."; $tipo_mensaje = "success"; }
    else { $mensaje = "Error al registrar proveedor."; $tipo_mensaje = "danger"; }
}

if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $productos_ofrecidos = trim($_POST['productos_ofrecidos']);
    $stmt = $conn->prepare("UPDATE proveedores SET nombre=?, telefono=?, productos_ofrecidos=? WHERE id=?");
    $stmt->bind_param("sssi", $nombre, $telefono, $productos_ofrecidos, $id);
    if ($stmt->execute()) { $mensaje = "Proveedor actualizado correctamente."; $tipo_mensaje = "success"; }
    else { $mensaje = "Error al actualizar proveedor."; $tipo_mensaje = "danger"; }
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("UPDATE proveedores SET estado = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { $mensaje = "Proveedor eliminado correctamente."; $tipo_mensaje = "success"; }
}

$proveedores = $conn->query("SELECT * FROM proveedores WHERE estado = 1 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-proveedores { background: var(--gris); border-radius: 12px; overflow: hidden; }
        .tabla-proveedores thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; }
        .tabla-proveedores tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-proveedores tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .btn-agregar { background: var(--amarillo); color: var(--negro); border: none; font-weight: 700; padding: 10px 20px; border-radius: 8px; transition: all 0.2s; }
        .btn-agregar:hover { background: #e6b000; color: var(--negro); }
        .modal-content { background: var(--negro2); border: 1px solid var(--amarillo); color: var(--texto); }
        .modal-header { border-bottom: 1px solid var(--gris2); }
        .modal-footer { border-top: 1px solid var(--gris2); }
        .modal-title { color: var(--amarillo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .btn-guardar { background: var(--amarillo); color: var(--negro); font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; }
        .btn-guardar:hover { background: #e6b000; color: var(--negro); }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        .badge-proveedor { background: rgba(255,195,0,0.15); color: var(--amarillo); border: 1px solid rgba(255,195,0,0.3); font-size: 11px; padding: 4px 10px; border-radius: 20px; }
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
        <a href="proveedores.php" class="nav-item active"><i class="bi bi-truck"></i> Proveedores</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
        <div class="nav-section">Operaciones</div>
        <a href="pedidos.php" class="nav-item"><i class="bi bi-cart-fill"></i> Pedidos</a>
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
            <span class="topbar-title">Proveedores</span>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?php echo date('d/m/Y'); ?></span>
        </div>
    </div>

    <div class="page-content">
        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert" style="border-radius:10px;">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="tabla-top">
            <div class="section-title" style="margin:0; flex:1;">Gestión de Abastecimiento de Productos</div>
            <button class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                <i class="bi bi-plus-lg"></i> Agregar Proveedor
            </button>
        </div>

        <div class="tabla-proveedores">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Productos que ofrece</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($p = $proveedores->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                            <td><i class="bi bi-telephone me-1" style="color:var(--amarillo)"></i><?php echo htmlspecialchars($p['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($p['productos_ofrecidos']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-1" onclick="abrirEditar(<?php echo htmlspecialchars(json_encode($p)); ?>)"><i class="bi bi-pencil-fill"></i></button>
                                <a href="?eliminar=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este proveedor?')"><i class="bi bi-trash-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($proveedores->num_rows === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay proveedores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Agregar Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Distribuidora PIL" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" placeholder="Ej: 76543210" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Productos que ofrece</label>
                        <textarea name="productos_ofrecidos" class="form-control" rows="3" placeholder="Ej: Leche, yogurt, queso..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar" class="btn-guardar btn"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="edit_telefono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Productos que ofrece</label>
                        <textarea name="productos_ofrecidos" id="edit_productos" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar" class="btn-guardar btn"><i class="bi bi-check-lg me-1"></i>Actualizar</button>
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
function abrirEditar(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_nombre').value = p.nombre;
    document.getElementById('edit_telefono').value = p.telefono;
    document.getElementById('edit_productos').value = p.productos_ofrecidos || '';
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
</body>
</html>
