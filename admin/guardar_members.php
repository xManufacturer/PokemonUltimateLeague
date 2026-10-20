
<?php

require_once "../config/conexion.php";
require_once "proteger.php";

/*
 * Datos recibidos
 */

$competicion_temporada = (int)($_POST["competicion_temporada"] ?? 0);
$fase = $_POST["fase"] ?? "";
$jornada = isset($_POST["jornada"]) && $_POST["jornada"] !== ""
    ? (int)$_POST["jornada"]
    : null;

$local = (int)($_POST["local"] ?? 0);
$visitante = (int)($_POST["visitante"] ?? 0);

$local_pokemon = $_POST["pokemon_local"] ?? [];
$visitante_pokemon = $_POST["pokemon_visitante"] ?? [];

$kills_local = $_POST["kills_local"] ?? [];
$kills_visitante = $_POST["kills_visitante"] ?? [];

$sets = $_POST["sets"] ?? [];

/*
 * Validaciones básicas
 */

if (
    $competicion_temporada <= 0 ||
    $local <= 0 ||
    $visitante <= 0
) {
    die("Faltan datos del combate.");
}

if (!in_array($fase, ["L", "SF", "F"], true)) {
    die("La fase seleccionada no es válida.");
}

if ($fase === "L" && ($jornada === null || $jornada <= 0)) {
    die("Debes seleccionar una jornada para la fase liga.");
}

if ($fase !== "L") {
    $jornada = null;
}

if ($local === $visitante) {
    die("El local y el visitante deben ser diferentes.");
}

if ($fase === "L") {

    if (
        !is_array($local_pokemon) ||
        !is_array($visitante_pokemon) ||
        !is_array($kills_local) ||
        !is_array($kills_visitante)
    ) {
        die("Los datos de los equipos no son válidos.");
    }

    if (
        count($local_pokemon) < 1 || count($local_pokemon) > 4 ||
        count($visitante_pokemon) < 1 || count($visitante_pokemon) > 4
    ) {
        die("Debes seleccionar entre 1 y 4 Pokémon por equipo.");
    }

    if (
        count($kills_local) !== count($local_pokemon) ||
        count($kills_visitante) !== count($visitante_pokemon)
    ) {
        die("Los Pokémon y sus kills no coinciden.");
    }
}

/*
 * Comprobar que la temporada pertenece a Members League
 * competicion_id = 17
 */

$sql = "SELECT id
        FROM competiciones_temporadas
        WHERE id = ?
        AND competicion_id = 17";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion_temporada);
$stmt->execute();

if ($stmt->get_result()->num_rows !== 1) {
    die("La temporada seleccionada no pertenece a Members League.");
}

/*
 * Comprobar que ambos miembros existen
 */

$sql = "SELECT id
        FROM members_miembros
        WHERE id IN (?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $local, $visitante);
$stmt->execute();

if ($stmt->get_result()->num_rows !== 2) {
    die("Uno de los miembros no existe.");
}

/*
 * Comprobar que ambos tienen equipo en la temporada
 */

function obtenerEquipo($conn, $miembro_id, $competicion_temporada)
{
    $sql = "SELECT id
            FROM members_equipos
            WHERE miembro_id = ?
            AND competicion_temporada_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $miembro_id, $competicion_temporada);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        die(
            "El miembro $miembro_id no tiene un equipo válido para esta temporada."
        );
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
 * Comprobar que los Pokémon pertenecen a sus equipos
 */

function comprobarPokemonEquipo($conn, $pokemon, $equipo)
{
    $pokemon = array_map("intval", $pokemon);

    if (
        in_array(0, $pokemon, true) ||
        count(array_unique($pokemon)) !== count($pokemon)
    ) {
        die("Los Pokémon seleccionados no son válidos o están repetidos.");
    }

    foreach ($pokemon as $pokemon_id) {
        $sql = "SELECT id
                FROM members_pokemon
                WHERE equipo_id = ?
                AND pokemon_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $equipo, $pokemon_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows !== 1) {
            die("Se ha enviado un Pokémon que no pertenece al equipo.");
        }
    }

    return $pokemon;
}

