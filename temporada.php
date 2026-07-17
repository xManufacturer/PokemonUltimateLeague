<?php
require_once 'config/conexion.php';
require_once 'includes/clasificacion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Temporada no válida.");
}

$id = (int) $_GET['id'];

$sql = "SELECT
            c.nombre AS competicion,
            c.tipo,
            t.numero AS temporada,
            ct.jornadas,
            ct.grupos
        FROM competiciones_temporadas ct
        JOIN competiciones c ON ct.competicion_id = c.id
        JOIN temporadas t ON ct.temporada_id = t.id
        WHERE ct.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$competicion = $stmt->get_result();

if ($competicion->num_rows === 0) {
    die("No existe esa competición.");
}

$datos = $competicion->fetch_assoc();

if ($datos["competicion"] == "Champions League") {

    $sql = "SELECT jornada, fase
            FROM partidos
            WHERE competicion_temporada_id = ?
            ORDER BY id DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $ultima = $stmt->get_result()->fetch_assoc();

    if ($ultima) {
        $jornadaActual = $ultima["jornada"];
        $faseActual = $ultima["fase"];
    } else {
        $jornadaActual = 1;
        $faseActual = "G1";
    }

} else {

$sql = "SELECT MAX(jornada) AS jornada_actual
        FROM partidos
        WHERE competicion_temporada_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$jornadaActual = $stmt->get_result()->fetch_assoc()["jornada_actual"];
}

if ($datos["competicion"] == "Champions League") {

    $clasificacionGrupos = [];

    $sql = "SELECT DISTINCT fase
            FROM partidos
            WHERE competicion_temporada_id = ?
            AND fase LIKE 'G%'
            ORDER BY fase";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $grupos = $stmt->get_result();

    while ($grupo = $grupos->fetch_assoc()) {

        $clasificacionGrupos[$grupo["fase"]] =
            obtenerClasificacion($conn, $id, $grupo["fase"]);

    }

} else {
    if ($datos["tipo"] == "copa") {

    $clasificacionesGrupos = [];

    for ($i = 0; $i < $datos["grupos"]; $i++) {

        $grupo = chr(65 + $i);

        $clasificacionesGrupos[$grupo] =
            obtenerClasificacionGrupo($conn, $id, $grupo);
    }

} else {

    $clasificacion = obtenerClasificacion($conn, $id);

}
}

$sql = "SELECT
            nombre,
            posicion_inicio,
            posicion_fin,
            color
        FROM zonas_clasificacion
        WHERE competicion_temporada_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

$zonas = [];

