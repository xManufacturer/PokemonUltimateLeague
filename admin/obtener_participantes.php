<?php
require_once '../config/conexion.php';
require_once "proteger.php";

$competicionTemporada = $_GET['competicion_temporada'];
$fase = $_GET['fase'] ?? '';

if (in_array($fase, ['GA', 'GB', 'GC', 'GD'])) {

    $sql = "SELECT
                pa.id,
                p.nombre,
                p.imagen
            FROM participantes pa
            JOIN pokemon p
                ON pa.pokemon_id = p.id
            WHERE pa.competicion_temporada_id = ?
            AND pa.grupo = ?
            ORDER BY p.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $competicionTemporada, $fase);

} else {

    $sql = "SELECT
                pa.id,
                p.nombre,
                p.imagen
            FROM participantes pa
            JOIN pokemon p
                ON pa.pokemon_id = p.id
            WHERE pa.competicion_temporada_id = ?
            ORDER BY p.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $competicionTemporada);

}

$stmt->execute();
$resultado = $stmt->get_result();

$participantes = [];

while ($fila = $resultado->fetch_assoc()) {
    $participantes[] = $fila;
}

echo json_encode($participantes);