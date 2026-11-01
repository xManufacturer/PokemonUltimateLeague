<?php
require_once 'config/conexion.php';
require_once 'includes/clasificacion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Temporada no válida.");
}

$id = (int) $_GET['id'];

// Poké Ball D'Or: por defecto muestra el top 30; permite desplegar toda la clasificación.
$mostrarTodosKillCount = isset($_GET['mostrar_todos_killcount'])
    && $_GET['mostrar_todos_killcount'] === '1';

$sql = "SELECT
            ct.competicion_id,
            c.nombre AS competicion,
            c.tipo,
            t.numero AS temporada,
            ct.jornadas,
            ct.grupos,
            ct.sets_final
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

$idPlayoffsMembers = null;

if ($datos["tipo"] == "members") {
    $sqlPlayoffs = "SELECT sets_final
                    FROM competiciones_temporadas
                    WHERE id = ?
                    LIMIT 1";

    $stmtPlayoffs = $conn->prepare($sqlPlayoffs);
    $stmtPlayoffs->bind_param("i", $id);
    $stmtPlayoffs->execute();
    $stmtPlayoffs->bind_result($setsFinal);

    if ($stmtPlayoffs->fetch() && (int)$setsFinal > 0) {
        $idPlayoffsMembers = $id;
    }

    $stmtPlayoffs->close();
}

// ============================================================
// MEMBERS LEAGUE
// Última jornada + todas las jornadas
// ============================================================

$ultimaJornadaMembers = null;
$partidosUltimaJornadaMembers = [];
$jornadasMembers = [];
$killsPorPartidoMembers = [];

if ($datos["tipo"] == "members") {

    /*
     * ==========================================
     * ÚLTIMA JORNADA DISPUTADA
     * ==========================================
     */

    $sql = "SELECT MAX(jornada) AS ultima_jornada
        FROM members_partidos
        WHERE competicion_temporada_id = ?
        AND fase = 'L'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();

    if (
        $fila &&
        $fila["ultima_jornada"] !== null
    ) {

        $ultimaJornadaMembers =
            (int)$fila["ultima_jornada"];

    }


    /*
     * ==========================================
     * PARTIDOS DE LA ÚLTIMA JORNADA
     * ==========================================
     */

    if ($ultimaJornadaMembers !== null) {

        $sql = "SELECT
                    mp.id,
                    mp.local_id,
                    mp.visitante_id,
                    mp.kills_local,
                    mp.kills_visitante,
                    ml.nombre AS local,
                    ml.imagen AS imagen_local,
                    mv.nombre AS visitante,
                    mv.imagen AS imagen_visitante
                FROM members_partidos mp

                INNER JOIN members_miembros ml
                    ON ml.id = mp.local_id

                INNER JOIN members_miembros mv
                    ON mv.id = mp.visitante_id

                WHERE mp.competicion_temporada_id = ?
                AND mp.jornada = ?
                AND mp.fase = 'L'

                ORDER BY mp.id";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ii",
            $id,
            $ultimaJornadaMembers
        );

        $stmt->execute();

        $resultado =
            $stmt->get_result();

        while (
            $partido =
            $resultado->fetch_assoc()
        ) {

            $partidosUltimaJornadaMembers[] =
                $partido;

        }

    }


    /*
     * ==========================================
     * TODAS LAS JORNADAS
     * ==========================================
     */

    $sql = "SELECT
                mp.id,
                mp.jornada,
                mp.local_id,
                mp.visitante_id,
                mp.kills_local,
                mp.kills_visitante,
                ml.nombre AS local,
                ml.imagen AS imagen_local,
                mv.nombre AS visitante,
                mv.imagen AS imagen_visitante
            FROM members_partidos mp

            INNER JOIN members_miembros ml
                ON ml.id = mp.local_id

            INNER JOIN members_miembros mv
                ON mv.id = mp.visitante_id

            WHERE mp.competicion_temporada_id = ?
            AND mp.fase = 'L'

            ORDER BY
                mp.jornada ASC,
                mp.id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado =
        $stmt->get_result();

    while (
        $partido =
        $resultado->fetch_assoc()
    ) {

        $jornada =
            (int)$partido["jornada"];

        if (
            !isset(
                $jornadasMembers[$jornada]
            )
        ) {

            $jornadasMembers[$jornada] = [];

        }

        $jornadasMembers[$jornada][] =
            $partido;
    }


    /*
     * ==========================================
     * KILLS DE CADA PARTIDO
     * ==========================================
     */

    $sql = "SELECT
            mpte.partido_id,
            mpte.miembro_id,
            p.id AS pokemon_id,
            p.nombre AS pokemon,
            COALESCE(mk.kills, 0) AS kills

        FROM members_participantes mpte

        INNER JOIN members_partidos mp
            ON mp.id = mpte.partido_id

        INNER JOIN pokemon p
            ON p.id = mpte.pokemon_id

        LEFT JOIN members_kills mk
            ON mk.partido_id = mpte.partido_id
            AND mk.miembro_id = mpte.miembro_id
            AND mk.pokemon_id = mpte.pokemon_id

        WHERE mp.competicion_temporada_id = ?

        ORDER BY
            mpte.partido_id ASC,
            mpte.miembro_id ASC,
            kills DESC,
            p.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

