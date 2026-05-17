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
    $nombre    = trim($_POST['nombre']);
    $apellido  = trim($_POST['apellido']);
    $usuario   = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);
    $rol       = $_POST['rol'];
    $check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->bind_param("s", $usuario);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $mensaje = "Ya existe un usuario con ese nombre de usuario."; $tipo_mensaje = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, usuario, contrasena, rol) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $nombre, $apellido, $usuario, $contrasena, $rol);
        if ($stmt->execute()) { $mensaje = "Usuario registrado correctamente."; $tipo_mensaje = "success"; }
        else { $mensaje = "Error al registrar usuario."; $tipo_mensaje = "danger"; }
    }
}

if (isset($_POST['editar'])) {
    $id       = $_POST['id'];
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $rol      = $_POST['rol'];
    $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, apellido=?, rol=? WHERE id=?");
    $stmt->bind_param("sssi", $nombre, $apellido, $rol, $id);
    if ($stmt->execute()) { $mensaje = "Usuario actualizado correctamente."; $tipo_mensaje = "success"; }
}

if (isset($_POST['cambiar_password'])) {
    $id          = $_POST['id'];
    $nueva_pass  = trim($_POST['nueva_password']);
    $stmt = $conn->prepare("UPDATE usuarios SET contrasena=? WHERE id=?");
    $stmt->bind_param("si", $nueva_pass, $id);
    if ($stmt->execute()) { $mensaje = "Contraseña actualizada correctamente."; $tipo_mensaje = "success"; }
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if ($id != $_SESSION['id']) {
        $stmt = $conn->prepare("UPDATE usuarios SET estado=0 WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) { $mensaje = "Usuario eliminado correctamente."; $tipo_mensaje = "success"; }
    } else {
        $mensaje = "No puedes eliminar tu propio usuario."; $tipo_mensaje = "danger";
    }
}

$usuarios = $conn->query("SELECT * FROM usuarios WHERE estado=1 ORDER BY id DESC");
$roles_color = ['administrador' => '#E63946', 'encargado_ventas' => '#FFC300'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-usuarios { background: var(--gris); border-radius: 12px; overflow: hidden; }
        .tabla-usuarios thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; padding: 14px 16px; }
        .tabla-usuarios tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-usuarios tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .btn-agregar { background: var(--amarillo); color: var(--negro); border: none; font-weight: 700; padding: 10px 20px; border-radius: 8px; }
        .modal-content { background: var(--negro2); border: 1px solid var(--amarillo); color: var(--texto); }
        .modal-header { border-bottom: 1px solid var(--gris2); }
        .modal-footer { border-top: 1px solid var(--gris2); }
        .modal-title { color: var(--amarillo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control, .form-select { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus, .form-select:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .form-select option { background: #111; }
        .btn-guardar { background: var(--amarillo); color: var(--negro); font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        .avatar-user { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
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
        <a href="usuarios.php" class="nav-item active"><i class="bi bi-person-gear"></i> Usuarios</a>
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
            <span class="topbar-title">Usuarios del Sistema</span>
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
            <div class="section-title" style="margin:0; flex:1;">Gestión de Usuarios</div>
            <button class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                <i class="bi bi-plus-lg"></i> Agregar Usuario
            </button>
        </div>

        <div class="tabla-usuarios">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>#</th><th>Usuario</th><th>Rol</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($u = $usuarios->fetch_assoc()):
                        $color = $roles_color[$u['rol']] ?? '#888';
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div class="avatar-user" style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>; border:2px solid <?php echo $color; ?>44">
                                        <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></strong><br>
                                        <small style="color:#888">@<?php echo $u['usuario']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>; border:1px solid <?php echo $color; ?>44; font-size:11px; padding:4px 10px; border-radius:20px; font-weight:600;"><?php echo ucfirst(str_replace('_', ' ', $u['rol'])); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-1" onclick="abrirEditar(<?php echo htmlspecialchars(json_encode($u)); ?>)"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-info me-1" onclick="abrirPassword(<?php echo $u['id']; ?>)"><i class="bi bi-key-fill"></i></button>
                                <?php if ($u['id'] != $_SESSION['id']): ?>
                                <a href="?eliminar=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este usuario?')"><i class="bi bi-trash-fill"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
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
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Agregar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellido" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="contrasena" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select" required>
                            <option value="administrador">Administrador</option>
                            <option value="encargado_ventas">Encargado de Ventas</option>
                        </select>
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
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="rol" id="edit_rol" class="form-select">
                            <option value="administrador">Administrador</option>
                            <option value="encargado_ventas">Encargado de Ventas</option>
                        </select>
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

<!-- MODAL CAMBIAR PASSWORD -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key-fill me-2"></i>Cambiar Contraseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="pass_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="nueva_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="cambiar_password" class="btn-guardar btn"><i class="bi bi-check-lg me-1"></i>Actualizar</button>
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
function abrirEditar(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_nombre').value = u.nombre;
    document.getElementById('edit_apellido').value = u.apellido;
    document.getElementById('edit_rol').value = u.rol;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
function abrirPassword(id) {
    document.getElementById('pass_id').value = id;
    new bootstrap.Modal(document.getElementById('modalPassword')).show();
}
</script>
</body>
</html>
