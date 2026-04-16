<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");

// Cargar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// 1. LEER DATOS CON ESCUDO PROTECTOR
$json = file_get_contents("php://input");
$data = json_decode($json);

// Validar que realmente llegaron los datos
if (!$data || !isset($data->nombre) || !isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Faltan datos para el registro."]);
    exit;
}

$nombre = $data->nombre;
$email = $data->email;
$password = $data->password;

$rol = 'usuario';
$fecha_vencimiento = date('Y-m-d'); 
$codigo_verificacion = rand(100000, 999999); // Genera un código de 6 dígitos

// 2. Verificar si el correo ya existe
$check_sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt_check = $conexion->prepare($check_sql);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Este correo ya está registrado"]);
    exit;
}

// 3. Insertar usuario no verificado con el código
$sql = "INSERT INTO usuarios (nombre, email, password, rol, fecha_vencimiento, codigo_verificacion, correo_verificado) VALUES (?, ?, ?, ?, ?, ?, 0)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $email, $password, $rol, $fecha_vencimiento, $codigo_verificacion);

if ($stmt->execute()) {
    // 4. Configurar PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joserty83@gmail.com'; 
        $mail->Password   = 'gkucqvjuzuqoxsqf'; // Tu nueva App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8'; // Para soporte de acentos y eñes

        // AGREGAR ESTO: Tiempos de espera cortos para que no trabe el server
        $mail->Timeout       = 10; // 10 segundos máximo
        $mail->SMTPKeepAlive = false;

        // Remitente y Destinatario
        $mail->setFrom('joserty83@gmail.com', 'Gym System');
        $mail->addAddress($email, $nombre);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificación - Gym System';
        $mail->Body    = "
            <div style='font-family: sans-serif; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #333;'>Hola $nombre,</h2>
                <p>Tu código de verificación para activar tu cuenta es:</p>
                <div style='background: #f8f9fa; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #007bff; border-radius: 5px;'>
                    $codigo_verificacion
                </div>
                <p style='margin-top: 20px; color: #666;'>Ingrésalo en la aplicación para completar tu registro.</p>
            </div>";

        // ENVÍO REAL DEL CORREO
        $mail->send(); 

        echo json_encode(["success" => true, "message" => "Cuenta creada. Revisa tu correo electrónico."]);
        exit;

    } catch (Exception $e) {
        // Si falla el envío por algo técnico (puertos, dns), avisamos pero el usuario ya existe en DB
        echo json_encode([
            "success" => true, 
            "message" => "Cuenta creada, pero hubo un error enviando el correo. Contacta a soporte.",
            "debug" => $mail->ErrorInfo
        ]);
        exit;
    }
} else {
    echo json_encode(["success" => false, "message" => "Error al registrar en la base de datos"]);
    exit;
}
?>