while ($kill = $resultado->fetch_assoc()) {

    $partidoId = (int)$kill["partido_id"];
    $miembroId = (int)$kill["miembro_id"];

    if (!isset($killsPorPartidoMembers[$partidoId])) {
        $killsPorPartidoMembers[$partidoId] = [];
    }

    if (!isset($killsPorPartidoMembers[$partidoId][$miembroId])) {
        $killsPorPartidoMembers[$partidoId][$miembroId] = [];
    }

    $killsPorPartidoMembers[$partidoId][$miembroId][] = [
        "pokemon" => $kill["pokemon"],
        "kills" => (int)$kill["kills"]
    ];
}
}

/*
 * ============================================================
 * MEMBERS LEAGUE
 * ============================================================
 */

$clasificacionMembers = [];
$killCountMembers = [];

if ($datos["tipo"] == "members") {

    $members = obtenerClasificacionMembers(
        $conn,
        $id
    );

    $clasificacionMembers = $members["clasificacion"];
    $killCountMembers = $members["killCount"];

    // 1. Obtener el entrenador más reciente (registro con ID más alto en members_pokemon) para cada Pokémon
    $entrenadorRealPorPokemon = [];
    $sqlEntrenadorReal = "
        SELECT mep.pokemon_id, e.miembro_id, mm.nombre AS miembro, mm.imagen AS imagen_miembro
        FROM members_pokemon mep
        INNER JOIN members_equipos e ON e.id = mep.equipo_id
        INNER JOIN members_miembros mm ON mm.id = e.miembro_id
        WHERE e.competicion_temporada_id = ?
        ORDER BY mep.id DESC
    ";
    $stmtEntrenadorReal = $conn->prepare($sqlEntrenadorReal);
    if ($stmtEntrenadorReal) {
        $stmtEntrenadorReal->bind_param("i", $id);
        $stmtEntrenadorReal->execute();
        $resultadoEntrenadorReal = $stmtEntrenadorReal->get_result();
        while ($filaER = $resultadoEntrenadorReal->fetch_assoc()) {
            $pId = (int)$filaER["pokemon_id"];
            if (!isset($entrenadorRealPorPokemon[$pId])) {
                $entrenadorRealPorPokemon[$pId] = [
                    'miembro_id'     => (int)$filaER["miembro_id"],
                    'miembro'        => $filaER["miembro"],
                    'imagen_miembro' => $filaER["imagen_miembro"]
                ];
            }
        }
        $stmtEntrenadorReal->close();
    }

    // 2. Obtener todos los entrenadores que ha tenido cada Pokémon para el asterisco
    $entrenadoresPorPokemonMembers = [];
    $sqlTodosEntrenadores = "
        SELECT DISTINCT mep.pokemon_id, e.miembro_id, mm.nombre AS miembro
        FROM members_pokemon mep
        INNER JOIN members_equipos e ON e.id = mep.equipo_id
        INNER JOIN members_miembros mm ON mm.id = e.miembro_id
        WHERE e.competicion_temporada_id = ?
    ";
    $stmtTodos = $conn->prepare($sqlTodosEntrenadores);
    if ($stmtTodos) {
        $stmtTodos->bind_param("i", $id);
        $stmtTodos->execute();
        $resultadoTodos = $stmtTodos->get_result();
        while ($filaT = $resultadoTodos->fetch_assoc()) {
            $pId = (int)$filaT["pokemon_id"];
            $mId = (int)$filaT["miembro_id"];
            $mNombre = (string)$filaT["miembro"];
            if ($pId > 0 && $mId > 0) {
                $entrenadoresPorPokemonMembers[$pId][$mId] = $mNombre;
            }
        }
        $stmtTodos->close();
    }

    // 3. Actualizar cada fila de $killCountMembers con su entrenador más reciente real
    foreach ($killCountMembers as &$filaKill) {
        $pokemonId = (int)($filaKill["pokemon_id"] ?? 0);
        if (isset($entrenadorRealPorPokemon[$pokemonId])) {
            $filaKill["miembro_id"]     = $entrenadorRealPorPokemon[$pokemonId]["miembro_id"];
            $filaKill["miembro"]        = $entrenadorRealPorPokemon[$pokemonId]["miembro"];
            $filaKill["imagen_miembro"] = $entrenadorRealPorPokemon[$pokemonId]["imagen_miembro"];
        }
    }
    unset($filaKill);

    // Motivos de sanción de los equipos de esta temporada.
    $sancionesMembers = [];

    $sqlSanciones = "
        SELECT
            e.miembro_id,
            s.sancion,
            s.motivo
        FROM sanciones s
        INNER JOIN members_equipos e
            ON e.id = s.equipo_id
        WHERE e.competicion_temporada_id = ?
          AND s.sancion <> 0
        ORDER BY s.id ASC
    ";

    $stmtSanciones = $conn->prepare($sqlSanciones);
    $stmtSanciones->bind_param("i", $id);
    $stmtSanciones->execute();
    $resultadoSanciones = $stmtSanciones->get_result();

    while ($sancion = $resultadoSanciones->fetch_assoc()) {
        $miembroSancionado = (int)$sancion["miembro_id"];
        $motivo = trim((string)$sancion["motivo"]);

        if (!isset($sancionesMembers[$miembroSancionado])) {
            $sancionesMembers[$miembroSancionado] = [];
        }

        if ($motivo === "") {
            $motivo = "Sanción de " . (int)$sancion["sancion"] . " puntos";
        }

        $sancionesMembers[$miembroSancionado][] = $motivo;
    }
}

