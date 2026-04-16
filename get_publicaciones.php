<?php
// gym-api/get_publicaciones.php
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include("db.php");
include("middleware.php");
$user = authenticate(); // Protegido por JWT

// Simulamos un retraso de 1 segundo para que puedas apreciar el "Loader" en React
sleep(1); 

$publicaciones = [
    ["id" => 1, "titulo" => "Mantenimiento de Caminadoras", "fecha" => "2026-04-10", "autor" => "Admin"],
    ["id" => 2, "titulo" => "Nueva clase de Spinning", "fecha" => "2026-04-12", "autor" => "Coach Maria"],
    ["id" => 3, "titulo" => "Horario de Días Festivos", "fecha" => "2026-04-15", "autor" => "Admin"]
];

echo json_encode(["success" => true, "data" => $publicaciones]);
?>