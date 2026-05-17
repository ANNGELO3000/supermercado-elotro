<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

// ========== AGREGAR PRODUCTO ==========
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $id_categoria = $_POST['id_categoria'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $fecha_venc = $_POST['fecha_vencimiento'];
    $imagen = "default.png";

    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombre_img = uniqid('prod_') . '.' . $ext;
        $ruta = "imagenes/productos/" . $nombre_img;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
            $imagen = $nombre_img;
        }
    }

    $sql = "INSERT INTO productos (nombre, id_categoria, precio, stock, fecha_vencimiento, imagen) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidiss", $nombre, $id_categoria, $precio, $stock, $fecha_venc, $imagen);
    if ($stmt->execute()) {
        $mensaje = "Producto agregado correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar producto.";
        $tipo_mensaje = "danger";
    }
}

// ========== EDITAR PRODUCTO ==========
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $id_categoria = $_POST['id_categoria'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $fecha_venc = $_POST['fecha_vencimiento'];
    $imagen_actual = $_POST['imagen_actual'];

    $imagen = $imagen_actual;
    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombre_img = uniqid('prod_') . '.' . $ext;
        $ruta = "imagenes/productos/" . $nombre_img;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
            $imagen = $nombre_img;
        }
    }

    $sql = "UPDATE productos SET nombre=?, id_categoria=?, precio=?, stock=?, fecha_vencimiento=?, imagen=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $fecha_venc = !empty($fecha_venc) ? $fecha_venc : NULL;
    $stmt->bind_param("sidissi", $nombre, $id_categoria, $precio, $stock, $fecha_venc, $imagen, $id);
    if ($stmt->execute()) {
        $mensaje = "Producto actualizado correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar producto.";
        $tipo_mensaje = "danger";
    }
}

// ========== ELIMINAR PRODUCTO ==========
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql = "UPDATE productos SET estado = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = "Producto eliminado correctamente.";
        $tipo_mensaje = "success";
    }
}

// ========== OBTENER CATEGORIAS ==========
$categorias = $conn->query("SELECT * FROM categorias WHERE estado = 1");

