<?php

require_once "../config/conexion.php";
require_once "proteger.php";

$competicion_temporada = (int)($_GET["competicion_temporada"] ?? 0);

$sql = "SELECT
            m.id,
            m.nombre,
            m.imagen
        FROM members_equipos e
        INNER JOIN members_miembros m
            ON m.id = e.miembro_id
        WHERE e.competicion_temporada_id = ?
        ORDER BY m.nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion_temporada);
$stmt->execute();

$resultado = $stmt->get_result();

$datos = [];

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

header("Content-Type: application/json; charset=utf-8");

echo json_encode($datos);