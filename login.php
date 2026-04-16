<?php
// gym-api/login.php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");
include("jwt_utils.php");

// 1. LEER DATOS CON ESCUDO PROTECTOR
$json = file_get_contents("php://input");
$data = json_decode($json);

// Validar que realmente llegaron el email y el password
if (!$data || !isset($data->email) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Faltan datos de inicio de sesión."]);
    exit;
}

$email = $data->email;
$password = $data->password;

// 2. OBTENER USUARIO POR EMAIL
$sql = "SELECT * FROM usuarios WHERE email=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // 3. PREVENCIÓN DE FUERZA BRUTA (Bloqueo temporal)
    if ($user['bloqueo_hasta'] && strtotime($user['bloqueo_hasta']) > time()) {
        http_response_code(429); // Too Many Requests
        echo json_encode(["success" => false, "message" => "Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta más tarde."]);
        exit;
    }

    // 4. VALIDAR CONTRASEÑA
    if ($user['password'] === $password) { // Nota: En un entorno 100% real se usa password_verify()
        
        // BLOQUEO DE SESIONES MÚLTIPLES (Destruir sesiones anteriores)
        $del_stmt = $conexion->prepare("DELETE FROM sesiones WHERE usuario_id = ?");
        $del_stmt->bind_param("i", $user['id']);
        $del_stmt->execute();

        // Limpiar intentos fallidos
        $conexion->query("UPDATE usuarios SET intentos_fallidos = 0, bloqueo_hasta = NULL WHERE id = " . $user['id']);

        $session_id = bin2hex(random_bytes(16)); 

        $access_payload = ["iss" => "gym-api", "iat" => time(), "exp" => time() + (15 * 60), "data" => ["id" => $user['id'], "rol" => $user['rol'], "session_id" => $session_id]];
        $access_token = generate_jwt($access_payload);

        $refresh_payload = ["iss" => "gym-api", "iat" => time(), "exp" => time() + (7 * 24 * 60 * 60), "data" => ["id" => $user['id'], "session_id" => $session_id]];
        $refresh_token = generate_jwt($refresh_payload);

        $dispositivo = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $token_hash = hash('sha256', $refresh_token); 

        $sql_sesion = "INSERT INTO sesiones (usuario_id, session_id, token_hash, dispositivo, ip) VALUES (?, ?, ?, ?, ?)";
        $stmt_sesion = $conexion->prepare($sql_sesion);
        $stmt_sesion->bind_param("issss", $user['id'], $session_id, $token_hash, $dispositivo, $ip);
        $stmt_sesion->execute();

        // 5. COOKIES PARA PRODUCCIÓN (¡Muy importante para Railway/Vercel!)
        setcookie("refresh_token", $refresh_token, [
            'expires' => time() + (7*24*60*60), 
            'path' => '/', 
            'secure' => true,       // Obligatorio en la nube (HTTPS)
            'httponly' => true, 
            'samesite' => 'None'    // Permite que dominios diferentes compartan la cookie
        ]);

        echo json_encode(["success" => true, "access_token" => $access_token, "id" => $user['id'], "rol" => $user['rol'], "nombre" => $user['nombre'], "email" => $user['email']]);
    } else {
        // CONTRASEÑA INCORRECTA - Aumentar intentos
        $intentos = $user['intentos_fallidos'] + 1;
        $bloqueo = ($intentos >= 3) ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
        
        $upd_stmt = $conexion->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueo_hasta = ? WHERE id = ?");
        $upd_stmt->bind_param("isi", $intentos, $bloqueo, $user['id']);
        $upd_stmt->execute();

        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Credenciales incorrectas. Intento $intentos de 3."]);
    }
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas."]);
}
?>