<?php
session_start();
require_once 'conexion.php';

$error = "";
$exito_registro = "";
$error_registro = "";

// ========== LOGIN ==========
if (isset($_POST['login'])) {
    $usuario    = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    if (empty($usuario) || empty($contrasena)) {
        $error = "Por favor completa todos los campos.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE usuario = ? AND contrasena = ? AND estado = 1 LIMIT 1");
        $stmt->bind_param("ss", $usuario, $contrasena);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $cliente = $resultado->fetch_assoc();
            $_SESSION['usuario'] = $cliente['usuario'];
            $_SESSION['rol']     = 'cliente';
            $_SESSION['id']      = $cliente['id'];
            header("Location: inicio_cliente.php");
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}

// ========== REGISTRO ==========
if (isset($_POST['registrar'])) {
    $nombres    = trim($_POST['nombres']);
    $apellidos  = trim($_POST['apellidos']);
    $ci         = trim($_POST['ci']);
    $celular    = trim($_POST['celular']);
    $domicilio  = trim($_POST['domicilio']);
    $referencia = trim($_POST['referencia']);
    $usuario    = trim($_POST['reg_usuario']);
    $contrasena = trim($_POST['reg_contrasena']);

    $check = $conn->prepare("SELECT id FROM clientes WHERE usuario = ?");
    $check->bind_param("s", $usuario);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error_registro = "Ese nombre de usuario ya está en uso.";
    } else {
        $stmt = $conn->prepare("INSERT INTO clientes (nombres, apellidos, ci, domicilio, referencia, celular, usuario, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $nombres, $apellidos, $ci, $domicilio, $referencia, $celular, $usuario, $contrasena);
        if ($stmt->execute()) {
            $exito_registro = "Cuenta creada correctamente. Ya puedes ingresar.";
        } else {
            $error_registro = "Error al registrar. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar - El Otro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <style>
        .btn-registrarse { background: transparent; border: 2px solid #FFC300; color: #FFC300; border-radius: 8px; padding: 11px; font-size: 15px; font-weight: 700; width: 100%; margin-top: 10px; cursor: pointer; transition: all 0.3s; }
        .btn-registrarse:hover { background: #FFC300; color: #111; }
        .modal-content { background: #1a1a1a; border: 1px solid #FFC300; color: #e0e0e0; }
        .modal-header { border-bottom: 1px solid #333; }
        .modal-footer { border-top: 1px solid #333; }
        .modal-title { color: #FFC300; font-weight: 700; }
        .form-label { color: #ccc; font-size: 13px; }
        .form-control { background: #111 !important; border: 1px solid #444 !important; color: #fff !important; border-radius: 8px !important; }
        .form-control:focus { border-color: #FFC300 !important; box-shadow: 0 0 0 3px rgba(255,195,0,0.15) !important; }
        .form-control::placeholder { color: #555 !important; }
        .btn-guardar { background: #FFC300; color: #111; font-weight: 700; border: none; border-radius: 8px; padding: 10px 24px; }
        .btn-guardar:hover { background: #e6b000; color: #111; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <h1>🛒 El Otro</h1>
            <p>Supermercado Online</p>
        </div>
        <div class="login-title">Ingresar como Cliente</div>

        <?php if (!empty($error)): ?>
        <div class="error-msg">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($exito_registro)): ?>
        <div style="background:rgba(40,199,111,0.1);border:1px solid #28c76f;color:#28c76f;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px;text-align:center;">
            ✅ <?php echo $exito_registro; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="Tu usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="contrasena" class="form-control" placeholder="Tu contraseña" required>
            </div>
            <button type="submit" name="login" class="btn-login">INGRESAR</button>
            <button type="button" class="btn-registrarse" data-bs-toggle="modal" data-bs-target="#modalRegistro">
                REGISTRARSE
            </button>
        </form>

        <div class="login-footer" style="margin-top:16px;">
            ¿Eres del equipo? <a href="login.php">Acceso administrativo</a>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO -->
<div class="modal fade" id="modalRegistro" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if (!empty($error_registro)): ?>
                    <div class="alert alert-danger" style="border-radius:8px;font-size:13px;">⚠️ <?php echo $error_registro; ?></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombres" class="form-control" placeholder="Ej: Angel Osriel" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" placeholder="Ej: Herrera Tola" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">CI</label>
                            <input type="text" name="ci" class="form-control" placeholder="Ej: 9908865" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Celular</label>
                            <input type="text" name="celular" class="form-control" placeholder="Ej: 77279442" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domicilio</label>
                        <input type="text" name="domicilio" class="form-control" placeholder="Ej: Calle 4 de Mayo #1061, Av Buenos Aires" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referencia <span style="color:#666;font-size:12px;">(opcional)</span></label>
                        <input type="text" name="referencia" class="form-control" placeholder="Ej: Subiendo cuadra y media">
                    </div>
                    <hr style="border-color:#333;">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="reg_usuario" class="form-control" placeholder="Ej: aherrera" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="reg_contrasena" class="form-control" placeholder="Mínimo 6 caracteres" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar" class="btn-guardar btn">
                        <i class="bi bi-check-lg me-1"></i> Crear Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($error_registro)): ?>
<script>
    new bootstrap.Modal(document.getElementById('modalRegistro')).show();
</script>
<?php endif; ?>
</body>
</html>
