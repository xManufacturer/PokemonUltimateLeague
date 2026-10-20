<?php
require_once "../config/conexion.php";

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("Temporada no válida.");
}

/*
 * Comprobar que la temporada pertenece a Members League
 * y que los playoffs están habilitados.
 */
$sql = "SELECT
            ct.id,
            t.numero AS temporada,
            ct.sets_final
        FROM competiciones_temporadas ct
        INNER JOIN temporadas t ON t.id = ct.temporada_id
        WHERE ct.id = ?
          AND ct.competicion_id = 17
          AND ct.sets_final > 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$temporada = $stmt->get_result()->fetch_assoc();

if (!$temporada) {
    die("Los playoffs no están disponibles para esta temporada.");
}

/*
 * Obtener semifinales y final.
 */
$sql = "SELECT
            mp.id,
            mp.fase,
            mp.local_id,
            mp.visitante_id,
            mp.kills_local,
            mp.kills_visitante,
            ml.nombre AS local,
            ml.imagen AS imagen_local,
            mv.nombre AS visitante,
            mv.imagen AS imagen_visitante
        FROM members_partidos mp
        INNER JOIN members_miembros ml ON ml.id = mp.local_id
        INNER JOIN members_miembros mv ON mv.id = mp.visitante_id
        WHERE mp.competicion_temporada_id = ?
          AND mp.fase IN ('SF', 'F')
        ORDER BY
            CASE mp.fase
                WHEN 'SF' THEN 1
                WHEN 'F' THEN 2
                ELSE 3
            END,
            mp.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

$semifinales = [];
$finales = [];

while ($partido = $resultado->fetch_assoc()) {
    if ($partido["fase"] === "SF") {
        $semifinales[] = $partido;
    } elseif ($partido["fase"] === "F") {
        $finales[] = $partido;
    }
}


/*
 * Obtener los marcadores de cada set.
 */
$setsPorPartido = [];

$sql = "SELECT
            ms.id AS set_id,
            ms.partido_id,
            ms.numero_set,
            ms.kills_local,
            ms.kills_visitante
        FROM members_sets ms
        INNER JOIN members_partidos mp ON mp.id = ms.partido_id
        WHERE mp.competicion_temporada_id = ?
          AND mp.fase IN ('SF', 'F')
        ORDER BY ms.partido_id, ms.numero_set";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultadoSets = $stmt->get_result();

while ($set = $resultadoSets->fetch_assoc()) {
    $partidoId = (int)$set["partido_id"];
    $setsPorPartido[$partidoId][] = $set;
}

/*
 * Obtener las kills de cada Pokémon, separadas por set y miembro.
 */
$killsPorPartido = [];

$sql = "SELECT
            mk.partido_id,
            mk.set_id,
            mk.miembro_id,
            p.nombre AS pokemon,
            mk.kills
        FROM members_kills mk
        INNER JOIN pokemon p ON p.id = mk.pokemon_id
        INNER JOIN members_partidos mp ON mp.id = mk.partido_id
        WHERE mp.competicion_temporada_id = ?
          AND mp.fase IN ('SF', 'F')
        ORDER BY
            mk.partido_id,
            mk.set_id,
            mk.miembro_id,
            p.nombre";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultadoKills = $stmt->get_result();

