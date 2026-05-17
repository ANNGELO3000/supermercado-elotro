<?php
session_start();
date_default_timezone_set('America/La_Paz');
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

// ========== ACTUALIZAR HORARIO ==========
if (isset($_POST['actualizar'])) {
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin    = $_POST['hora_fin'];
    $activo      = isset($_POST['activo']) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE horario_atencion SET hora_inicio=?, hora_fin=?, activo=? WHERE id=1");
    $stmt->bind_param("ssi", $hora_inicio, $hora_fin, $activo);
    if ($stmt->execute()) { $mensaje = "Horario actualizado correctamente."; $tipo_mensaje = "success"; }
    else { $mensaje = "Error al actualizar horario."; $tipo_mensaje = "danger"; }
}

$horario = $conn->query("SELECT * FROM horario_atencion WHERE id=1")->fetch_assoc();
$hora_actual = date('H:i:s');
$dentro_horario = ($hora_actual >= $horario['hora_inicio'] && $hora_actual <= $horario['hora_fin'] && $horario['activo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .horario-card { background: var(--gris); border-radius: 16px; padding: 32px; border: 1px solid var(--gris2); max-width: 500px; }
        .horario-status { border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .horario-status.abierto { background: rgba(40,199,111,0.1); border: 1px solid rgba(40,199,111,0.3); }
        .horario-status.cerrado { background: rgba(230,57,70,0.1); border: 1px solid rgba(230,57,70,0.3); }
        .status-icon { font-size: 28px; }
        .status-text h5 { margin: 0; font-weight: 700; }
        .status-text p { margin: 0; font-size: 13px; color: #888; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; padding: 10px 14px !important; }
        .form-control:focus { border-color: var(--amarillo) !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .btn-guardar { background: var(--amarillo); color: var(--negro); font-weight: 700; border: none; border-radius: 8px; padding: 12px 28px; width: 100%; font-size: 15px; }
        .btn-guardar:hover { background: #e6b000; color: var(--negro); }
        .hora-actual { text-align: center; padding: 16px; background: var(--negro2); border-radius: 12px; margin-bottom: 24px; border: 1px solid var(--gris2); }
        .hora-actual h2 { color: var(--amarillo); font-size: 36px; font-weight: 700; margin: 0; font-family: monospace; }
        .hora-actual p { color: #888; font-size: 13px; margin: 4px 0 0; }
        .form-check-input:checked { background-color: var(--amarillo); border-color: var(--amarillo); }
        .form-check-label { color: #ccc; }
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
        <a href="reportes.php" class="nav-item"><i class="bi bi-bar-chart-fill"></i> Reportes</a>
        <a href="horario.php" class="nav-item active"><i class="bi bi-clock-fill"></i> Horario</a>
        <a href="usuarios.php" class="nav-item"><i class="bi bi-person-gear"></i> Usuarios</a>
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
            <span class="topbar-title">Horario de Atención</span>
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

        <div class="row justify-content-center">
            <div class="col-md-6">

                <!-- Hora actual -->
                <div class="hora-actual">
                    <h2 id="reloj"><?php echo date('H:i:s'); ?></h2>
                    <p><?php echo date('l, d \d\e F \d\e Y'); ?></p>
                </div>

                <!-- Estado actual -->
                <div class="horario-status <?php echo $dentro_horario ? 'abierto' : 'cerrado'; ?>">
                    <div class="status-icon"><?php echo $dentro_horario ? '🟢' : '🔴'; ?></div>
                    <div class="status-text">
                        <h5 style="color:<?php echo $dentro_horario ? '#28c76f' : '#E63946'; ?>">
                            <?php echo $dentro_horario ? 'Supermercado ABIERTO' : 'Supermercado CERRADO'; ?>
                        </h5>
                        <p>Horario: <?php echo date('H:i', strtotime($horario['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($horario['hora_fin'])); ?></p>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="horario-card">
                    <h5 style="color:var(--amarillo); margin-bottom:20px; font-weight:700;">
                        <i class="bi bi-pencil-fill me-2"></i>Configurar Horario
                    </h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Hora de apertura</label>
                            <input type="time" name="hora_inicio" class="form-control" value="<?php echo $horario['hora_inicio']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora de cierre</label>
                            <input type="time" name="hora_fin" class="form-control" value="<?php echo $horario['hora_fin']; ?>" required>
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo $horario['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Atención activa</label>
                            </div>
                            <small style="color:#666;">Si desactivas la atención, los clientes no podrán realizar pedidos.</small>
                        </div>
                        <button type="submit" name="actualizar" class="btn-guardar btn">
                            <i class="bi bi-check-lg me-2"></i>Guardar Horario
                        </button>
                    </form>
                </div>

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
// Reloj sincronizado con el servidor
let serverTime = new Date();
serverTime.setHours(<?php echo date('H'); ?>, <?php echo date('i'); ?>, <?php echo date('s'); ?>);

function actualizarReloj() {
    serverTime.setSeconds(serverTime.getSeconds() + 1);
    const h = String(serverTime.getHours()).padStart(2, '0');
    const m = String(serverTime.getMinutes()).padStart(2, '0');
    const s = String(serverTime.getSeconds()).padStart(2, '0');
    document.getElementById('reloj').textContent = `${h}:${m}:${s}`;
}
setInterval(actualizarReloj, 1000);
</script>
</body>
</html>
