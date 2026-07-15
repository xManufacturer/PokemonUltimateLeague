<?php
require_once '../config/conexion.php';
require_once 'proteger.php';

$competicion = $_GET['competicion'];

$sql = "SELECT
            ct.id,
            t.numero,
            c.tipo,
            ct.jornadas,
            ct.grupos,
            ct.sets_fase,
            ct.sets_final
        FROM competiciones_temporadas ct
        JOIN temporadas t ON ct.temporada_id = t.id
        JOIN competiciones c ON ct.competicion_id = c.id
        WHERE ct.competicion_id = ?
        ORDER BY t.numero";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion);
$stmt->execute();
$resultado = $stmt->get_result();

$temporadas = [];

while ($fila = $resultado->fetch_assoc()) {
    $temporadas[] = $fila;
}

echo json_encode($temporadas);