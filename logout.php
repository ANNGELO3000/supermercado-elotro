<?php
session_start();
$destino = isset($_SESSION['rol']) && $_SESSION['rol'] === 'cliente' ? 'login_cliente.php' : 'login.php';
session_destroy();
header("Location: " . $destino);
exit();
?>
