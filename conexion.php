<?php
$conn = new mysqli('mysql.railway.internal', 'root', 'bGXMLUEcTYTxWGJySgNVJVirwcDmaTOP', 'railway', 3306);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>