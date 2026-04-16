<?php
// 1. Configuración de Cabeceras (CORS) para que React pueda conectarse
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Manejo de peticiones OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Obtener la ruta de la URL
// Si la URL es /login, $request será "login"
$request = $_GET['url'] ?? '';
$request = rtrim($request, '/');

// 3. Enrutador: decide qué archivo cargar según la URL
switch ($request) {
    case 'login':
        require 'gym-api/login.php';
        break;
        
    case 'usuarios':
        require 'gym-api/get_usuarios.php';
        break;

    case 'registro':
        require 'gym-api/register.php';
        break;

    case 'add-tiempo':
        require 'gym-api/add_tiempo.php';
        break;

    // Puedes seguir agregando más casos para tus otros archivos:
    // case 'publicaciones': require 'gym-api/get_publicaciones.php'; break;

    default:
        // Si no encuentra la ruta, devuelve un error 404
        http_response_code(404);
        echo json_encode(["message" => "Ruta no encontrada", "ruta_solicitada" => $request]);
        break;
}