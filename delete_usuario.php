<?php
// gym-api/delete_usuario.php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");
include("middleware.php");

// Debugging: Log Authorization header
$headers = getallheaders();
file_put_contents('debug.log', "Authorization Header: " . ($headers['Authorization'] ?? 'Not Provided') . "\n", FILE_APPEND);

// Debugging: Log token decoding
try {
    $user_token = authenticate();
    file_put_contents('debug.log', "Token Decoded: " . json_encode($user_token) . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents('debug.log', "Token Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token inválido o expirado."]);
    exit;
}

// Debugging: Log role validation
$stmt_rol = $conexion->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt_rol->bind_param("i", $user_token->id);
$stmt_rol->execute();
$user_db = $stmt_rol->get_result()->fetch_assoc();
file_put_contents('debug.log', "Role Validation: " . json_encode($user_db) . "\n", FILE_APPEND);

if (!$user_db || $user_db['rol'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autorizado. Solo los administradores pueden eliminar usuarios."]);
    exit;
}

// 3. Extraer el ID que React nos pide eliminar
$data = json_decode(file_get_contents("php://input"));
$id_a_eliminar = $data->id ?? null;

if (!$id_a_eliminar) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID no proporcionado"]);
    exit;
}

// (Opcional pero recomendado) Evitar que el admin se borre a sí mismo por accidente
if ($id_a_eliminar == $user_token->id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Operación denegada: No puedes eliminar tu propia cuenta de administrador."]);
    exit;
}

// 4. Proceder con la eliminación segura
$stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_a_eliminar);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Usuario eliminado con éxito."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error interno al eliminar el usuario."]);
}
?>