// ============================================================
// BOTÓN PARA IR A LA PROMOCIÓN CORRESPONDIENTE
// ============================================================

$idPromocion = null;

if ($datos["tipo"] == "segunda") {

    $sqlPromocion = "SELECT ct.id
                     FROM competiciones_temporadas ct
                     JOIN competiciones c
                          ON ct.competicion_id = c.id
                     WHERE ct.segunda_de_id = ?
                     AND c.tipo = 'promocion'
                     LIMIT 1";

    $stmtPromocion = $conn->prepare($sqlPromocion);
    $stmtPromocion->bind_param("i", $id);
    $stmtPromocion->execute();

    $resultadoPromocion = $stmtPromocion->get_result();

    if ($resultadoPromocion->num_rows > 0) {
        $filaPromocion = $resultadoPromocion->fetch_assoc();
        $idPromocion = (int) $filaPromocion["id"];
    }
}

// ============================================================
// DATOS DE LA ÚLTIMA JORNADA / FASE
// ============================================================
if ($datos["tipo"] == "segunda" || $datos["tipo"] == "promocion") {

    $sql = "SELECT fase
            FROM partidos
            WHERE competicion_temporada_id = ?
            ORDER BY id DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $ultima = $stmt->get_result()->fetch_assoc();

    if ($ultima) {
        $faseActual = $ultima["fase"];
    } else {
        $faseActual = null;
    }

} elseif ($datos["competicion"] == "Champions League") {

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

// ============================================================
// CLASIFICACIÓN
// ============================================================
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

    } elseif ($datos["tipo"] == "segunda") {

        // Segunda División tiene una única clasificación
        $clasificacion = obtenerClasificacion($conn, $id);

    } elseif ($datos["tipo"] == "promocion") {

        // Promoción no necesita clasificación

    } else {

        $clasificacion = obtenerClasificacion($conn, $id);

    }
}

// ============================================================
// ZONAS DE CLASIFICACIÓN
// ============================================================
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
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+i:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TZL2J8ZT');</script>
    <!-- End Google Tag Manager -->
</head>

<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TZL2J8ZT"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <h1><?php echo $datos['competicion']; ?></h1>
    <h2>Temporada <?php echo $datos['temporada']; ?></h2>
    <a href="index.php" class="btn-inicio">
        <img src="img/inicio.png" alt="Inicio">
    </a>

    <?php
// ============================================================
// BOTÓN PARA IR A SEGUNDA DIVISIÓN DE ESTA MISMA TEMPORADA
// ============================================================

$idSegunda = null;

$sqlSegunda = "SELECT ct.id
               FROM competiciones_temporadas ct
               JOIN competiciones c 
                    ON ct.competicion_id = c.id
               WHERE ct.segunda_de_id = ?
               AND c.tipo = 'segunda'
               LIMIT 1";

