<?php

require_once "../config/conexion.php";
require_once "proteger.php";


/*
 * Datos recibidos
 */

$competicion_temporada = (int)($_POST["competicion_temporada"] ?? 0);
$jornada = (int)($_POST["jornada"] ?? 0);

$local = (int)($_POST["local"] ?? 0);
$visitante = (int)($_POST["visitante"] ?? 0);

$local_pokemon = $_POST["pokemon_local"] ?? [];
$visitante_pokemon = $_POST["pokemon_visitante"] ?? [];

$kills_local = $_POST["kills_local"] ?? [];
$kills_visitante = $_POST["kills_visitante"] ?? [];


/*
 * Validaciones básicas
 */

if (
    $competicion_temporada <= 0 ||
    $jornada <= 0 ||
    $local <= 0 ||
    $visitante <= 0
) {
    die("Faltan datos del combate.");
}

if ($local === $visitante) {
    die("El local y el visitante deben ser diferentes.");
}

if (
    count($local_pokemon) < 1 || count($local_pokemon) > 4 ||
    count($visitante_pokemon) < 1 || count($visitante_pokemon) > 4
) {
    die("Debes seleccionar entre 1 y 4 Pokémon por equipo.");
}

if (count($kills_local) < 1 || count($kills_local) > 4) {
    die("Las kills del local no son válidas.");
}

if (count($kills_visitante) < 1 || count($kills_visitante) > 4) {
    die("Las kills del visitante no son válidas.");
}


/*
 * Comprobar que ambos miembros existen
 */

$sql = "SELECT id
        FROM members_miembros
        WHERE id IN (?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $local,
    $visitante
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 2) {
    die("Uno de los miembros no existe.");
}


/*
 * Comprobar que ambos tienen equipo
 */

function obtenerEquipo($conn, $miembro_id, $competicion_temporada)
{
    $sql = "SELECT id
            FROM members_equipos
            WHERE miembro_id = ?
            AND competicion_temporada_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $miembro_id,
        $competicion_temporada
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        die("El miembro $miembro_id no tiene un equipo válido para esta temporada.");
    }

    $fila = $resultado->fetch_assoc();

    return (int)$fila["id"];
}

$equipo_local = obtenerEquipo(
    $conn,
    $local,
    $competicion_temporada
);

$equipo_visitante = obtenerEquipo(
    $conn,
    $visitante,
    $competicion_temporada
);


/*
 * Comprobar que los 4 Pokémon pertenecen al equipo
 */

function comprobarPokemonEquipo(
    $conn,
    $pokemon,
    $equipo
) {

    $pokemon = array_map("intval", $pokemon);

    if (count(array_unique($pokemon)) !== count($pokemon)) {
        die("No puedes repetir Pokémon dentro del mismo equipo.");
    }

    foreach ($pokemon as $pokemon_id) {

        $sql = "SELECT id
                FROM members_pokemon
                WHERE equipo_id = ?
                AND pokemon_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ii",
            $equipo,
            $pokemon_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows !== 1) {
            die("Se ha enviado un Pokémon que no pertenece al equipo.");
        }
    }

    return $pokemon;
}

$local_pokemon = comprobarPokemonEquipo(
    $conn,
    $local_pokemon,
    $equipo_local
);

$visitante_pokemon = comprobarPokemonEquipo(
    $conn,
    $visitante_pokemon,
    $equipo_visitante
);


/*
 * Comprobar kills
 */

$kills_local = array_map("intval", $kills_local);
$kills_visitante = array_map("intval", $kills_visitante);

foreach ($kills_local as $kills) {

    if ($kills < 0 || $kills > 4) {
        die("Las kills deben estar entre 0 y 4.");
    }
}

foreach ($kills_visitante as $kills) {

    if ($kills < 0 || $kills > 4) {
        die("Las kills deben estar entre 0 y 4.");
    }
}


/*
 * Calcular marcador
 */

$total_local = array_sum($kills_local);
$total_visitante = array_sum($kills_visitante);


/*
 * Un combate no puede superar 4 kills
 */

if ($total_local > 4 || $total_visitante > 4) {
    die("Un participante no puede superar las 4 kills.");
}


