<?php
// gym-api/verificar_codigo.php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");

$data = json_decode(file_get_contents("php://input"));
$email = $data->email;
$codigo = $data->codigo;

// Buscar usuario con ese email y código
$sql = "SELECT id FROM usuarios WHERE email = ? AND codigo_verificacion = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $email, $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Si coincide, lo verificamos y borramos el código
    $update_sql = "UPDATE usuarios SET correo_verificado = 1, codigo_verificacion = NULL WHERE email = ?";
    $update_stmt = $conexion->prepare($update_sql);
    $update_stmt->bind_param("s", $email);
    $update_stmt->execute();
    
    echo json_encode(["success" => true, "message" => "Cuenta verificada exitosamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Código incorrecto o inválido"]);
}
?>