if ($fase === "L") {
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
} else {
    /*
     * Validar los sets cuando ya conocemos los equipos.
     */
    if (!is_array($sets) || !isset($sets[1], $sets[2])) {
        die("Debes enviar los datos del Set 1 y del Set 2.");
    }

    $setsGanadosLocalValidacion = 0;
    $setsGanadosVisitanteValidacion = 0;

    foreach ([1, 2, 3] as $numeroSet) {
        // El tercer set puede no enviarse si alguien ganó los dos primeros.
        if (!isset($sets[$numeroSet])) {
            if ($numeroSet === 3) {
                break;
            }

            die("Faltan los datos del Set " . $numeroSet . ".");
        }

        $datosSet = $sets[$numeroSet];

        $pLocalSet = $datosSet["pokemon_local"] ?? [];
        $pVisSet = $datosSet["pokemon_visitante"] ?? [];
        $kLocalSet = $datosSet["kills_local"] ?? [];
        $kVisSet = $datosSet["kills_visitante"] ?? [];

        if (
            !is_array($pLocalSet) || !is_array($pVisSet) ||
            !is_array($kLocalSet) || !is_array($kVisSet)
        ) {
            die("Los datos del Set " . $numeroSet . " no son válidos.");
        }

        if (
            count($pLocalSet) < 1 || count($pLocalSet) > 4 ||
            count($pVisSet) < 1 || count($pVisSet) > 4
        ) {
            die("En el Set " . $numeroSet .
                " debes seleccionar entre 1 y 4 Pokémon por equipo.");
        }

        if (
            count($kLocalSet) !== count($pLocalSet) ||
            count($kVisSet) !== count($pVisSet)
        ) {
            die("Los Pokémon y las kills no coinciden en el Set " . $numeroSet . ".");
        }

        $pLocalSet = comprobarPokemonEquipo($conn, $pLocalSet, $equipo_local);
        $pVisSet = comprobarPokemonEquipo($conn, $pVisSet, $equipo_visitante);

        $kLocalSet = array_map("intval", $kLocalSet);
        $kVisSet = array_map("intval", $kVisSet);

        foreach (array_merge($kLocalSet, $kVisSet) as $kills) {
            if ($kills < 0 || $kills > 4) {
                die("Las kills de cada Pokémon deben estar entre 0 y 4.");
            }
        }

        $totalLocalSet = array_sum($kLocalSet);
        $totalVisitanteSet = array_sum($kVisSet);

        if ($totalLocalSet > 4 || $totalVisitanteSet > 4) {
            die("En cada set, cada equipo puede sumar como máximo 4 kills.");
        }

        if ($totalLocalSet === $totalVisitanteSet) {
            die("El Set " . $numeroSet . " no puede terminar empatado.");
        }

        if ($totalLocalSet > $totalVisitanteSet) {
            $setsGanadosLocalValidacion++;
        } else {
            $setsGanadosVisitanteValidacion++;
        }

        if (
            $setsGanadosLocalValidacion === 2 ||
            $setsGanadosVisitanteValidacion === 2
        ) {
            if ($numeroSet < 3 && isset($sets[$numeroSet + 1])) {
                die("No puedes registrar sets adicionales después de conseguir dos victorias.");
            }

            break;
        }
    }

    if (
        $setsGanadosLocalValidacion < 2 &&
        $setsGanadosVisitanteValidacion < 2
    ) {
        die("El enfrentamiento debe terminar con dos sets ganados.");
    }
}

/*
 * Comprobar kills y calcular marcador
 *
 * En Liga se usan los campos antiguos.
 * En Playoffs, los totales se calculan dentro de cada set.
 */

