<?php
// Datos de conexión al servidor local
$host = "localhost";
$usuario = "root"; // Usuario por defecto en XAMPP
$password = ""; // XAMPP no tiene contraseña por defecto
$base_datos = "conectalocal";
$puerto = 3307; // Puerto ajustado para evitar conflictos

// Crear la conexión utilizando MySQLi (Orientado a Objetos)
$conexion = new mysqli($host, $usuario, $password, $base_datos, $puerto);

// Verificar si hay errores en la conexión
if ($conexion->connect_error) {
    die("Error crítico de conexión a la base de datos: " . $conexion->connect_error);
}

// Forzar el uso de caracteres UTF-8 para evitar problemas con tildes y la letra Ñ
$conexion->set_charset("utf8mb4");
?>