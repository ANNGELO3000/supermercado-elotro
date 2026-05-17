<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

// ========== AGREGAR CLIENTE ==========
if (isset($_POST['agregar'])) {
    $nombres   = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $ci        = trim($_POST['ci']);
    $domicilio = trim($_POST['domicilio']);
    $referencia = trim($_POST['referencia']);
    $celular   = trim($_POST['celular']);
    $usuario   = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    // Verificar CI o usuario duplicado
    $check = $conn->prepare("SELECT id FROM clientes WHERE ci = ? OR usuario = ?");
    $check->bind_param("ss", $ci, $usuario);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $mensaje = "Ya existe un cliente con ese CI o usuario.";
        $tipo_mensaje = "danger";
    } else {
        $sql = "INSERT INTO clientes (nombres, apellidos, ci, domicilio, referencia, celular, usuario, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $nombres, $apellidos, $ci, $domicilio, $referencia, $celular, $usuario, $contrasena);
        if ($stmt->execute()) {
            $mensaje = "Cliente registrado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al registrar cliente.";
            $tipo_mensaje = "danger";
        }
    }
}

// ========== EDITAR CLIENTE ==========
if (isset($_POST['editar'])) {
    $id        = $_POST['id'];
    $nombres   = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $ci        = trim($_POST['ci']);
    $domicilio = trim($_POST['domicilio']);
    $referencia = trim($_POST['referencia']);
    $celular   = trim($_POST['celular']);

    $sql = "UPDATE clientes SET nombres=?, apellidos=?, ci=?, domicilio=?, referencia=?, celular=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $nombres, $apellidos, $ci, $domicilio, $referencia, $celular, $id);
    if ($stmt->execute()) {
        $mensaje = "Cliente actualizado correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar cliente.";
        $tipo_mensaje = "danger";
    }
}

// ========== ELIMINAR CLIENTE ==========
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("UPDATE clientes SET estado = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = "Cliente eliminado correctamente.";
        $tipo_mensaje = "success";
    }
}

// ========== OBTENER CLIENTES ==========
$clientes = $conn->query("SELECT * FROM clientes WHERE estado = 1 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-clientes { background: var(--gris); border-radius: 12px; overflow: hidden; }
        .tabla-clientes thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; }
        .tabla-clientes tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-clientes tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .badge-usuario { background: rgba(255,195,0,0.15); color: var(--amarillo); border: 1px solid rgba(255,195,0,0.3); font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 500; }
        .btn-agregar { background: var(--amarillo); color: var(--negro); border: none; font-weight: 700; padding: 10px 20px; border-radius: 8px; transition: all 0.2s; }
        .btn-agregar:hover { background: #e6b000; color: var(--negro); transform: translateY(-1px); }
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
        .avatar-cliente { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,195,0,0.15); color: var(--amarillo); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; border: 2px solid rgba(255,195,0,0.3); }
        .cliente-nombre { display: flex; align-items: center; gap: 10px; }
        .buscador { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; padding: 8px 14px; width: 250px; }
        .buscador:focus { border-color: var(--amarillo) !important; outline: none; }
        .buscador::placeholder { color: #666; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2>🛒 El Otro</h2>
        <p>Supermercado Online</p>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php $fp=($_SESSION['rol']=='administrador'?'imagenes/admin.jpeg':'imagenes/ventas.jpeg'); if(file_exists($fp)): ?><img src="<?php echo $fp; ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;"><?php else: ?><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?><?php endif; ?></div>
        <div class="user-info">
            <small>Administrador</small>
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a href="admin.php" class="nav-item"><i class="bi bi-grid-fill"></i> Dashboard</a>
        <div class="nav-section">Gestión</div>
        <a href="productos.php" class="nav-item"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="clientes.php" class="nav-item active"><i class="bi bi-people-fill"></i> Clientes</a>
        <a href="proveedores.php" class="nav-item"><i class="bi bi-truck"></i> Proveedores</a>
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

<!-- MAIN -->
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <span class="topbar-title">Clientes</span>
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
            <div class="section-title" style="margin:0; flex:1;">Gestión de Clientes</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" id="buscador" class="buscador" placeholder="🔍 Buscar cliente..." onkeyup="buscarCliente()">
                <button class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                    <i class="bi bi-plus-lg"></i> Agregar Cliente
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div class="tabla-clientes">
            <div class="table-responsive">
                <table class="table table-borderless mb-0" id="tablaClientes">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>CI</th>
                            <th>Celular</th>
                            <th>Domicilio</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        while ($c = $clientes->fetch_assoc()):
                        $inicial = strtoupper(substr($c['nombres'], 0, 1));
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td>
                                <div class="cliente-nombre">
                                    <div class="avatar-cliente"><?php echo $inicial; ?></div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']); ?></strong>
                                        <?php if ($c['referencia']): ?>
                                        <br><small style="color:#888;"><?php echo htmlspecialchars($c['referencia']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($c['ci']); ?></td>
                            <td><i class="bi bi-phone me-1" style="color:var(--amarillo)"></i><?php echo htmlspecialchars($c['celular']); ?></td>
                            <td><?php echo htmlspecialchars($c['domicilio']); ?></td>
                            <td><span class="badge-usuario"><?php echo htmlspecialchars($c['usuario']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-1"
                                    onclick="abrirEditar(<?php echo htmlspecialchars(json_encode($c)); ?>)"
                                    title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <a href="?eliminar=<?php echo $c['id']; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar este cliente?')"
                                    title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($clientes->num_rows === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ========== MODAL AGREGAR ========== -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Agregar Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombres" class="form-control" placeholder="Ej: Juan Carlos" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" placeholder="Ej: Mamani Quispe" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CI</label>
                            <input type="text" name="ci" class="form-control" placeholder="Ej: 12345678" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Celular</label>
                            <input type="text" name="celular" class="form-control" placeholder="Ej: 76543210" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domicilio</label>
                        <input type="text" name="domicilio" class="form-control" placeholder="Ej: Calle 5 de octubre #123, Villa Victoria" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referencia <span style="color:#666">(opcional)</span></label>
                        <input type="text" name="referencia" class="form-control" placeholder="Ej: Frente a la cancha">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="usuario" class="form-control" placeholder="Ej: jmamani" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="contrasena" class="form-control" placeholder="Contraseña" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar" class="btn-guardar btn">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== MODAL EDITAR ========== -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombres" id="edit_nombres" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" id="edit_apellidos" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CI</label>
                            <input type="text" name="ci" id="edit_ci" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Celular</label>
                            <input type="text" name="celular" id="edit_celular" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domicilio</label>
                        <input type="text" name="domicilio" id="edit_domicilio" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referencia <span style="color:#666">(opcional)</span></label>
                        <input type="text" name="referencia" id="edit_referencia" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar" class="btn-guardar btn">
                        <i class="bi bi-check-lg me-1"></i> Actualizar
                    </button>
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

function abrirEditar(c) {
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_nombres').value = c.nombres;
    document.getElementById('edit_apellidos').value = c.apellidos;
    document.getElementById('edit_ci').value = c.ci;
    document.getElementById('edit_celular').value = c.celular;
    document.getElementById('edit_domicilio').value = c.domicilio;
    document.getElementById('edit_referencia').value = c.referencia || '';
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function buscarCliente() {
    const input = document.getElementById('buscador').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaClientes tbody tr');
    filas.forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(input) ? '' : 'none';
    });
}
</script>
</body>
</html>
