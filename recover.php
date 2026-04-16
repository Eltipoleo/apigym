<?php
// gym-api/recover.php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';
$email = $data->email ?? '';

if (!$email) {
    echo json_encode(["success" => false, "message" => "Correo requerido"]); exit;
}

// 1. OBTENER Y VALIDAR USUARIO (Prevenir Fuerza Bruta)
$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Si el correo existe, se enviará la recuperación."]); exit;
}

if ($user['bloqueo_hasta'] && strtotime($user['bloqueo_hasta']) > time()) {
    echo json_encode(["success" => false, "message" => "Demasiados intentos. Cuenta bloqueada temporalmente."]); exit;
}

// Función auxiliar para registrar intento fallido
function registrarFallo($conexion, $user) {
    $intentos = $user['intentos_recuperacion'] + 1;
    $bloqueo = ($intentos >= 3) ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
    $stmt = $conexion->prepare("UPDATE usuarios SET intentos_recuperacion = ?, bloqueo_hasta = ? WHERE id = ?");
    $stmt->bind_param("isi", $intentos, $bloqueo, $user['id']);
    $stmt->execute();
    return $intentos >= 3 ? "Cuenta bloqueada por 15 minutos." : "Respuesta/Código incorrecto. Intentos: $intentos/3";
}

// ==========================================
// ACCIÓN: SOLICITAR ENLACE POR CORREO
// ==========================================
if ($action === 'request_email') {
    $token = bin2hex(random_bytes(32)); // Token único seguro
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expira en 1 hora

    $stmt = $conexion->prepare("UPDATE usuarios SET recovery_token = ?, recovery_expires = ? WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expires, $user['id']);
    $stmt->execute();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'TU_CORREO@gmail.com'; // CAMBIA ESTO
        $mail->Password = 'TU_CONTRASEÑA_DE_APP'; // CAMBIA ESTO
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('TU_CORREO@gmail.com', 'Gym System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Recuperacion de Contrasena';
        
        $link = "http://localhost:5173/recuperar?token=$token&email=$email";
        $mail->Body = "Haz clic en el enlace para cambiar tu contraseña: <br><a href='$link'>$link</a> <br><br>Este enlace expira en 1 hora.";
        $mail->send();
        echo json_encode(["success" => true, "message" => "Enlace enviado al correo."]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error al enviar correo."]);
    }
    exit;
}

// ==========================================
// ACCIÓN: SOLICITAR SMS O LLAMADA (REAL)
// ==========================================
if ($action === 'request_otp') {
    // Generar código de 6 dígitos
    $otp = rand(100000, 999999);
    // Cifrarlo para la base de datos (seguridad estricta)
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT); 
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes')); 

    // Almacenar en la base de datos
    $stmt = $conexion->prepare("UPDATE usuarios SET otp_code = ?, otp_expires = ? WHERE id = ?");
    $stmt->bind_param("ssi", $otp_hash, $expires, $user['id']);
    $stmt->execute();

    // =========================================================
    // AQUÍ ES DONDE SE CONECTARÍA UNA API DE SMS COMO "TWILIO"
    // Ejemplo: $twilio->messages->create($telefono, ['from' => 'GYM', 'body' => "Tu codigo es $otp"]);
    // =========================================================

    $metodo = $data->method === 'call' ? "Llamada de voz" : "SMS";
    
    // Ya NO devolvemos el código al frontend por seguridad.
    echo json_encode([
        "success" => true, 
        "message" => "$metodo procesado. Revisa tu dispositivo."
    ]);
    exit;
}

// ==========================================
// ACCIÓN: EJECUTAR EL CAMBIO DE CONTRASEÑA
// ==========================================
if ($action === 'reset_password') {
    $new_password = $data->new_password;
    $method = $data->method; // 'email', 'question', 'otp'
    $auth_value = $data->auth_value; // El token, la respuesta secreta o el OTP

    $autorizado = false;

    if ($method === 'email') {
        if ($user['recovery_token'] === $auth_value && strtotime($user['recovery_expires']) > time()) {
            $autorizado = true;
        }
    } elseif ($method === 'question') {
        if (password_verify(strtolower(trim($auth_value)), $user['respuesta_secreta'])) {
            $autorizado = true;
        }
    } elseif ($method === 'otp') {
        if (password_verify($auth_value, $user['otp_code']) && strtotime($user['otp_expires']) > time()) {
            $autorizado = true;
        }
    }

    if ($autorizado) {
        // ACTUALIZAR CONTRASEÑA Y LIMPIAR TOKENS/INTENTOS
        $stmt = $conexion->prepare("UPDATE usuarios SET password = ?, recovery_token = NULL, otp_code = NULL, intentos_recuperacion = 0, bloqueo_hasta = NULL WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user['id']);
        $stmt->execute();
        
        echo json_encode(["success" => true, "message" => "Contraseña actualizada con éxito."]);
    } else {
        $msg = registrarFallo($conexion, $user);
        echo json_encode(["success" => false, "message" => "Validación fallida o expirada. $msg"]);
    }
    exit;
}
?>