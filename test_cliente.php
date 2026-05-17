<?php
require_once 'conexion.php';
$r = $conn->query("SELECT * FROM clientes WHERE usuario='jmamani' AND contrasena='1234'");
echo "Encontrados: " . $r->num_rows;
?>