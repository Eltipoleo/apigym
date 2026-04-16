<?php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");
include("middleware.php");

$user = authenticate(); // Extrae los datos del token
$usuario_id = $user['id'];
$action = $_GET['action'] ?? '';

// 1. OBTENER CONFIGURACIÓN ACTUAL Y SESIONES
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_data') {
    // Obtener preferencias
    $sql = "SELECT mfa_enabled, tema, idioma FROM usuarios WHERE id = $usuario_id";
    $prefs = $conexion->query($sql)->fetch_assoc();

    // Obtener sesiones activas
    $sql_sesiones = "SELECT id, dispositivo, ip, fecha_inicio FROM sesiones WHERE usuario_id = $usuario_id ORDER BY fecha_inicio DESC";
    $sesiones_result = $conexion->query($sql_sesiones);
    $sesiones = [];
    while($row = $sesiones_result->fetch_assoc()) {
        $sesiones[] = $row;
    }

    echo json_encode(["success" => true, "prefs" => $prefs, "sesiones" => $sesiones]);
    exit;
}

// 2. ACTUALIZAR PREFERENCIAS Y MFA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_prefs') {
    $data = json_decode(file_get_contents("php://input"), true);
    $tema = $data['tema'];
    $idioma = $data['idioma'];
    $mfa = $data['mfa_enabled'] ? 1 : 0;

    $sql = "UPDATE usuarios SET tema=?, idioma=?, mfa_enabled=? WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssii", $tema, $idioma, $mfa, $usuario_id);
    
    echo json_encode(["success" => $stmt->execute()]);
    exit;
}

// 3. CAMBIAR CONTRASEÑA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
    $data = json_decode(file_get_contents("php://input"), true);
    $old_pass = $data['old_password'];
    $new_pass = $data['new_password'];

    // Verificar contraseña actual
    $sql = "SELECT password FROM usuarios WHERE id = $usuario_id";
    $current_pass = $conexion->query($sql)->fetch_assoc()['password'];

    if ($current_pass !== $old_pass) {
        echo json_encode(["success" => false, "message" => "La contraseña actual es incorrecta"]);
        exit;
    }

    $sql_update = "UPDATE usuarios SET password=? WHERE id=?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("si", $new_pass, $usuario_id);
    echo json_encode(["success" => $stmt->execute(), "message" => "Contraseña actualizada"]);
    exit;
}

// 4. CERRAR SESIÓN REMOTA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'close_session') {
    $data = json_decode(file_get_contents("php://input"), true);
    $sesion_id = $data['sesion_id'];

    $sql = "DELETE FROM sesiones WHERE id=? AND usuario_id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $sesion_id, $usuario_id);
    echo json_encode(["success" => $stmt->execute()]);
    exit;
}

echo json_encode(["success" => false, "message" => "Acción no válida"]);
?>