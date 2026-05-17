<?php
$conn = new mysqli("127.0.0.1", "root", "", "elotro", 3306);
$r = $conn->query("SELECT * FROM productos");
echo "Sin filtro: " . $r->num_rows . "<br>";

$r2 = $conn->query("SELECT * FROM productos WHERE estado = 1");
echo "Con filtro estado=1: " . $r2->num_rows . "<br>";

$r3 = $conn->query("SELECT DATABASE()");
echo "BD activa: " . $r3->fetch_row()[0] . "<br>";
?>