<?php
$host = "127.0.0.1";
$usuario = "root";
$contrasena = "root";
$base_datos = "elotro";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos, 3307);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>