$stmtSegunda = $conn->prepare($sqlSegunda);
$stmtSegunda->bind_param("i", $id);
$stmtSegunda->execute();

$resultadoSegunda = $stmtSegunda->get_result();

if ($resultadoSegunda->num_rows > 0) {
    $filaSegunda = $resultadoSegunda->fetch_assoc();
    $idSegunda = (int)$filaSegunda["id"];
}
?>

<?php if ($idSegunda !== null && $datos["tipo"] == "liga") { ?>

<div class="tarjetas">

    <a href="temporada.php?id=<?php echo $idSegunda; ?>" class="tarjeta-segunda">
        Segunda División
    </a>

</div>

<?php } ?>

<?php if ($idPlayoffsMembers !== null) { ?>

<div class="tarjetas">

    <a href="competiciones/members_playoffs.php?id=<?php echo $idPlayoffsMembers; ?>"
       class="tarjeta-segunda">
        Playoffs
    </a>

</div>

<?php } ?>

<?php
// ============================================================
// BOTÓN PARA IR A LA PROMOCIÓN DE ESTA SEGUNDA DIVISIÓN
// ============================================================
?>

<?php if ($idPromocion !== null) { ?>

<div class="tarjetas">

    <a href="temporada.php?id=<?php echo $idPromocion; ?>" class="tarjeta-segunda">
        Promoción Ascenso
    </a>

</div>

<?php } ?>
    <!-- ======================================================
         SEGUNDA DIVISIÓN
         ====================================================== -->
