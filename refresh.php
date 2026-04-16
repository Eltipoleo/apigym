<?php
// gym-api/refresh.php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

include("db.php");
include("jwt_utils.php");

if (!isset($_COOKIE['refresh_token'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No hay refresh token"]);
    exit;
}

$refresh_token = $_COOKIE['refresh_token'];
$payload = verify_jwt($refresh_token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Refresh token inválido o expirado"]);
    exit;
}

$session_id = $payload['data']['session_id'];

// Verificar si la sesión no ha sido cerrada por el usuario
$stmt = $conexion->prepare("SELECT id FROM sesiones WHERE session_id = ?");
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión ha sido revocada remotamente"]);
    exit;
}

// Generar nuevo Access Token manteniendo el mismo session_id
$access_payload = [
    "iss" => "gym-api",
    "iat" => time(),
    "exp" => time() + (15 * 60),
    "data" => $payload['data'] 
];
$new_access_token = generate_jwt($access_payload);

echo json_encode(["success" => true, "access_token" => $new_access_token]);
?>