<?php
// gym-api/db.php
$conexion = new mysqli("localhost", "root", "", "gym");

if ($conexion->connect_error) {
    die("Error de conexion");
}
?>