if ($fase === "L") {

    $kills_local = array_map("intval", $kills_local);
    $kills_visitante = array_map("intval", $kills_visitante);

    foreach (array_merge($kills_local, $kills_visitante) as $kills) {
        if ($kills < 0 || $kills > 4) {
            die("Las kills deben estar entre 0 y 4.");
        }
    }

    $total_local = array_sum($kills_local);
    $total_visitante = array_sum($kills_visitante);

    if ($total_local > 4 || $total_visitante > 4) {
        die("Un participante no puede superar las 4 kills.");
    }

} else {

    // En playoffs, el marcador se calcula sumando los resultados de los sets.
    $total_local = 0;
    $total_visitante = 0;

}

/*
 * Comprobar duplicados dentro de la misma fase
 *
 * En Liga: misma temporada, fase, jornada y enfrentamiento.
 * En eliminatorias: misma temporada, fase y enfrentamiento.
 */

if ($fase === "L") {
    $sql = "SELECT id
            FROM members_partidos
            WHERE competicion_temporada_id = ?
            AND fase = ?
            AND jornada = ?
            AND (
                (local_id = ? AND visitante_id = ?)
                OR
                (local_id = ? AND visitante_id = ?)
            )";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isiiiii",
        $competicion_temporada,
        $fase,
        $jornada,
        $local,
        $visitante,
        $visitante,
        $local
    );
} else {
    $sql = "SELECT id
            FROM members_partidos
            WHERE competicion_temporada_id = ?
            AND fase = ?
            AND jornada IS NULL
            AND (
                (local_id = ? AND visitante_id = ?)
                OR
                (local_id = ? AND visitante_id = ?)
            )";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isiiii",
        $competicion_temporada,
        $fase,
        $local,
        $visitante,
        $visitante,
        $local
    );
}

$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    die("Este enfrentamiento ya está registrado en esta fase.");
}

/*
 * Transacción
 */

$conn->begin_transaction();

