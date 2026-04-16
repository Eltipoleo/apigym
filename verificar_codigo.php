<?php
// gym-api/verificar_codigo.php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");

// 1. LEER DATOS CON ESCUDO PROTECTOR
$json = file_get_contents("php://input");
$data = json_decode($json);

// Validar que los datos existan antes de usarlos
if (!$data || !isset($data->email) || !isset($data->codigo)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos de verificación incompletos."]);
    exit;
}

$email = $data->email;
$codigo = $data->codigo;

// 2. BUSCAR USUARIO
$sql = "SELECT id FROM usuarios WHERE email = ? AND codigo_verificacion = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $email, $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 3. ACTUALIZAR ESTADO DE VERIFICACIÓN
    $update_sql = "UPDATE usuarios SET correo_verificado = 1, codigo_verificacion = NULL WHERE email = ?";
    $update_stmt = $conexion->prepare($update_sql);
    $update_stmt->bind_param("s", $email);
    
    if ($update_stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Cuenta verificada exitosamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al actualizar la cuenta en la base de datos."]);
    }
} else {
    // Código no coincide o usuario no existe
    echo json_encode(["success" => false, "message" => "El código es incorrecto o ya ha expirado."]);
}

exit; // Asegura que no se envíe nada más después del JSON