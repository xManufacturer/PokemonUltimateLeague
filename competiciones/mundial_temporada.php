<?php
require_once '../config/conexion.php';

$id = (int)$_GET["id"];

$sql = "SELECT
            t.numero
        FROM competiciones_temporadas ct
        JOIN temporadas t
            ON ct.temporada_id = t.id
        WHERE ct.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$temporada = $stmt->get_result()->fetch_assoc();

$sql = "SELECT *
        FROM mundial_ediciones
        WHERE competicion_temporada_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$edicion = $stmt->get_result()->fetch_assoc();

$mundial_id = $edicion["id"];

$sql = "SELECT *
        FROM mundial_resultados
        WHERE mundial_id=?";

$stmt=$conn->prepare($sql);
$stmt->bind_param("i",$mundial_id);
$stmt->execute();

$resultado=$stmt->get_result()->fetch_assoc();

$equipos = [];

$sql = "SELECT *
        FROM mundial_regiones
        WHERE id IN (?, ?)
        ORDER BY id";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ii",
    $resultado["region_local_id"],
    $resultado["region_visitante_id"]
);

$stmt->execute();

$regiones = $stmt->get_result();

while ($fila = $regiones->fetch_assoc()) {
    $equipos[] = $fila;
}

foreach($equipos as &$equipo){

    $sql = "SELECT
                p.nombre,
                p.imagen
            FROM mundial_participantes mp
            JOIN pokemon p
                ON mp.pokemon_id=p.id
            WHERE mp.mundial_id=?
            AND mp.region_id=?
            ORDER BY p.id";

    $stmt=$conn->prepare($sql);
    $stmt->bind_param("ii", $mundial_id, $equipo["id"]);
    $stmt->execute();

    $equipo["pokemon"]=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($equipo);

$idCampeon = $edicion["region_campeona_id"];

$campeon = "";

foreach ($equipos as $equipo) {

    if ($equipo["id"] == $idCampeon) {

        $campeon = $equipo["nombre"];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<title>Mundial</title>

<link rel="stylesheet" href="../css/style.css">

</head>

<body>

<h1>Mundial</h1>

<h2>Temporada <?php echo $temporada["numero"]; ?></h2>

<a href="../index.php" class="btn-inicio">
    <img src="../img/inicio.png">
</a>

<div class="mundial-vs">

    <div class="equipo-mundial">

        <div class="cabecera-mundial">

            <h2><?php echo $equipos[0]["nombre"]; ?></h2>

            <div class="resultado-mundial">
                <?php echo $resultado["puntos_local"]; ?>
                -
                <?php echo $resultado["puntos_visitante"]; ?>
            </div>

            <h2><?php echo $equipos[1]["nombre"]; ?></h2>

        </div>

        <?php
        for($i=0;$i<count($equipos[0]["pokemon"]);$i++){
        ?>

            <div class="partido">

                <div class="equipo local">

                    <img src="../img/pokemon/<?php echo $equipos[0]["pokemon"][$i]["imagen"]; ?>">

                    <span><?php echo $equipos[0]["pokemon"][$i]["nombre"]; ?></span>

                </div>

                <div class="resultado"></div>

                <div class="equipo visitante">

                    <span><?php echo $equipos[1]["pokemon"][$i]["nombre"]; ?></span>
                    
                    <img src="../img/pokemon/<?php echo $equipos[1]["pokemon"][$i]["imagen"]; ?>">

                </div>

            </div>

        <?php } ?>

    </div>

</div>
<h2>

Región Campeona:
<?php echo $campeon; ?>

</h2>