<?php if ($datos["tipo"] == "segunda" || $datos["tipo"] == "promocion") { ?>

    <?php if ($datos["tipo"] != "promocion") { ?>
    <h2>Fases</h2>
<?php } ?>

    <?php

    if ($datos["tipo"] == "segunda") {

        $fases = [
            "RP"  => "Ronda Previa",
            "R1"  => "Ronda 1",
            "R2"  => "Ronda 2",
            "R3"  => "Ronda 3",
            "OCT" => "Octavos",
            "QF"  => "Cuartos",
            "SF"  => "Semifinales",
            "F"   => "Final"
        ];

    } else {

        $fases = [
            "F" => "Final"
        ];

    }

    ?>

    <div class="contenedor-fases">

        <?php foreach ($fases as $codigoFase => $nombreFase) { ?>

            <?php

            $sql = "SELECT
                        pa.id,
                        pl.nombre AS local,
                        pl.imagen AS imagen_local,
                        pv.nombre AS visitante,
                        pv.imagen AS imagen_visitante
                    FROM partidos pa

                    JOIN participantes l
                        ON pa.local_id = l.id

                    JOIN pokemon pl
                        ON l.pokemon_id = pl.id

                    JOIN participantes v
                        ON pa.visitante_id = v.id

                    JOIN pokemon pv
                        ON v.pokemon_id = pv.id

                    WHERE pa.competicion_temporada_id = ?
                    AND pa.fase = ?

                    ORDER BY pa.id";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $id, $codigoFase);
            $stmt->execute();

            $partidosFase = $stmt->get_result();

            if ($partidosFase->num_rows === 0) {
                continue;
            }

            ?>

            <div class="fase <?php echo ($datos["tipo"] == "promocion") ? 'fase-promocion' : ''; ?>">

    <h3 class="titulo-fase">
        <?php echo $nombreFase; ?>
    </h3>

    <div class="partidos-fase <?php
        echo ($partidosFase->num_rows === 1) ? 'un-solo-partido' : '';

        if ($datos["tipo"] == "promocion") {
            echo ' final-promocion';
        }
    ?>">

                    <?php while ($partido = $partidosFase->fetch_assoc()) { ?>

                        <?php

                        $sqlSets = "SELECT
                                        vida_local,
                                        vida_visitante
                                    FROM sets
                                    WHERE partido_id = ?";

                        $stmtSets = $conn->prepare($sqlSets);
                        $stmtSets->bind_param("i", $partido["id"]);
                        $stmtSets->execute();

                        $sets = $stmtSets->get_result();

                        $marcadores = [];

                        $setsGanadosLocal = 0;
                        $setsGanadosVisitante = 0;

                        while ($set = $sets->fetch_assoc()) {

                            $marcadores[] = [
                                "local" => $set["vida_local"],
                                "visitante" => $set["vida_visitante"]
                            ];

                            if (
                                $set["vida_local"] == 0 &&
                                $set["vida_visitante"] > 0
                            ) {

                                $setsGanadosVisitante++;

                            } elseif (
                                $set["vida_visitante"] == 0 &&
                                $set["vida_local"] > 0
                            ) {

                                $setsGanadosLocal++;

                            }

                        }

                        $totalSets = count($marcadores);

                        ?>

                        <div class="partido">

                            <div class="equipo local">

                                <img
                                    src="img/pokemon/<?php echo $partido["imagen_local"]; ?>"
                                    width="70"
                                    alt="<?php echo $partido["local"]; ?>"
                                >

                                <span>
                                    <?php echo $partido["local"]; ?>
                                </span>

                            </div>


                            <div class="resultado">

                                <div class="resultado-sets">

                                    <?php if ($totalSets === 1) { ?>

                                        <?php echo $marcadores[0]["local"]; ?>
                                        -
                                        <?php echo $marcadores[0]["visitante"]; ?>

                                    <?php } else { ?>

                                        <?php echo $setsGanadosLocal; ?>
                                        -
                                        <?php echo $setsGanadosVisitante; ?>

                                    <?php } ?>

                                </div>


                                <?php if ($totalSets > 1) { ?>

                                    <div class="resultado-detalle">

                                        <?php foreach ($marcadores as $set) { ?>

                                            <div>
                                                <?php echo $set["local"]; ?>
                                                -
                                                <?php echo $set["visitante"]; ?>
                                            </div>

                                        <?php } ?>

                                    </div>

                                <?php } ?>

                            </div>


                            <div class="equipo visitante">

                                <span>
                                    <?php echo $partido["visitante"]; ?>
                                </span>

                                <img
                                    src="img/pokemon/<?php echo $partido["imagen_visitante"]; ?>"
                                    width="70"
                                    alt="<?php echo $partido["visitante"]; ?>"
                                >

                            </div>

                        </div>

                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    </div>

<?php } ?>

<?php if ($datos["tipo"] == "members") { ?>

    <br><h2>Última Jornada</h2>

    <?php if ($ultimaJornadaMembers === null) { ?>

        <div class="contenedor-jornada-actual">

            <div class="jornada">

                <h3>
                    Todavía no se ha disputado ninguna jornada
                </h3>

            </div>

        </div>

    <?php } else { ?>

        <div class="contenedor-jornada-actual members-ultima-jornada<?php echo count($partidosUltimaJornadaMembers) == 1 ? ' una-jornada' : ''; ?>">

    <div class="jornada">

        <h3>
            Jornada <?php echo $ultimaJornadaMembers; ?>
        </h3>

        <div class="partidos-jornada-actual">

            <?php foreach (
                $partidosUltimaJornadaMembers
                as $partido
            ) { ?>

                <div class="partido">

                    <!-- LOCAL -->

                    <div class="equipo local">

    <?php if (!empty($partido["imagen_local"])) { ?>

        <img
            src="img/members/<?php echo htmlspecialchars($partido["imagen_local"]); ?>"
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
                            echo $partido["kills_local"];
                            ?>

                            -

                            <?php
                            echo $partido["kills_visitante"];
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
            src="img/members/<?php echo htmlspecialchars($partido["imagen_visitante"]); ?>"
            alt="<?php echo htmlspecialchars($partido["visitante"]); ?>">

    <?php } ?>

</div>

                </div>

            <?php } ?>

        </div>

    </div>

</div>

    <?php } ?>

<?php } ?>

<?php if (
    $datos["tipo"] != "segunda" &&
    $datos["tipo"] != "promocion" &&
    $datos["tipo"] != "members"
) { ?>

    <br><h2>Última Jornada</h2>

<?php

$claseUltima = "";

$sql = "SELECT COUNT(*) total
        FROM partidos
        WHERE competicion_temporada_id = ?";

if (
    $datos["competicion"] == "Champions League" ||
    $datos["tipo"] == "segunda" ||
    $datos["tipo"] == "promocion"
) {

    $sql .= " AND fase = ?";

} else {

    $sql .= " AND jornada = ?";
}

$stmt = $conn->prepare($sql);

if (
    $datos["competicion"] == "Champions League" ||
    $datos["tipo"] == "segunda" ||
    $datos["tipo"] == "promocion"
) {

    $stmt->bind_param("is", $id, $faseActual);

} else {

    $stmt->bind_param("ii", $id, $jornadaActual);
}

$stmt->execute();

$totalPartidos = $stmt->get_result()->fetch_assoc()["total"];

$claseUltima = ($totalPartidos == 1)
    ? " ultima-eliminatoria"
    : "";

?>
<div class="contenedor-jornada-actual<?php echo $claseUltima; ?>">
    <div class="jornada">
            <h3>

<?php

if (
    $datos["tipo"] == "segunda" ||
    $datos["tipo"] == "promocion"
) {

    $nombresFase = [
        "RP"  => "Ronda Previa",
        "R1"  => "Ronda 1",
        "R2"  => "Ronda 2",
        "R3"  => "Ronda 3",
        "OCT" => "Octavos",
        "QF"  => "Cuartos",
        "SF"  => "Semifinales",
        "F"   => "Final"
    ];

    echo $nombresFase[$faseActual] ?? $faseActual;

} elseif ($datos["competicion"] == "Champions League") {

    if ($faseActual == "SF") {

        echo "Semifinales";

    } elseif ($faseActual == "F") {

        echo "Final";

    } else {

        echo "Jornada ".$jornadaActual;
    }

} elseif (
    $datos["tipo"] == "legendary" &&
    $datos["jornadas"] == 1
) {

    echo "Final";

} else {

    echo "Jornada ".$jornadaActual;

}
?>
</h3>
            <div class="partidos-jornada-actual">
    <?php

if (
    $datos["tipo"] == "segunda" ||
    $datos["tipo"] == "promocion"
) {

    /*
     * Segunda División y Promoción
     * funcionan mediante fases, no mediante jornadas.
     */

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

} elseif ($datos["competicion"] == "Champions League") {

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

    /*
     * Liga, Copa y Legendary
     */

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
<?php } ?>

<?php if (
    $datos["tipo"] != "promocion" &&
    $datos["tipo"] != "segunda" &&
    $datos["tipo"] != "members"
) { ?>

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
    if (!empty($fila["plaza_especial"])) {

    if ($fila["plaza_especial"] == "Champions League") {
        $clases = array_diff($clases, ["mundial"]);
    }

    $clases[] = strtolower(str_replace(" ", "-", $fila["plaza_especial"]));
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

<?php } ?>

<?php if ($datos["tipo"] == "members") { ?>

    <?php
    $mostrarDosColumnasCaos = (
        $datos["competicion"] === "Caos League" &&
        (int)$datos["temporada"] === 1 &&
        count($clasificacionMembers) > 7
    );

    $columnasClasificacionMembers = $mostrarDosColumnasCaos
        ? [array_slice($clasificacionMembers, 0, 7), array_slice($clasificacionMembers, 7)]
        : [$clasificacionMembers];
    ?>

    <h2>Clasificación</h2>

    <div class="clasificacion-contenedor clasificacion-members">

        <?php foreach ($columnasClasificacionMembers as $indiceColumna => $filasColumna) { ?>
        <div class="clasificacion-columna">

            <table>

                <tr>
                    <th>Pos</th>
                    <th>Entrenador</th>
                    <th>Com</th>
                    <th>V</th>
                    <th>E</th>
                    <th>D</th>
                    <th>Set+</th>
                    <th>Set-</th>
                    <th>Dif</th>
                    <th>Pts</th>
                </tr>

                <?php

                $posicion = $mostrarDosColumnasCaos ? ($indiceColumna * 7 + 1) : 1;

                foreach ($filasColumna as $fila) {

    $clases = [];

    // Solo colorear las posiciones 2 y 3
    // de Members League temporada 2 (ID 22).
    if ($id === 22 && $posicion >= 2 && $posicion <= 3) {
        $clases[] = "champions";
    }

?>
    <tr class="<?php echo implode(" ", $clases); ?>">

                        <td>
                            <strong>
                                <?php echo $posicion; ?>
                            </strong>
                        </td>

                        <td>

                            <div class="pokemon-clasificacion">

                                <?php if (!empty($fila["imagen"])) { ?>

                                    <img
                                        src="img/members/<?php echo htmlspecialchars($fila["imagen"]); ?>"
                                        alt="<?php echo htmlspecialchars($fila["nombre"]); ?>"
                                    >

                                <?php } ?>

                                <span>
    <?php echo htmlspecialchars($fila["nombre"]); ?>
</span>

<?php if (!empty($fila["pais"])) { ?>
    <img
        src="img/banderas/<?php echo htmlspecialchars($fila["pais"], ENT_QUOTES, 'UTF-8'); ?>"
        alt="Bandera de <?php echo htmlspecialchars($fila["nombre"], ENT_QUOTES, 'UTF-8'); ?>"
        class="bandera-miembro"
    >
<?php } ?>

                            </div>

                        </td>

                        <td>
                            <?php echo $fila["pj"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["pg"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["pe"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["pp"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["kaf"]; ?>
                        </td>

                        <td>
                            <?php echo $fila["kec"]; ?>
                        </td>

                        <td>
                            <strong>
                                <?php
                                echo ($fila["dif"] > 0 ? "+" : "") . $fila["dif"];
                                ?>
                            </strong>
                        </td>

                        <td><strong><?php echo $fila["pts"]; ?><?php
                            $idMiembroFila = (int)$fila["id"];
                            if (!empty($sancionesMembers[$idMiembroFila])) {
                                $textoMotivos = implode("; ", $sancionesMembers[$idMiembroFila]);
                                ?><span class="sancion-asterisco" title="<?php echo htmlspecialchars($textoMotivos, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Sanción: <?php echo htmlspecialchars($textoMotivos, ENT_QUOTES, 'UTF-8'); ?>" style="display:inline; margin:0; padding:0; cursor:help; color:blue; font-weight:bold;">*</span><?php } ?></strong></td>

                    </tr>

                <?php

                    $posicion++;

                }

                ?>

            </table>

        </div>
        <?php } ?>

    </div>

<?php if ($id === 22) { ?>

    <div class="leyenda-clasificacion">

        <div class="item-leyenda">
            <span class="color-leyenda campeon"></span>
            Final
        </div>

        <div class="item-leyenda">
            <span class="color-leyenda champions"></span>
            Semifinales
        </div>

    </div>

<?php } ?>

<?php } ?>

<?php if ($datos["tipo"] == "members") { ?>

    <br><h2>Poké Ball D'Or</h2>

    <?php // La vista empieza siempre en el top 30; el resto se despliega dinámicamente con JavaScript. ?>
    <div class="clasificacion-contenedor clasificacion-members kill-count">
        <div class="clasificacion-columna">

            <table>

                <th>Pos</th>
                <th>Pokémon</th>
                <th>Entrenador</th>
                <th>Com</th>
                <th>Kills</th>

                <?php

                $posicionKill = 1;

                $totalKillCount = count($killCountMembers);
                foreach ($killCountMembers as $fila) {
                    $filaOcultaKillCount = $posicionKill > 30;
                ?>

                    <tr<?php echo $filaOcultaKillCount ? ' class="fila-kill-count-oculta" hidden' : ''; ?>>

    <td>
        <strong>
            <?php echo $posicionKill; ?>
        </strong>
    </td>

    <td>

        <div class="pokemon-clasificacion">

            <img
                src="img/pokemon/<?php echo htmlspecialchars($fila["imagen_pokemon"]); ?>"
                alt="<?php echo htmlspecialchars($fila["pokemon"]); ?>"
            >

            <span>
                <?php echo htmlspecialchars($fila["pokemon"]); ?>
            </span>

        </div>

    </td>

    <td>

        <div class="pokemon-clasificacion members-miembro-kill">

            <?php if (!empty($fila["imagen_miembro"])) { ?>

                <img
                    src="img/members/<?php echo htmlspecialchars($fila["imagen_miembro"]); ?>"
                    alt="<?php echo htmlspecialchars($fila["miembro"]); ?>"
                >

            <?php } ?>

            <span><?php 
                echo htmlspecialchars($fila["miembro"], ENT_QUOTES, 'UTF-8'); 
                
                $pokemonIdFilaKill = (int)($fila["pokemon_id"] ?? 0);
                $miembroIdActual = (int)($fila["miembro_id"] ?? 0);

                if (isset($entrenadoresPorPokemonMembers[$pokemonIdFilaKill]) && count($entrenadoresPorPokemonMembers[$pokemonIdFilaKill]) > 1) {
                    $nombresEntrenadores = [];
                    foreach ($entrenadoresPorPokemonMembers[$pokemonIdFilaKill] as $mId => $mNombre) {
                        if ($mId !== $miembroIdActual && $mNombre !== '') {
                            $nombresEntrenadores[] = $mNombre;
                        }
                    }

                    if (!empty($nombresEntrenadores)) {
                        $textoEntrenadores = 'Otros entrenadores: ' . implode(', ', $nombresEntrenadores);
                        ?><span class="pokemon-varios-entrenadores-asterisco" title="<?php echo htmlspecialchars($textoEntrenadores, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-label="<?php echo htmlspecialchars($textoEntrenadores, ENT_QUOTES, 'UTF-8'); ?>" 
                        style="display:inline; margin:0; padding:0; cursor:help; color:blue; font-weight:bold;">*</span><?php
                    }
                }
            ?></span>

        </div>

    </td>

    <td>
            <?php echo $fila["combates"]; ?>
    </td>

    <td>
        <strong>
            <?php echo $fila["kills"]; ?>
        </strong>
    </td>

</tr>

                <?php

                    $posicionKill++;
                }
                ?>

            </table>
            <?php if ($totalKillCount > 30) { ?>
            <div class="kill-count-control"><br>
                <button type="button" id="alternar-kill-count" class="btn-kill-count-toggle" aria-expanded="false">
                    Mostrar todos (<?php echo (int)$totalKillCount; ?>)
                </button>
            </div>
            <script>
            (function () {
                var boton = document.getElementById('alternar-kill-count');
                if (!boton) return;
                var expandido = false;
                boton.addEventListener('click', function () {
                    expandido = !expandido;
                    document.querySelectorAll('.fila-kill-count-oculta').forEach(function (fila) {
                        fila.hidden = !expandido;
                    });
                    boton.textContent = expandido ? 'Mostrar top 30' : 'Mostrar todos (<?php echo (int)$totalKillCount; ?>)';
                    boton.setAttribute('aria-expanded', expandido ? 'true' : 'false');
                });
            })();
            </script>
            <?php } ?>

        </div>

    </div>

<?php } ?>

<?php if ($datos["tipo"] == "members") { ?>

    <br><h2>Jornadas</h2>

    <div class="contenedor-jornadas">

        <?php foreach (
            $jornadasMembers as $numeroJornada => $partidosJornada
        ) { ?>

            <div class="jornada">

                <h3>
                    Jornada <?php echo $numeroJornada; ?>
                </h3>


                <?php foreach (
                    $partidosJornada
                    as $partido
                ) { ?>

                    <div class="partido">

                        <!-- LOCAL -->

                        <div class="equipo local">

    <?php if (!empty($partido["imagen_local"])) { ?>

        <img
            src="img/members/<?php echo htmlspecialchars($partido["imagen_local"]); ?>"
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
                                echo $partido["kills_local"];
                                ?>

                                -

                                <?php
                                echo $partido["kills_visitante"];
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
            src="img/members/<?php echo htmlspecialchars($partido["imagen_visitante"]); ?>"
            alt="<?php echo htmlspecialchars($partido["visitante"]); ?>">

    <?php } ?>

</div>

                    </div>


                    <?php

                    /*
                     * ==================================
                     * KILLS DEL PARTIDO
                     * ==================================
                     */

                    $partidoId =
                        (int)$partido["id"];

                    if (
                        isset(
                            $killsPorPartidoMembers[$partidoId]
                        )
                    ) {

                    ?>

                        <div class="members-kills-partido">

                            <?php

$ordenMiembros = [
    (int)$partido["local_id"],
    (int)$partido["visitante_id"]
];

foreach ($ordenMiembros as $miembroId) {

    if (
        !isset(
            $killsPorPartidoMembers[$partidoId][$miembroId]
        )
    ) {
        continue;
    }

    $kills = $killsPorPartidoMembers[$partidoId][$miembroId];

?>

                                <div class="members-kills-miembro">

                                    <strong>

                                        <?php
                                        if (
                                            $miembroId ==
                                            $partido["local_id"]
                                        ) {

                                            echo htmlspecialchars(
                                                $partido["local"]
                                            );

                                        } else {

                                            echo htmlspecialchars(
                                                $partido["visitante"]
                                            );

                                        }
                                        ?>

                                    </strong>

                                    :

                                    <?php foreach (
                                        $kills
                                        as $indice => $kill
                                    ) { ?>

                                        <?php if (
                                            $indice > 0
                                        ) {
                                            echo ", ";
                                        } ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $kill["pokemon"]
                                        );
                                        ?>

                                        <?php if ($kill["kills"] > 0) { ?>
    (<?php echo $kill["kills"]; ?>)
<?php } ?>

                                    <?php } ?>

                                </div>

                            <?php } ?>

                        </div>

                    <?php } ?>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

<?php } ?>

        <?php if (
    $datos["tipo"] != "copa" &&
    $datos["tipo"] != "promocion" &&
    $datos["competicion"] != "Legendary League" &&
    $datos["tipo"] != "segunda" &&
    $datos["tipo"] != "members"
) { ?>
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

        <?php if (
    $datos["tipo"] != "segunda" &&
    $datos["tipo"] != "promocion" &&
    $datos["tipo"] != "members"
) { ?>
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

<?php } ?>

<?php include 'includes/footer.php'; ?>
</body>
</html>