// ========== OBTENER PRODUCTOS ==========
$filtro_cat = isset($_GET['categoria']) ? $_GET['categoria'] : '';
if ($filtro_cat) {
    $productos = $conn->query("SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.estado = 1 AND p.id_categoria = $filtro_cat ORDER BY p.id DESC");
} else {
    $productos = $conn->query("SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.estado = 1 ORDER BY p.id DESC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabla-productos { background: var(--gris); border-radius: 12px; overflow: hidden; }
        .tabla-productos table { margin: 0; }
        .tabla-productos thead th { background: #222; color: var(--amarillo); border-color: var(--gris2); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; }
        .tabla-productos tbody td { border-color: var(--gris2); color: var(--texto); padding: 12px 16px; vertical-align: middle; background: var(--gris); }
        .tabla-productos tbody tr:hover td { background: rgba(255,195,0,0.05); }
        .producto-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 2px solid var(--gris2); }
        .badge-cat { background: rgba(255,195,0,0.15); color: var(--amarillo); border: 1px solid rgba(255,195,0,0.3); font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 500; }
        .stock-bajo { color: var(--rojo); font-weight: 700; }
        .stock-ok { color: #28c76f; font-weight: 600; }
        .btn-agregar { background: var(--amarillo); color: var(--negro); border: none; font-weight: 700; padding: 10px 20px; border-radius: 8px; transition: all 0.2s; }
        .btn-agregar:hover { background: #e6b000; color: var(--negro); transform: translateY(-1px); }
        .modal-content { background: var(--negro2); border: 1px solid var(--amarillo); color: var(--texto); }
        .modal-header { border-bottom: 1px solid var(--gris2); }
        .modal-footer { border-top: 1px solid var(--gris2); }
        .modal-title { color: var(--amarillo); font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control, .form-select { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus, .form-select:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .form-select option { background: #111; }
        .btn-guardar { background: var(--amarillo); color: var(--negro); font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; }
        .btn-guardar:hover { background: #e6b000; color: var(--negro); }
        .filtros { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .btn-filtro { background: var(--gris); border: 1px solid var(--gris2); color: #aaa; border-radius: 20px; padding: 6px 16px; font-size: 13px; text-decoration: none; transition: all 0.2s; }
        .btn-filtro:hover, .btn-filtro.active { background: rgba(255,195,0,0.15); border-color: var(--amarillo); color: var(--amarillo); }
        .tabla-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
        .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--gris2); display: none; margin-top: 8px; }
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
        <a href="productos.php" class="nav-item active"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="clientes.php" class="nav-item"><i class="bi bi-people-fill"></i> Clientes</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
        <a href="proveedores.php" class="nav-item"><i class="bi bi-truck"></i> Proveedores</a>
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
            <span class="topbar-title">Productos</span>
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
            <div class="section-title" style="margin:0; flex:1;">Gestión de Productos</div>
            <button class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                <i class="bi bi-plus-lg"></i> Agregar Producto
            </button>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <a href="productos.php" class="btn-filtro <?php echo !$filtro_cat ? 'active' : ''; ?>">Todos</a>
            <?php
            $cats = $conn->query("SELECT * FROM categorias WHERE estado = 1");
            while ($cat = $cats->fetch_assoc()):
            ?>
            <a href="?categoria=<?php echo $cat['id']; ?>" class="btn-filtro <?php echo $filtro_cat == $cat['id'] ? 'active' : ''; ?>">
                <?php echo $cat['nombre']; ?>
            </a>
            <?php endwhile; ?>
        </div>

        <!-- Tabla -->
        <div class="tabla-productos">
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Vencimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        while ($p = $productos->fetch_assoc()):
                        $stock_class = $p['stock'] <= 5 ? 'stock-bajo' : 'stock-ok';
                        $img_src = !empty($p['imagen']) && file_exists("imagenes/productos/" . $p['imagen'])
                            ? "imagenes/productos/" . $p['imagen']
                            : "imagenes/default.png";
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><img src="<?php echo $img_src; ?>" class="producto-img" alt="<?php echo $p['nombre']; ?>"></td>
                            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                            <td><span class="badge-cat"><?php echo $p['categoria']; ?></span></td>
                            <td>Bs <?php echo number_format($p['precio'], 2); ?></td>
                            <td class="<?php echo $stock_class; ?>">
                                <?php echo $p['stock']; ?>
                                <?php if ($p['stock'] <= 5): ?><i class="bi bi-exclamation-triangle-fill ms-1"></i><?php endif; ?>
                            </td>
                            <td><?php echo $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '—'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-1"
                                    onclick="abrirEditar(<?php echo htmlspecialchars(json_encode($p)); ?>)"
                                    title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <a href="?eliminar=<?php echo $p['id']; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar este producto?')"
                                    title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($productos->num_rows === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No hay productos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ========== MODAL AGREGAR ========== -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Agregar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del producto</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Leche PIL" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="id_categoria" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $cats2 = $conn->query("SELECT * FROM categorias WHERE estado = 1");
                                while ($c = $cats2->fetch_assoc()):
                                ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Precio (Bs)</label>
                            <input type="number" step="0.01" name="precio" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" placeholder="0" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Fecha vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagen del producto</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewImagen(this, 'previewAgregar')">
                        <img id="previewAgregar" class="preview-img">
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="imagen_actual" id="edit_imagen_actual">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del producto</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="id_categoria" id="edit_categoria" class="form-select" required>
                                <?php
                                $cats3 = $conn->query("SELECT * FROM categorias WHERE estado = 1");
                                while ($c = $cats3->fetch_assoc()):
                                ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Precio (Bs)</label>
                            <input type="number" step="0.01" name="precio" id="edit_precio" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Fecha vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="edit_fecha" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cambiar imagen (opcional)</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewImagen(this, 'previewEditar')">
                        <img id="previewEditar" class="preview-img">
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

function abrirEditar(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_nombre').value = p.nombre;
    document.getElementById('edit_categoria').value = p.id_categoria;
    document.getElementById('edit_precio').value = p.precio;
    document.getElementById('edit_stock').value = p.stock;
    document.getElementById('edit_fecha').value = p.fecha_vencimiento || '';
    document.getElementById('edit_imagen_actual').value = p.imagen;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function previewImagen(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
