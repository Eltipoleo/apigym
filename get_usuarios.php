<?php


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include("db.php");

$sql = "SELECT id, nombre, email, fecha_vencimiento, rol FROM usuarios";
$result = $conexion->query($sql);

$usuarios = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

echo json_encode($usuarios);
?>
