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
// --- REEMPLAZA DESDE AQUÍ ---
$request = $_GET['url'] ?? '';

// Si por alguna razón recibimos un array, tomamos el primer elemento
if (is_array($request)) {
    $request = $request ?? '';
}

$request = rtrim((string)$request, '/');
// --- HASTA AQUÍ ---

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