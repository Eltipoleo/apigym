<?php
// Activa errores para que NO salga el Error 500 genérico y nos diga el fallo real
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- NUEVOS HEADERS PARA PRODUCCIÓN Y COOKIES ---

// 1. Detectar de qué URL viene la petición (tu frontend)
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';

// 2. Dar permiso específico a ese origen en lugar de usar '*'
if ($origen) {
    header("Access-Control-Allow-Origin: $origen");
} else {
    header("Access-Control-Allow-Origin: *"); // Respaldo por si acaso
}

// 3. ¡LA LÍNEA MÁGICA! Permitir el envío de cookies y tokens
header("Access-Control-Allow-Credentials: true");

// 4. Métodos y encabezados permitidos
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 5. Manejar la petición "Preflight" (OPTIONS) que hace el navegador antes del POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// --- FIN DE LOS HEADERS ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// Reemplaza la lógica anterior por esta:
// --- REEMPLAZA DESDE AQUÍ ---
$request = $_GET['url'] ?? '';

// Si por alguna razón recibimos un array, tomamos el primer elemento
if (is_array($request)) {
    $request = $request ?? '';
}

$request = rtrim((string)$request, '/');
// --- HASTA AQUÍ ---

// ... dentro de tu index.php ...
$url = $_GET['url'] ?? '';

switch ($url) {
    case 'login':
        include 'login.php';
        break;
    case 'registro':
        include 'register.php';
        break;
    case 'verificar_codigo': // ESTO ES LO QUE FALTA
        include 'verificar_codigo.php'; 
        break;
    // ... otros casos ...
    default:
        echo json_encode(["message" => "Ruta no encontrada", "ruta_solicitada" => $url]);
        break;
}