try {

    /*
     * Insertar partido.
     * Liga guarda su jornada; semifinales y final guardan NULL.
     */

    if ($fase === "L") {
        $sql = "INSERT INTO members_partidos
                (
                    competicion_temporada_id,
                    fase,
                    jornada,
                    local_id,
                    visitante_id,
                    kills_local,
                    kills_visitante
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isiiiii",
            $competicion_temporada,
            $fase,
            $jornada,
            $local,
            $visitante,
            $total_local,
            $total_visitante
        );
    } else {
        $sql = "INSERT INTO members_partidos
                (
                    competicion_temporada_id,
                    fase,
                    jornada,
                    local_id,
                    visitante_id,
                    kills_local,
                    kills_visitante
                )
                VALUES (?, ?, NULL, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isiiii",
            $competicion_temporada,
            $fase,
            $local,
            $visitante,
            $total_local,
            $total_visitante
        );
    }

    $stmt->execute();

    $partido_id = $conn->insert_id;

    if ($fase === "L") {
        /*
         * Inserción para Liga (comportamiento original)
         */
        $sql = "INSERT INTO members_participantes (partido_id, miembro_id, pokemon_id) VALUES (?, ?, ?)";
        $stmtParticipante = $conn->prepare($sql);

        foreach ($local_pokemon as $pokemon_id) {
            $stmtParticipante->bind_param("iii", $partido_id, $local, $pokemon_id);
            $stmtParticipante->execute();
        }

        foreach ($visitante_pokemon as $pokemon_id) {
            $stmtParticipante->bind_param("iii", $partido_id, $visitante, $pokemon_id);
            $stmtParticipante->execute();
        }

        $sql = "INSERT INTO members_kills (partido_id, miembro_id, pokemon_id, kills) VALUES (?, ?, ?, ?)";
        $stmtKills = $conn->prepare($sql);

        foreach ($local_pokemon as $i => $pokemon_id) {
            $kills = (int)$kills_local[$i];
            $stmtKills->bind_param("iiii", $partido_id, $local, $pokemon_id, $kills);
            $stmtKills->execute();
        }

        foreach ($visitante_pokemon as $i => $pokemon_id) {
            $kills = (int)$kills_visitante[$i];
            $stmtKills->bind_param("iiii", $partido_id, $visitante, $pokemon_id, $kills);
            $stmtKills->execute();
        }

    } else {
        /*
         * Inserción para Playoffs (SF y F con sets)
         */
        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 0;
        $totalKillsLocal = 0;
        $totalKillsVisitante = 0;

        $stmtSet = $conn->prepare(
            "INSERT INTO members_sets (partido_id, numero_set, kills_local, kills_visitante) VALUES (?, ?, ?, ?)"
        );

        $stmtParticipante = $conn->prepare(
            "INSERT INTO members_participantes (partido_id, set_id, miembro_id, pokemon_id) VALUES (?, ?, ?, ?)"
        );

        $stmtKills = $conn->prepare(
            "INSERT INTO members_kills (partido_id, set_id, miembro_id, pokemon_id, kills) VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($sets as $numeroSet => $datosSet) {

    if ($setsGanadosLocal === 2 || $setsGanadosVisitante === 2) {
        break;
    }

    if (
        empty($datosSet["pokemon_local"]) &&
        empty($datosSet["pokemon_visitante"])
    ) {
        continue;
    }

            $pLocalSet = $datosSet['pokemon_local'] ?? [];
            $pVisSet = $datosSet['pokemon_visitante'] ?? [];
            $kLocalSetArr = array_map('intval', $datosSet['kills_local'] ?? []);
            $kVisSetArr = array_map('intval', $datosSet['kills_visitante'] ?? []);

            $killsLocalSet = array_sum($kLocalSetArr);
            $killsVisitanteSet = array_sum($kVisSetArr);

            if ($killsLocalSet === $killsVisitanteSet) {
                throw new RuntimeException('Un set de playoffs no puede terminar empatado.');
            }

            $stmtSet->bind_param("iiii", $partido_id, $numeroSet, $killsLocalSet, $killsVisitanteSet);
            $stmtSet->execute();
            $setId = $conn->insert_id;

            $totalKillsLocal += $killsLocalSet;
            $totalKillsVisitante += $killsVisitanteSet;

            if ($killsLocalSet > $killsVisitanteSet) {
                $setsGanadosLocal++;
            } else {
                $setsGanadosVisitante++;
            }

            foreach ($pLocalSet as $i => $pokemon_id) {
                $pokemon_id = (int)$pokemon_id;
                $kills = $kLocalSetArr[$i] ?? 0;

                $stmtParticipante->bind_param("iiii", $partido_id, $setId, $local, $pokemon_id);
                $stmtParticipante->execute();

                $stmtKills->bind_param("iiiii", $partido_id, $setId, $local, $pokemon_id, $kills);
                $stmtKills->execute();
            }

            foreach ($pVisSet as $i => $pokemon_id) {
                $pokemon_id = (int)$pokemon_id;
                $kills = $kVisSetArr[$i] ?? 0;

                $stmtParticipante->bind_param("iiii", $partido_id, $setId, $visitante, $pokemon_id);
                $stmtParticipante->execute();

                $stmtKills->bind_param("iiiii", $partido_id, $setId, $visitante, $pokemon_id, $kills);
                $stmtKills->execute();
            }
        }

        if ($setsGanadosLocal < 2 && $setsGanadosVisitante < 2) {
            throw new RuntimeException('El enfrentamiento debe terminar con dos sets ganados.');
        }

        $stmtTotales = $conn->prepare(
            "UPDATE members_partidos SET kills_local = ?, kills_visitante = ? WHERE id = ?"
        );
        $stmtTotales->bind_param("iii", $totalKillsLocal, $totalKillsVisitante, $partido_id);
        $stmtTotales->execute();
    }

    /*
     * Actualizar fecha de la competición
     */

    $sql = "UPDATE competiciones_temporadas
            SET fecha_actualizacion = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $competicion_temporada);
    $stmt->execute();

    /*
     * Confirmar
     */

    $conn->commit();

} catch (Throwable $e) {

    $conn->rollback();

    error_log("Error al guardar combate Members League: " . $e->getMessage());

    die(
        "Error al guardar el combate: " .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8")
    );
}

/*
 * Volver al formulario
 */

header("Location: registrar_members.php");
exit;
