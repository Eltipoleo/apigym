<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data["nombre"] ?? "";
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";
$fecha_vencimiento = $data["fecha_vencimiento"] ?? "";

if (!$nombre || !$email || !$fecha_vencimiento) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

$stmt = $conexion->prepare(
    "INSERT INTO usuarios (nombre, email, password, fecha_vencimiento, rol)
     VALUES (?, ?, ?, ?, 'usuario')"
);

$stmt->bind_param("ssss", $nombre, $email, $password, $fecha_vencimiento);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => $stmt->error
    ]);
}
?>
