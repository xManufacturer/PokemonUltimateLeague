<?php

$host = "sql303.infinityfree.com";
$usuario = "if0_42341239";
$password = "";
$bd = "if0_42341239_pokemonultimateleague";

$conn = new mysqli($host, $usuario, $password, $bd);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>