<?php
session_start();
require_once 'conexion.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    if (empty($usuario) || empty($contrasena)) {
        $error = "Por favor completa todos los campos.";
    } else {
        $sql = "SELECT * FROM usuarios WHERE usuario = ? AND contrasena = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $usuario, $contrasena);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $user = $resultado->fetch_assoc();
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['id'] = $user['id'];

            if ($user['rol'] == 'administrador') {
                header("Location: admin.php");
            } elseif ($user['rol'] == 'encargado_ventas') {
                header("Location: panel_ventas.php");
            } else {
                header("Location: inicio.php");
            }
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - El Otro</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS propio -->
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Logo / Nombre -->
        <div class="login-logo">
            <h1>🛒 El Otro</h1>
            <p>Supermercado Online</p>
        </div>

        <div class="login-title">Iniciar Sesión</div>

        <!-- Mensaje de error -->
        <?php if (!empty($error)): ?>
            <div class="error-msg">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="">

            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input 
                    type="text" 
                    name="usuario" 
                    class="form-control" 
                    placeholder="Ingresa tu usuario"
                    value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input 
                    type="password" 
                    name="contrasena" 
                    class="form-control" 
                    placeholder="Ingresa tu contraseña"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                INICIAR SESIÓN
            </button>

        </form>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
