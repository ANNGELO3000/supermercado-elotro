<?php
session_start();
date_default_timezone_set('America/La_Paz');
require_once 'conexion.php';

// Login opcional
$logueado = isset($_SESSION['usuario']) && isset($_SESSION['rol']) && $_SESSION['rol'] == 'cliente';
$cliente = null;
if ($logueado) {
    $cliente = $conn->query("SELECT * FROM clientes WHERE usuario='{$_SESSION['usuario']}'")->fetch_assoc();
}

// Verificar horario
$horario = $conn->query("SELECT * FROM horario_atencion WHERE id=1")->fetch_assoc();
$hora_actual = date('H:i:s');
$dentro_horario = ($hora_actual >= $horario['hora_inicio'] && $hora_actual <= $horario['hora_fin'] && $horario['activo']);

// Filtro categoria
$filtro_cat = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$sql = "SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.estado=1 AND p.stock > 0";
if ($filtro_cat) $sql .= " AND p.id_categoria=$filtro_cat";
$sql .= " ORDER BY p.nombre ASC";
$productos  = $conn->query($sql);
$categorias = $conn->query("SELECT * FROM categorias WHERE estado=1");

// Carrito en sesión
if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
$total_carrito = count($_SESSION['carrito']);

// Agregar al carrito - requiere login
if (isset($_POST['agregar_carrito'])) {
    if (!$logueado) { header("Location: login_cliente.php"); exit(); }
    $id_producto   = $_POST['id_producto'];
    $producto_info = $conn->query("SELECT * FROM productos WHERE id=$id_producto AND stock > 0")->fetch_assoc();
    if ($producto_info) {
        if (isset($_SESSION['carrito'][$id_producto])) {
            $_SESSION['carrito'][$id_producto]['cantidad']++;
        } else {
            $_SESSION['carrito'][$id_producto] = [
                'id' => $id_producto, 'nombre' => $producto_info['nombre'],
                'precio' => $producto_info['precio'], 'cantidad' => 1
            ];
        }
    }
    header("Location: inicio_cliente.php" . ($filtro_cat ? "?categoria=$filtro_cat" : ""));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --amarillo: #FFC300; --rojo: #E63946; --negro: #1A1A1A; --negro2: #111; --gris: #2a2a2a; --gris2: #333; --texto: #e0e0e0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0f0f0f; color: var(--texto); font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .navbar-cliente { background: var(--negro2); border-bottom: 2px solid var(--amarillo); padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .navbar-brand { color: var(--amarillo); font-size: 22px; font-weight: 700; text-decoration: none; }
        .navbar-brand span { color: #fff; font-size: 13px; font-weight: 400; display: block; }
        .navbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-carrito { background: var(--amarillo); color: var(--negro); border: none; border-radius: 8px; padding: 8px 16px; font-weight: 700; font-size: 14px; text-decoration: none; }
        .btn-carrito:hover { background: #e6b000; color: var(--negro); }
        .carrito-badge { background: var(--rojo); color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; margin-left: 4px; }
        .btn-nav { background: transparent; border: 1px solid #444; color: #aaa; border-radius: 8px; padding: 8px 12px; font-size: 13px; text-decoration: none; }
        .btn-nav:hover { border-color: var(--amarillo); color: var(--amarillo); }
        .btn-login-nav { background: transparent; border: 1px solid var(--amarillo); color: var(--amarillo); border-radius: 8px; padding: 8px 14px; font-size: 13px; text-decoration: none; font-weight: 600; }
        .btn-login-nav:hover { background: var(--amarillo); color: var(--negro); }
        .contenido { padding: 24px; max-width: 1200px; margin: 0 auto; }
        .alerta-horario { background: rgba(230,57,70,0.1); border: 1px solid rgba(230,57,70,0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: var(--rojo); font-weight: 600; }
        .filtros { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .btn-filtro { background: var(--gris); border: 1px solid var(--gris2); color: #aaa; border-radius: 20px; padding: 6px 16px; font-size: 13px; text-decoration: none; transition: all 0.2s; }
        .btn-filtro:hover, .btn-filtro.active { background: rgba(255,195,0,0.15); border-color: var(--amarillo); color: var(--amarillo); }
        .productos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .producto-card { background: var(--gris); border: 1px solid var(--gris2); border-radius: 14px; overflow: hidden; transition: all 0.2s; }
        .producto-card:hover { transform: translateY(-3px); border-color: var(--amarillo); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        .producto-img { width: 100%; height: 160px; background: #222; display: flex; align-items: center; justify-content: center; font-size: 48px; }
        .producto-img img { width: 100%; height: 160px; object-fit: cover; }
        .producto-body { padding: 14px; }
        .producto-cat { font-size: 11px; color: var(--amarillo); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .producto-nombre { font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 4px; }
        .producto-precio { font-size: 18px; font-weight: 700; color: var(--amarillo); margin-bottom: 12px; }
        .producto-stock { font-size: 11px; color: #28c76f; margin-bottom: 10px; }
        .btn-agregar { background: var(--amarillo); color: var(--negro); border: none; border-radius: 8px; padding: 8px; width: 100%; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .btn-agregar:hover { background: #e6b000; }
        .btn-agregar:disabled { background: #444; color: #666; cursor: not-allowed; }
        .btn-login-card { background: transparent; border: 1px solid var(--amarillo); color: var(--amarillo); border-radius: 8px; padding: 8px; width: 100%; font-weight: 700; font-size: 13px; text-decoration: none; display: block; text-align: center; transition: all 0.2s; }
        .btn-login-card:hover { background: var(--amarillo); color: var(--negro); }
        @media (max-width: 768px) { .contenido { padding: 16px; } .productos-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
        @media (max-width: 400px) { .productos-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav class="navbar-cliente">
    <a href="inicio_cliente.php" class="navbar-brand">🛒 El Otro<span>Supermercado Online</span></a>
    <div class="navbar-right">
        <?php if ($logueado): ?>
            <span style="color:#aaa; font-size:13px;">👋 <?php echo htmlspecialchars($cliente['nombres']); ?></span>
            <a href="mis_pedidos.php" class="btn-nav"><i class="bi bi-bag me-1"></i>Mis Pedidos</a>
            <a href="carrito.php" class="btn-carrito">
                <i class="bi bi-cart-fill"></i> Carrito
                <?php if ($total_carrito > 0): ?><span class="carrito-badge"><?php echo $total_carrito; ?></span><?php endif; ?>
            </a>
            <a href="logout.php" class="btn-nav"><i class="bi bi-box-arrow-right"></i></a>
        <?php else: ?>
            <a href="carrito.php" class="btn-carrito">
                <i class="bi bi-cart-fill"></i> Carrito
                <?php if ($total_carrito > 0): ?><span class="carrito-badge"><?php echo $total_carrito; ?></span><?php endif; ?>
            </a>
            <a href="login_cliente.php" class="btn-login-nav"><i class="bi bi-person-fill me-1"></i>Ingresar</a>
        <?php endif; ?>
    </div>
</nav>

<div class="contenido">

    <?php if (!$dentro_horario): ?>
    <div class="alerta-horario">
        <i class="bi bi-clock-fill" style="font-size:20px;"></i>
        <div>
            <strong>Fuera del horario de atención</strong><br>
            <span style="font-size:13px; font-weight:400;">Atendemos de <?php echo date('H:i', strtotime($horario['hora_inicio'])); ?> a <?php echo date('H:i', strtotime($horario['hora_fin'])); ?>.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="filtros">
        <a href="inicio_cliente.php" class="btn-filtro <?php echo !$filtro_cat ? 'active' : ''; ?>">Todos</a>
        <?php while ($c = $categorias->fetch_assoc()): ?>
        <a href="?categoria=<?php echo $c['id']; ?>" class="btn-filtro <?php echo $filtro_cat == $c['id'] ? 'active' : ''; ?>"><?php echo $c['nombre']; ?></a>
        <?php endwhile; ?>
    </div>

    <div class="productos-grid">
        <?php while ($p = $productos->fetch_assoc()):
        $img_src = !empty($p['imagen']) && file_exists("imagenes/productos/" . $p['imagen']) ? "imagenes/productos/" . $p['imagen'] : null;
        ?>
        <div class="producto-card">
            <div class="producto-img">
                <?php if ($img_src): ?><img src="<?php echo $img_src; ?>" alt="<?php echo $p['nombre']; ?>"><?php else: ?>🛍️<?php endif; ?>
            </div>
            <div class="producto-body">
                <div class="producto-cat"><?php echo $p['categoria']; ?></div>
                <div class="producto-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                <div class="producto-precio">Bs <?php echo number_format($p['precio'], 2); ?></div>
                <div class="producto-stock"><i class="bi bi-check-circle-fill me-1"></i>Stock: <?php echo $p['stock']; ?></div>

                <?php if (!$dentro_horario): ?>
                    <button class="btn-agregar" disabled>Fuera de horario</button>
                <?php elseif ($logueado): ?>
                    <form method="POST">
                        <input type="hidden" name="id_producto" value="<?php echo $p['id']; ?>">
                        <button type="submit" name="agregar_carrito" class="btn-agregar"><i class="bi bi-cart-plus me-1"></i>Agregar al carrito</button>
                    </form>
                <?php else: ?>
                    <a href="login_cliente.php" class="btn-login-nav"><i class="bi bi-person-fill me-1"></i>Ingresar</a>
<a href="login.php" class="btn-nav" title="Acceso administrativo"><i class="bi bi-shield-lock-fill"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
