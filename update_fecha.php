<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include("db.php");

$data = json_decode(file_get_contents("php://input"));

$id = $data->id;
$fecha = $data->fecha;

$sql = "UPDATE usuarios SET fecha_vencimiento = '$fecha' WHERE id = $id";

if ($conexion->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
?>
