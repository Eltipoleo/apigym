<?php
// gym-api/db.php

/**
 * Configuracion de conexion dinamica.
 * Intenta leer las variables de entorno de Railway; 
 * si no existen (desarrollo local), usa los valores por defecto.
 */

$host = getenv('MYSQLHOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('MYSQLDATABASE') ?: "gym";
$port = getenv('MYSQLPORT') ?: "3306";

// Crear la conexion usando el puerto especificado
$conexion = new mysqli($host, $user, $pass, $db, $port);

// Verificar si hay errores de conexion
if ($conexion->connect_error) {
    // En produccion es mejor no mostrar detalles especificos, 
    // pero para depuracion en Railway esto te ayudara:
    die("Error de conexion: " . $conexion->connect_error);
}

// Opcional: Establecer el conjunto de caracteres a utf8
$conexion->set_charset("utf8");
?>