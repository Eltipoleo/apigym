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

include("middleware.php");


$user = authorize(['admin', 'editor']); 



$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"];
$nombre = $data["nombre"];
$email = $data["email"];
$fecha = $data["fecha_vencimiento"];

$sql = "UPDATE usuarios SET nombre=?, email=?, fecha_vencimiento=? WHERE id=?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssi", $nombre, $email, $fecha, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
?>
