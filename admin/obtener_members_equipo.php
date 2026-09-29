<?php

require_once "../config/conexion.php";
require_once "proteger.php";

$miembro_id = (int)($_GET["miembro_id"] ?? 0);
$competicion_temporada = (int)($_GET["competicion_temporada"] ?? 0);

$sql = "SELECT
            p.id,
            p.nombre
        FROM members_equipos e
        INNER JOIN members_pokemon mp
            ON mp.equipo_id = e.id
        INNER JOIN pokemon p
            ON p.id = mp.pokemon_id
        WHERE e.miembro_id = ?
        AND e.competicion_temporada_id = ?
        ORDER BY mp.id";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $miembro_id,
    $competicion_temporada
);

$stmt->execute();

$resultado = $stmt->get_result();

$datos = [];

while ($fila = $resultado->fetch_assoc()) {

    $datos[] = $fila;

}

header("Content-Type: application/json; charset=utf-8");

echo json_encode($datos);