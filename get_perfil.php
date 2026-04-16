<?php
// gym-api/get_perfil.php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");

// Recibimos el ID por la URL (ej: get_perfil&id=5)
$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(["success" => false, "message" => "ID de usuario no proporcionado"]);
    exit;
}

$sql = "SELECT id, nombre, email, rol, fecha_vencimiento, correo_verificado FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Enviamos los datos como JSON
    echo json_encode($user);
} else {
    echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
}
exit;