while ($fila = $resultado->fetch_assoc()) {
    $zonas[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $datos['competicion']; ?></title>
    <link rel="icon" href="img/icono.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <h1><?php echo $datos['competicion']; ?></h1>
    <h2>Temporada <?php echo $datos['temporada']; ?></h2>
    <a href="index.php" class="btn-inicio">
        <img src="img/inicio.png" alt="Inicio">
    </a>

    <h2>Última Jornada</h2>

<?php
$claseUltima = "";

if (
    isset($faseActual) &&
    ($faseActual == "SF" || $faseActual == "F")
) {
    $claseUltima = " ultima-eliminatoria";
}


$sql = "SELECT COUNT(*) total
        FROM partidos
        WHERE competicion_temporada_id = ?";

if ($datos["competicion"] == "Champions League") {
    $sql .= " AND fase = ?";
} else {
    $sql .= " AND jornada = ?";
}

$stmt = $conn->prepare($sql);

if ($datos["competicion"] == "Champions League") {
    $stmt->bind_param("is", $id, $faseActual);
} else {
    $stmt->bind_param("ii", $id, $jornadaActual);
}

$stmt->execute();
$totalPartidos = $stmt->get_result()->fetch_assoc()["total"];

$claseUltima = ($totalPartidos == 1) ? " ultima-eliminatoria" : "";
?>
<div class="contenedor-jornada-actual<?php echo $claseUltima; ?>">
    <div class="jornada">
            <h3>

<?php
if ($datos["competicion"] == "Champions League") {

    if ($faseActual == "SF") {
        echo "Semifinales";

    } elseif ($faseActual == "F") {
        echo "Final";

    } else {
        echo "Jornada ".$jornadaActual;
    }

} elseif ($datos["tipo"] == "legendary" && $datos["jornadas"] == 1) {

    echo "Final";

} else {

    echo "Jornada ".$jornadaActual;

}
?>
</h3>
            <div class="partidos-jornada-actual">
        <?php

    if ($datos["competicion"] == "Champions League") {

    if ($faseActual == "SF" || $faseActual == "F") {

        $sql = "SELECT
                    pa.id,
                    pl.nombre AS local,
                    pl.imagen AS imagen_local,
                    pv.nombre AS visitante,
                    pv.imagen AS imagen_visitante
                FROM partidos pa
                JOIN participantes l ON pa.local_id = l.id
                JOIN pokemon pl ON l.pokemon_id = pl.id
                JOIN participantes v ON pa.visitante_id = v.id
                JOIN pokemon pv ON v.pokemon_id = pv.id
                WHERE pa.competicion_temporada_id = ?
                AND pa.fase = ?
                ORDER BY pa.id";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $faseActual);

    } else {

        $sql = "SELECT
                    pa.id,
                    pl.nombre AS local,
                    pl.imagen AS imagen_local,
                    pv.nombre AS visitante,
                    pv.imagen AS imagen_visitante
                FROM partidos pa
                JOIN participantes l ON pa.local_id = l.id
                JOIN pokemon pl ON l.pokemon_id = pl.id
                JOIN participantes v ON pa.visitante_id = v.id
                JOIN pokemon pv ON v.pokemon_id = pv.id
                WHERE pa.competicion_temporada_id = ?
                AND pa.jornada = ?
                AND pa.fase LIKE 'G%'
                ORDER BY pa.fase, pa.id";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $jornadaActual);

    }

} else {

    $sql = "SELECT
                pa.id,
                pl.nombre AS local,
                pl.imagen AS imagen_local,
                pv.nombre AS visitante,
                pv.imagen AS imagen_visitante
            FROM partidos pa
            JOIN participantes l ON pa.local_id = l.id
            JOIN pokemon pl ON l.pokemon_id = pl.id
            JOIN participantes v ON pa.visitante_id = v.id
            JOIN pokemon pv ON v.pokemon_id = pv.id
            WHERE pa.competicion_temporada_id = ?
            AND pa.jornada = ?
            ORDER BY pa.id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $jornadaActual);
}

$stmt->execute();
$partidos = $stmt->get_result();

    while ($partido = $partidos->fetch_assoc()) {

        $sqlSets = "SELECT vida_local, vida_visitante
                    FROM sets
                    WHERE partido_id = ?";

        $stmtSets = $conn->prepare($sqlSets);
        $stmtSets->bind_param("i", $partido["id"]);
        $stmtSets->execute();
        $sets = $stmtSets->get_result();

        $totalSets = $sets->num_rows;

        $totalLocal = 0;
$totalVisitante = 0;

$setsGanadosLocal = 0;
$setsGanadosVisitante = 0;

while ($set = $sets->fetch_assoc()) {

    $totalLocal += $set["vida_local"];
    $totalVisitante += $set["vida_visitante"];

    if ($set["vida_local"] == 0 && $set["vida_visitante"] > 0) {
        $setsGanadosVisitante++;
    } elseif ($set["vida_visitante"] == 0 && $set["vida_local"] > 0) {
        $setsGanadosLocal++;
    }

}
    ?>

    <div class="partido">

        <div class="equipo local">
            <img src="img/pokemon/<?php echo $partido["imagen_local"]; ?>">
            <span><?php echo $partido["local"]; ?></span>
        </div>

        <div class="resultado">

    <div class="resultado-sets">

    <?php if ($totalSets == 1) { ?>

        <?php echo $totalLocal; ?> - <?php echo $totalVisitante; ?>

    <?php } else { ?>

        <?php echo $setsGanadosLocal; ?> - <?php echo $setsGanadosVisitante; ?>

    <?php } ?>

    </div>

</div>

        <div class="equipo visitante">
            <span><?php echo $partido["visitante"]; ?></span>
            <img src="img/pokemon/<?php echo $partido["imagen_visitante"]; ?>">
        </div>

    </div>

<?php
}
?>
            </div>

        </div>
    </div>

    <h2>Clasificación</h2>

<?php

if ($datos["competicion"] == "Champions League") {

$sql = "SELECT grupos
        FROM competiciones_temporadas
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$totalGrupos = $stmt->get_result()->fetch_assoc()["grupos"];

?>

<div class="clasificacion-contenedor">

<?php

for ($i = 0; $i < $totalGrupos; $i++) {

    $grupo = chr(65 + $i);

    $clasificacionGrupo =
        obtenerClasificacionGrupo($conn, $id, $grupo);

?>

    <div class="clasificacion-columna">

        <h3>Grupo <?php echo $grupo; ?></h3>

        <table>

            <tr>
                <th>Pos</th>
                <th>Pokémon</th>
                <th>Com</th>
                <th>V</th>
                <th>E</th>
                <th>D</th>
                <th>PS+</th>
                <th>PS-</th>
                <th>Dif</th>
                <th>Pts</th>
            </tr>

            <?php

$posicion = 1;

foreach ($clasificacionGrupo as $fila) {

    $clases = [];

    foreach ($zonas as $zona) {
        if (
            $posicion >= $zona["posicion_inicio"] &&
            $posicion <= $zona["posicion_fin"]
        ) {
            $clases[] = strtolower(str_replace(" ", "-", $zona["nombre"]));
        }
    }

?>

<tr class="<?php echo implode(" ", $clases); ?>">
                <td><strong><?php echo $posicion; ?></strong></td>

                <td>

                    <div class="pokemon-clasificacion">
                        <img src="img/pokemon/<?php echo $fila["imagen"]; ?>">
                        <span><?php echo $fila["nombre"]; ?></span>
                    </div>

                </td>

                <td><?php echo $fila["com"]; ?></td>
                <td><?php echo $fila["v"]; ?></td>
                <td><?php echo $fila["e"]; ?></td>
                <td><?php echo $fila["d"]; ?></td>
                <td><?php echo $fila["ps_favor"]; ?></td>
                <td><?php echo $fila["ps_contra"]; ?></td>
                <td><strong><?php echo $fila["diferencia"]; ?></strong></td>
                <td><strong><?php echo $fila["puntos"]; ?></strong></td>

            </tr>

            <?php

                $posicion++;

            }

            ?>

        </table>

    </div>

<?php

}

?>

</div>

<?php

} elseif ($datos["tipo"] == "copa") {

    ?>

<div class="clasificacion-contenedor-copa">

<?php

foreach ($clasificacionesGrupos as $grupo => $clasificacionGrupo) {

?>

    <div class="clasificacion-columna">

        <h3>Grupo <?php echo $grupo; ?></h3>

        <table>

            <tr>
                <th>Pos</th>
                <th>Pokémon</th>
                <th>Com</th>
                <th>V</th>
                <th>E</th>
                <th>D</th>
                <th>PS+</th>
                <th>PS-</th>
                <th>Dif</th>
                <th>Pts</th>
            </tr>

            <?php

$posicion = 1;

foreach ($clasificacionGrupo as $fila) {

    $clases = [];

    foreach ($zonas as $zona) {
        if (
            $posicion >= $zona["posicion_inicio"] &&
            $posicion <= $zona["posicion_fin"]
        ) {
            $clases[] = strtolower(str_replace(" ", "-", $zona["nombre"]));
        }
    }

?>

<tr class="<?php echo implode(" ", $clases); ?>">

                <td><strong><?php echo $posicion; ?></strong></td>

                <td>
                    <div class="pokemon-clasificacion">
                        <img src="img/pokemon/<?php echo $fila["imagen"]; ?>">
                        <span><?php echo $fila["nombre"]; ?></span>
                    </div>
                </td>

                <td><?php echo $fila["com"]; ?></td>
                <td><?php echo $fila["v"]; ?></td>
                <td><?php echo $fila["e"]; ?></td>
                <td><?php echo $fila["d"]; ?></td>
                <td><?php echo $fila["ps_favor"]; ?></td>
                <td><?php echo $fila["ps_contra"]; ?></td>
                <td><strong><?php echo $fila["diferencia"]; ?></strong></td>
                <td><strong><?php echo $fila["puntos"]; ?></strong></td>

            </tr>

            <?php

                $posicion++;

            }

            ?>

        </table>

    </div>

<?php

}

?>

</div>

<?php

} else {

$totalParticipantes = count($clasificacion);
$unaColumna = ($totalParticipantes <= 10);

?>

<div class="clasificacion-contenedor">

    <div class="clasificacion-columna">

        <table>

            <tr>
                <th>Pos</th>
                <th>Pokémon</th>
                <th>Com</th>
                <th>V</th>
                <th>E</th>
                <th>D</th>
                <th>PS+</th>
                <th>PS-</th>
                <th>Dif</th>
                <th>Pts</th>
            </tr>

            <?php

            $posicion = 1;

            foreach ($clasificacion as $fila) {

                if (!$unaColumna && $posicion > 10) {
                    break;
                }

                $clases = [];

                foreach ($zonas as $zona) {

                    if (
                        $posicion >= $zona["posicion_inicio"] &&
                        $posicion <= $zona["posicion_fin"]
                    ) {
                        $clases[] = strtolower(str_replace(" ", "-", $zona["nombre"]));
                    }

                }

                if (empty($clases) && !empty($fila["plaza_especial"])) {
                    $clases[] = strtolower($fila["plaza_especial"]);
                }

            ?>

            <tr class="<?php echo implode(" ", $clases); ?>">

                <td><strong><?php echo $posicion; ?></strong></td>

                <td>

                    <div class="pokemon-clasificacion">
                        <img src="img/pokemon/<?php echo $fila["imagen"]; ?>">
                        <span><?php echo $fila["nombre"]; ?></span>
                    </div>

                </td>

                <td><?php echo $fila["com"]; ?></td>
                <td><?php echo $fila["v"]; ?></td>
                <td><?php echo $fila["e"]; ?></td>
                <td><?php echo $fila["d"]; ?></td>
                <td><?php echo $fila["ps_favor"]; ?></td>
                <td><?php echo $fila["ps_contra"]; ?></td>
                <td><strong><?php echo $fila["diferencia"]; ?></strong></td>
                <td><strong><?php echo $fila["puntos"]; ?></strong></td>

            </tr>

            <?php

                $posicion++;

            }

            ?>

        </table>

    </div>

<?php if (!$unaColumna) { ?>

    <div class="clasificacion-columna">

        <table>

            <tr>
                <th>Pos</th>
                <th>Pokémon</th>
                <th>Com</th>
                <th>V</th>
                <th>E</th>
                <th>D</th>
                <th>PS+</th>
                <th>PS-</th>
                <th>Dif</th>
                <th>Pts</th>
            </tr>

            <?php

            $posicion = 1;

            foreach ($clasificacion as $fila) {

                if ($posicion <= 10) {
                    $posicion++;
                    continue;
                }

                $clases = [];

                foreach ($zonas as $zona) {

                    if (
                        $posicion >= $zona["posicion_inicio"] &&
                        $posicion <= $zona["posicion_fin"]
                    ) {
                        $clases[] = strtolower(str_replace(" ", "-", $zona["nombre"]));
                    }

                }

                if (empty($clases) && !empty($fila["plaza_especial"])) {
                    $clases[] = strtolower($fila["plaza_especial"]);
                }

            ?>

            <tr class="<?php echo implode(" ", $clases); ?>">

                <td><strong><?php echo $posicion; ?></strong></td>

                <td>

                    <div class="pokemon-clasificacion">
                        <img src="img/pokemon/<?php echo $fila["imagen"]; ?>">
                        <span><?php echo $fila["nombre"]; ?></span>
                    </div>

                </td>

                <td><?php echo $fila["com"]; ?></td>
                <td><?php echo $fila["v"]; ?></td>
                <td><?php echo $fila["e"]; ?></td>
                <td><?php echo $fila["d"]; ?></td>
                <td><?php echo $fila["ps_favor"]; ?></td>
                <td><?php echo $fila["ps_contra"]; ?></td>
                <td><strong><?php echo $fila["diferencia"]; ?></strong></td>
                <td><strong><?php echo $fila["puntos"]; ?></strong></td>

            </tr>

            <?php

                $posicion++;

            }

            ?>

        </table>

    </div>

<?php } ?>

</div>

<?php } ?>

        <?php if ($datos["tipo"] != "copa" && $datos["competicion"] != "Legendary League") { ?>

<div class="leyenda-clasificacion">

    <div class="item-leyenda">
        <span class="color-leyenda campeon"></span>
        Campeón
    </div>

    <div class="item-leyenda">
        <span class="color-leyenda champions"></span>
        Champions
    </div>

    <div class="item-leyenda">
        <span class="color-leyenda mundial"></span>
        Mundial
    </div>

    <div class="item-leyenda">
        <span class="color-leyenda promocion"></span>
        Promoción
    </div>

    <div class="item-leyenda">
        <span class="color-leyenda descenso"></span>
        Descenso
    </div>

</div>

<?php } ?>

    <h2>Jornadas</h2>
    <div class="contenedor-jornadas">

        <?php
        $inicio = 1;

if ($datos["tipo"] == "legendary" && $datos["jornadas"] == 1) {
    $inicio = 0;
}

for ($jornada = $inicio; $jornada <= $datos["jornadas"]; $jornada++) {
        
                $sql = "SELECT
            pa.id,
            pl.nombre AS local,
            pl.imagen AS imagen_local,
            pv.nombre AS visitante,
            pv.imagen AS imagen_visitante
        FROM partidos pa
        JOIN participantes l ON pa.local_id = l.id
        JOIN pokemon pl ON l.pokemon_id = pl.id
        JOIN participantes v ON pa.visitante_id = v.id
        JOIN pokemon pv ON v.pokemon_id = pv.id
        WHERE pa.competicion_temporada_id = ?
        AND pa.jornada = ?";

        if ($datos["competicion"] == "Champions League") {

    $sql .= " AND pa.fase LIKE 'G%'";

} elseif ($datos["tipo"] == "legendary" && $datos["jornadas"] == 1) {

    $sql .= " AND pa.fase = 'F'";

} else {

    $sql .= " AND pa.fase = 'L'";

}

$sql .= " ORDER BY pa.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $jornada);
$stmt->execute();
$partidos = $stmt->get_result();

                if ($partidos->num_rows == 0) {
                    continue;
                }
                ?>

                <div class="jornada">
                <h3>
<?php

if ($datos["competicion"] == "Champions League") {

    if ($jornada == 4) {
        echo "Semifinales";
    } else {
        echo "Jornada ".$jornada;
    }

} elseif ($datos["tipo"] == "legendary" && $datos["jornadas"] == 1) {

    echo "Final";

} else {

    echo "Jornada ".$jornada;

}

?>
</h3>

                <?php
                while ($partido = $partidos->fetch_assoc()) {
                    $sqlSets = "SELECT
                                    vida_local,
                                    vida_visitante
                                FROM sets
                                WHERE partido_id = ?";

                    $stmtSets = $conn->prepare($sqlSets);
                    $stmtSets->bind_param("i", $partido["id"]);
                    $stmtSets->execute();
                    $sets = $stmtSets->get_result();

                    $vidasLocal = [];
$vidasVisitante = [];

$setsGanadosLocal = 0;
$setsGanadosVisitante = 0;

$marcadores = [];

while ($set = $sets->fetch_assoc()) {

    $marcadores[] = [
        "local" => $set["vida_local"],
        "visitante" => $set["vida_visitante"]
    ];

    if ($set["vida_local"] == 0 && $set["vida_visitante"] > 0) {
        $setsGanadosVisitante++;
    } elseif ($set["vida_visitante"] == 0 && $set["vida_local"] > 0) {
        $setsGanadosLocal++;
    }

}
$totalSets = count($marcadores);

                    ?>

                    <div class="partido">
                        <div class="equipo local">
                            <img src="img/pokemon/<?php echo $partido["imagen_local"]; ?>" width="70">
                            <span><?php echo $partido["local"]; ?></span>
                        </div>
                            
                        <div class="resultado">

    <div class="resultado-sets">

<?php if ($totalSets == 1) { ?>

    <?php echo $marcadores[0]["local"]; ?>
    -
    <?php echo $marcadores[0]["visitante"]; ?>

<?php } else { ?>

    <?php echo $setsGanadosLocal; ?>
    -
    <?php echo $setsGanadosVisitante; ?>

<?php } ?>

</div>

    <?php if (count($marcadores) > 1) { ?>

<div class="resultado-detalle">

    <?php foreach ($marcadores as $set) { ?>

        <div>
            <?php echo $set["local"]; ?> -
            <?php echo $set["visitante"]; ?>
        </div>

    <?php } ?>

</div>

<?php } ?>

</div>
                    
                        <div class="equipo visitante">
                            <span><?php echo $partido["visitante"]; ?></span>
                            <img src="img/pokemon/<?php echo $partido["imagen_visitante"]; ?>" width="70">
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        <?php
        }

        if ($datos["competicion"] == "Champions League") {

    $fases = [
        "SF" => "Semifinales",
        "F" => "Final"
    ];

    foreach ($fases as $fase => $titulo) {

        $sql = "SELECT
                    pa.id,
                    pl.nombre AS local,
                    pl.imagen AS imagen_local,
                    pv.nombre AS visitante,
                    pv.imagen AS imagen_visitante
                FROM partidos pa
                JOIN participantes l ON pa.local_id = l.id
                JOIN pokemon pl ON l.pokemon_id = pl.id
                JOIN participantes v ON pa.visitante_id = v.id
                JOIN pokemon pv ON v.pokemon_id = pv.id
                WHERE pa.competicion_temporada_id = ?
                AND pa.fase = ?
                ORDER BY pa.id";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $fase);
        $stmt->execute();
        $partidos = $stmt->get_result();

        if ($partidos->num_rows == 0) {
            continue;
        }
        ?>
        <div class="jornada">
<h3>
<?php
echo $titulo;
?>
</h3>
<?php
while ($partido = $partidos->fetch_assoc()) {

    $sqlSets = "SELECT vida_local, vida_visitante
                FROM sets
                WHERE partido_id = ?";

    $stmtSets = $conn->prepare($sqlSets);
    $stmtSets->bind_param("i", $partido["id"]);
    $stmtSets->execute();
    $sets = $stmtSets->get_result();

    $setsGanadosLocal = 0;
$setsGanadosVisitante = 0;

$marcadores = [];

while ($set = $sets->fetch_assoc()) {

    $marcadores[] = [
        "local" => $set["vida_local"],
        "visitante" => $set["vida_visitante"]
    ];

    if ($set["vida_local"] == 0 && $set["vida_visitante"] > 0) {
        $setsGanadosVisitante++;
    } elseif ($set["vida_visitante"] == 0 && $set["vida_local"] > 0) {
        $setsGanadosLocal++;
    }

}
$totalSets = count($marcadores);
?>

<div class="partido">

    <div class="equipo local">
        <img src="img/pokemon/<?php echo $partido["imagen_local"]; ?>" width="70">
        <span><?php echo $partido["local"]; ?></span>
    </div>

    <div class="resultado">

    <div class="resultado-sets">

<?php if ($totalSets == 1) { ?>

    <?php echo $marcadores[0]["local"]; ?>
    -
    <?php echo $marcadores[0]["visitante"]; ?>

<?php } else { ?>

    <?php echo $setsGanadosLocal; ?>
    -
    <?php echo $setsGanadosVisitante; ?>

<?php } ?>

</div>

    <?php if (count($marcadores) > 1) { ?>

<div class="resultado-detalle">

    <?php foreach ($marcadores as $set) { ?>

        <div>
            <?php echo $set["local"]; ?> -
            <?php echo $set["visitante"]; ?>
        </div>

    <?php } ?>

</div>

<?php } ?>

</div>

    <div class="equipo visitante">
        <span><?php echo $partido["visitante"]; ?></span>
        <img src="img/pokemon/<?php echo $partido["imagen_visitante"]; ?>" width="70">
    </div>

</div>

<?php } ?>

</div>

<?php
    }
}
?>
    </div>
</body>
</html>