while ($kill = $resultadoKills->fetch_assoc()) {
    $partidoId = (int)$kill["partido_id"];
    $setId = (int)$kill["set_id"];
    $miembroId = (int)$kill["miembro_id"];

    $killsPorPartido[$partidoId][$setId][$miembroId][] = $kill;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members League</title>
    <link rel="icon" href="../img/icono.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-TZL2J8ZT');</script>
<!-- End Google Tag Manager -->
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TZL2J8ZT"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<div class="contenedor">

    <h1>
        Members League - Playoffs
    </h1>
    <h2>
        Temporada <?php echo (int)$temporada["temporada"]; ?>
    </h2>
    <a href="../index.php" class="btn-inicio">
        <img src="../img/inicio.png" alt="Inicio">
    </a>

    <?php
    /*
     * Mostrar partidos con el formato de las jornadas.
     */
    function mostrarPartidosPlayoffs($partidos, $titulo, $killsPorPartido, $setsPorPartido)
    {
    ?>

        <br>
        <h2><?php echo htmlspecialchars($titulo); ?></h2>

        <div class="contenedor-jornadas">

            <div class="jornada">

                <?php if (empty($partidos)) { ?>

                    <p>Aún no hay combates registrados.</p>

                <?php } ?>

                <?php foreach ($partidos as $partido) { ?>

                    <div class="partido">

                        <!-- LOCAL -->
                        <div class="equipo local">

                            <?php if (!empty($partido["imagen_local"])) { ?>
                                <img
                                    src="../img/members/<?php echo htmlspecialchars($partido["imagen_local"]); ?>"
                                    alt="<?php echo htmlspecialchars($partido["local"]); ?>">
                            <?php } ?>

                            <span>
                                <?php echo htmlspecialchars($partido["local"]); ?>
                            </span>

                        </div>

                        <!-- MARCADOR -->
                        <div class="resultado">
    <div class="resultado-sets">
        <?php
        $partidoId = (int)$partido["id"];
        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 0;

        foreach ($setsPorPartido[$partidoId] ?? [] as $set) {
            if ((int)$set["kills_local"] > (int)$set["kills_visitante"]) {
                $setsGanadosLocal++;
            } elseif ((int)$set["kills_visitante"] > (int)$set["kills_local"]) {
                $setsGanadosVisitante++;
            }
        }

        echo $setsGanadosLocal . " - " . $setsGanadosVisitante;
        ?>
    </div>
</div>

                        <!-- VISITANTE -->
                        <div class="equipo visitante">

                            <span>
                                <?php echo htmlspecialchars($partido["visitante"]); ?>
                            </span>

                            <?php if (!empty($partido["imagen_visitante"])) { ?>
                                <img
                                    src="../img/members/<?php echo htmlspecialchars($partido["imagen_visitante"]); ?>"
                                    alt="<?php echo htmlspecialchars($partido["visitante"]); ?>">
                            <?php } ?>

                        </div>

                    </div>

                    <?php
$partidoId = (int)$partido["id"];

if (!empty($setsPorPartido[$partidoId])) {
    ?>

    <div class="members-kills-partido">

        <?php foreach ($setsPorPartido[$partidoId] as $set) { ?>

            <?php
            $setId = (int)$set["set_id"];

            $ordenMiembros = [
                (int)$partido["local_id"],
                (int)$partido["visitante_id"]
            ];
            ?>

            <div class="members-kills-miembro">

                <br><strong>
                    Set <?php echo (int)$set["numero_set"]; ?>: 
                    (<?php echo (int)$set["kills_local"]; ?>
                    -
                    <?php echo (int)$set["kills_visitante"]; ?>)
                </strong>

            </div>

            <?php foreach ($ordenMiembros as $miembroId) { ?>

                <?php
                if (
                    empty(
                        $killsPorPartido[$partidoId][$setId][$miembroId]
                    )
                ) {
                    continue;
                }

                $kills = $killsPorPartido
                    [$partidoId][$setId][$miembroId];
                ?>

                <div class="members-kills-miembro">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $miembroId === (int)$partido["local_id"]
                                ? $partido["local"]
                                : $partido["visitante"]
                        );
                        ?>
                    </strong>:

                    <?php foreach ($kills as $indice => $kill) { ?>

                        <?php if ($indice > 0) echo ", "; ?>

                        <?php echo htmlspecialchars($kill["pokemon"]); ?>

                        <?php if ((int)$kill["kills"] > 0) { ?>
                            (<?php echo (int)$kill["kills"]; ?>)
                        <?php } ?>

                    <?php } ?>

                </div>

            <?php } ?>

        <?php } ?>

    </div>

    <?php
}
?>

                <?php } ?>

            </div>

        </div>

    <?php
    }
    ?>

    <?php
    mostrarPartidosPlayoffs(
        $semifinales,
        "Semifinales",
        $killsPorPartido,
        $setsPorPartido
    );

    mostrarPartidosPlayoffs(
        $finales,
        "Final",
        $killsPorPartido,
        $setsPorPartido
    );
    ?>

</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>