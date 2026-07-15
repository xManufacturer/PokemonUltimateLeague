<?php
require_once "../config/conexion.php";
require_once "proteger.php";

$competicion_temporada = $_POST["competicion_temporada"];
$fase = $_POST["fase"];
$jornada = $_POST["jornada"];
$local = $_POST["local"];
$visitante = $_POST["visitante"];

$vidas_local = $_POST["vida_local"];
$vidas_visitante = $_POST["vida_visitante"];

$sql = "SELECT id
        FROM partidos
        WHERE competicion_temporada_id = ?
        AND fase = ?
        AND jornada = ?
        AND local_id = ?
        AND visitante_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "isiii",
    $competicion_temporada,
    $fase,
    $jornada,
    $local,
    $visitante
);

$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    die("Este combate ya está registrado.");
}

$sql = "INSERT INTO partidos
        (competicion_temporada_id, fase, jornada, local_id, visitante_id)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isiii",
    $competicion_temporada,
    $fase,
    $jornada,
    $local,
    $visitante
);

$stmt->execute();
$partido_id = $conn->insert_id;

$sql = "INSERT INTO sets
        (partido_id, numero_set, vida_local, vida_visitante)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

for ($i = 0; $i < count($vidas_local); $i++) {

    $numero_set = $i + 1;

    $vidaLocal = ($vidas_local[$i] === "") ? 0 : (int)$vidas_local[$i];
    $vidaVisitante = ($vidas_visitante[$i] === "") ? 0 : (int)$vidas_visitante[$i];

    $stmt->bind_param(
        "iiii",
        $partido_id,
        $numero_set,
        $vidaLocal,
        $vidaVisitante
    );

    $stmt->execute();
}

$sql = "UPDATE competiciones_temporadas
        SET fecha_actualizacion = NOW()
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion_temporada);
$stmt->execute();

header("Location: registrar_combate.php");
exit;