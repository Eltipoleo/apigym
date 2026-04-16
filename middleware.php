<?php
// gym-api/middleware.php
include_once("jwt_utils.php");
// Necesitamos conexión a BD para verificar si la sesión sigue viva
include_once("db.php"); 

function authenticate() {
    global $conexion;
    
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Token no proporcionado"]);
        exit;
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);
    
    $payload = verify_jwt($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Token inválido o expirado"]);
        exit;
    }
    
    $data = $payload['data'];
    $session_id = $data['session_id'] ?? '';

    // === NUEVO: Verificación en Base de Datos (Invalidar Revocados) ===
    $stmt = $conexion->prepare("SELECT id FROM sesiones WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Sesión revocada o finalizada"]);
        exit; 
    }
    // =================================================================

    return $data; 
}

function authorize($allowed_roles = []) {
    $user = authenticate(); 
    
    if (!empty($allowed_roles) && !in_array($user['rol'], $allowed_roles)) {
        http_response_code(403); 
        echo json_encode(["success" => false, "error" => "Acceso denegado: Permisos insuficientes"]);
        exit;
    }
    
    return $user; 
}
?>