/*
 * Comprobar si el combate ya existe
 */

$sql = "SELECT id
        FROM members_partidos
        WHERE competicion_temporada_id = ?
        AND jornada = ?
        AND local_id = ?
        AND visitante_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iiii",
    $competicion_temporada,
    $jornada,
    $local,
    $visitante
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    die("Este combate ya está registrado.");
}


/*
 * Comprobar también el partido inverso
 *
 * Evita registrar:
 * Feyd vs Honrey
 *
 * y después:
 * Honrey vs Feyd
 *
 * en la misma jornada.
 */

$sql = "SELECT id
        FROM members_partidos
        WHERE competicion_temporada_id = ?
        AND jornada = ?
        AND local_id = ?
        AND visitante_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iiii",
    $competicion_temporada,
    $jornada,
    $visitante,
    $local
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    die("Este enfrentamiento ya está registrado en esta jornada.");
}


/*
 * Determinar resultado
 *
 * 4 kills = victoria
 * menos de 4 ambos = empate
 */

if ($total_local === 4 && $total_visitante < 4) {

    $resultado_partido = "victoria_local";

} elseif ($total_visitante === 4 && $total_local < 4) {

    $resultado_partido = "victoria_visitante";

} else {

    $resultado_partido = "empate";
}


/*
 * Transacción
 */

$conn->begin_transaction();

try {


    /*
     * Insertar partido
     */

    $sql = "INSERT INTO members_partidos
            (
                competicion_temporada_id,
                jornada,
                local_id,
                visitante_id,
                kills_local,
                kills_visitante
            )
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiiiii",
        $competicion_temporada,
        $jornada,
        $local,
        $visitante,
        $total_local,
        $total_visitante
    );

    $stmt->execute();

    $partido_id = $conn->insert_id;


    /*
     * Insertar Pokémon participantes - LOCAL
     */

    $sql = "INSERT INTO members_participantes
            (
                partido_id,
                miembro_id,
                pokemon_id
            )
            VALUES (?, ?, ?)";

    $stmtParticipante = $conn->prepare($sql);

    foreach ($local_pokemon as $pokemon_id) {

        $stmtParticipante->bind_param(
            "iii",
            $partido_id,
            $local,
            $pokemon_id
        );

        $stmtParticipante->execute();
    }


    /*
     * Insertar Pokémon participantes - VISITANTE
     */

    foreach ($visitante_pokemon as $pokemon_id) {

        $stmtParticipante->bind_param(
            "iii",
            $partido_id,
            $visitante,
            $pokemon_id
        );

        $stmtParticipante->execute();
    }


    /*
 * Insertar kills - LOCAL
 */
$sql = "INSERT INTO members_kills (partido_id, miembro_id, pokemon_id, kills) VALUES (?, ?, ?, ?)";
$stmtKills = $conn->prepare($sql);

$total_local_pkmn = count($local_pokemon);
for ($i = 0; $i < $total_local_pkmn; $i++) {
    $pokemon_id = $local_pokemon[$i];
    $kills = $kills_local[$i] ?? 0;

    $stmtKills->bind_param("iiii", $partido_id, $local, $pokemon_id, $kills);
    $stmtKills->execute();
}

/*
 * Insertar kills - VISITANTE
 */
$total_visitante_pkmn = count($visitante_pokemon);
for ($i = 0; $i < $total_visitante_pkmn; $i++) {
    $pokemon_id = $visitante_pokemon[$i];
    $kills = $kills_visitante[$i] ?? 0;

    $stmtKills->bind_param("iiii", $partido_id, $visitante, $pokemon_id, $kills);
    $stmtKills->execute();
}


    /*
     * Actualizar fecha de la competición
     */

    $sql = "UPDATE competiciones_temporadas
            SET fecha_actualizacion = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $competicion_temporada
    );

    $stmt->execute();


    /*
     * Confirmar
     */

    $conn->commit();


} catch (Exception $e) {

    $conn->rollback();

    die(
        "Error al guardar el combate: " .
        $e->getMessage()
    );
}


/*
 * Volver al formulario
 */

header("Location: registrar_members.php");
exit;