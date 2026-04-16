<?php
// gym-api/register.php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");

// Cargar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$data = json_decode(file_get_contents("php://input"));
$nombre = $data->nombre;
$email = $data->email;
$password = $data->password;

$rol = 'usuario';
$fecha_vencimiento = date('Y-m-d'); 
$codigo_verificacion = rand(100000, 999999); // Genera un código de 6 dígitos

// 1. Verificar si el correo ya existe
$check_sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt_check = $conexion->prepare($check_sql);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Este correo ya está registrado"]);
    exit;
}

// 2. Insertar usuario no verificado con el código
$sql = "INSERT INTO usuarios (nombre, email, password, rol, fecha_vencimiento, codigo_verificacion, correo_verificado) VALUES (?, ?, ?, ?, ?, ?, 0)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $email, $password, $rol, $fecha_vencimiento, $codigo_verificacion);

if ($stmt->execute()) {
    // 3. Enviar el correo con PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joserty83@gmail.com'; // PON TU CORREO AQUÍ
        $mail->Password   = 'wcdjizplhwpfqohk'; // PON TU APP PASSWORD AQUÍ
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y Destinatario
        $mail->setFrom('joserty83@gmail.com', 'Gym');
        $mail->addAddress($email, $nombre);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Codigo de Verificacion - Gym System';
        $mail->Body    = "Hola <b>$nombre</b>,<br><br>Tu código de verificación es: <h2>$codigo_verificacion</h2><br>Ingrésalo en la aplicación para activar tu cuenta.";

        $mail->send();
        echo json_encode(["success" => true, "message" => "Cuenta creada. Revisa tu correo electrónico."]);
    } catch (Exception $e) {
        // Si falla el correo, igual se creó la cuenta, pero informamos del error
        echo json_encode(["success" => true, "message" => "Cuenta creada, pero el correo no se pudo enviar. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Error al registrar en la base de datos"]);
}
?>