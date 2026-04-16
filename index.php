<?php
// Activa errores para que NO salga el Error 500 genérico y nos diga el fallo real
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// Reemplaza la lógica anterior por esta:
$request = '';

if (isset($_GET['url'])) {
    // Si viene por el parámetro ?url=
    $request = is_array($_GET['url']) ? $_GET['url'] : $_GET['url'];
} else {
    // Si viene por la URL limpia (/usuarios)
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    $request = $parts ?? '';
}

$request = rtrim($request, '/');

switch ($request) {
    case 'usuarios':
        require 'get_usuarios.php';
        break;
    case 'login':
        require 'login.php';
        break;
    case 'registro':
        require 'register.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Ruta no encontrada", "ruta_solicitada" => $request]);
        break;
}