<?php
function obtenerClasificacion($conn, $competicion_temporada) {
    $sql = "SELECT
                pa.id,
                p.nombre,
                p.imagen,
                pe.competicion AS plaza_especial
            FROM participantes pa
            JOIN pokemon p ON pa.pokemon_id = p.id
            LEFT JOIN plazas_especiales pe ON pe.participante_id = pa.id 
                AND pe.competicion_temporada_id = pa.competicion_temporada_id
            WHERE pa.competicion_temporada_id = ?
            ORDER BY p.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $competicion_temporada);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $clasificacion = [];

    while ($fila = $resultado->fetch_assoc()) {
        $clasificacion[$fila["id"]] = [
            "id" => $fila["id"],
            "nombre" => $fila["nombre"],
            "imagen" => $fila["imagen"],
            "plaza_especial" => $fila["plaza_especial"],

            "com" => 0,
            "v" => 0,
            "e" => 0,
            "d" => 0,

            "ps_favor" => 0,
            "ps_contra" => 0,
            "diferencia" => 0,

            "puntos" => 0
        ];
    }

    $sql = "SELECT
        id,
        local_id,
        visitante_id,
        fase
    FROM partidos
    WHERE competicion_temporada_id = ?
    AND fase != 'RP'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $competicion_temporada);
    $stmt->execute();
    $partidos = $stmt->get_result();

    while ($partido = $partidos->fetch_assoc()) {
         $sql = "SELECT
                vida_local,
                vida_visitante
            FROM sets
            WHERE partido_id = ?
            ORDER BY numero_set";

        $stmtSets = $conn->prepare($sql);
        $stmtSets->bind_param("i", $partido["id"]);
        $stmtSets->execute();
        $sets = $stmtSets->get_result();

        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 0;

        $psLocal = 0;
        $psVisitante = 0;

        $totalSets = 0;

        while ($set = $sets->fetch_assoc()) {
    $totalSets++;

    $psLocal += $set["vida_local"];
    $psVisitante += $set["vida_visitante"];

    if ($partido["fase"] == "L") {

        // Liga: gana únicamente quien deja al rival a 0.
        if ($set["vida_local"] == 0 && $set["vida_visitante"] > 0) {
            $setsGanadosVisitante++;
        } elseif ($set["vida_visitante"] == 0 && $set["vida_local"] > 0) {
            $setsGanadosLocal++;
        }

    } else {

        // Champions, Copa, eliminatorias...
        if ($set["vida_local"] == 0 && $set["vida_visitante"] > 0) {
            $setsGanadosVisitante++;
        } elseif ($set["vida_visitante"] == 0 && $set["vida_local"] > 0) {
            $setsGanadosLocal++;
        } else {
            // Si nadie cae a 0 (o ambos caen), el set es empate.
            $setsGanadosLocal++;
            $setsGanadosVisitante++;
        }

    }
}

        // Participantes del partido
        $local = $partido["local_id"];
        $visitante = $partido["visitante_id"];

        // Un combate más para ambos
        $clasificacion[$local]["com"]++;
        $clasificacion[$visitante]["com"]++;

        // Puntos de vida
        $clasificacion[$local]["ps_favor"] += $psLocal;
        $clasificacion[$local]["ps_contra"] += $psVisitante;

        $clasificacion[$visitante]["ps_favor"] += $psVisitante;
        $clasificacion[$visitante]["ps_contra"] += $psLocal;

        // Diferencia
        $clasificacion[$local]["diferencia"] =
            $clasificacion[$local]["ps_favor"] -
            $clasificacion[$local]["ps_contra"];

        $clasificacion[$visitante]["diferencia"] =
            $clasificacion[$visitante]["ps_favor"] -
            $clasificacion[$visitante]["ps_contra"];

        // En ligas, un solo combate solo tiene vencedor si alguien llega a 0 PS
if ($partido["fase"] == "L" && $totalSets == 1) {

    if ($psLocal == 0 && $psVisitante == 0) {
        // Ambos KO → empate
        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 0;

    } elseif ($psLocal == 0) {
        // Solo cae el local
        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 1;

    } elseif ($psVisitante == 0) {
        // Solo cae el visitante
        $setsGanadosLocal = 1;
        $setsGanadosVisitante = 0;

    } else {
        // Nadie cae → empate
        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 0;
    }
}

        // Resultado del combate
        if ($setsGanadosLocal > $setsGanadosVisitante) {
            $clasificacion[$local]["v"]++;
            $clasificacion[$visitante]["d"]++;
            $clasificacion[$local]["puntos"] += 3;
        } elseif ($setsGanadosVisitante > $setsGanadosLocal) {
            $clasificacion[$visitante]["v"]++;
            $clasificacion[$local]["d"]++;
            $clasificacion[$visitante]["puntos"] += 3;
        } else {
            $clasificacion[$local]["e"]++;
            $clasificacion[$visitante]["e"]++;
            $clasificacion[$local]["puntos"]++;
            $clasificacion[$visitante]["puntos"]++;
        }
    }
    usort($clasificacion, function($a, $b) {

        // 1º Puntos
        if ($a["puntos"] != $b["puntos"]) {
            return $b["puntos"] <=> $a["puntos"];
        }

        // 2º Diferencia de PS
        if ($a["diferencia"] != $b["diferencia"]) {
            return $b["diferencia"] <=> $a["diferencia"];
        }

        // 3º PS+
        return $b["ps_favor"] <=> $a["ps_favor"];
    });

    return $clasificacion;
}

function obtenerClasificacionGrupo($conn, $competicion_temporada, $grupo) {
        $sql = "SELECT
                pa.id,
                pa.grupo,
                p.nombre,
                p.imagen,
                pe.competicion AS plaza_especial
            FROM participantes pa
            JOIN pokemon p ON pa.pokemon_id = p.id
            LEFT JOIN plazas_especiales pe ON pe.participante_id = pa.id 
                AND pe.competicion_temporada_id = pa.competicion_temporada_id
            WHERE pa.competicion_temporada_id = ? AND pa.grupo = ?
            ORDER BY p.nombre";

    $grupoBD = "G".$grupo;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $competicion_temporada, $grupoBD);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $clasificacion = [];

    while ($fila = $resultado->fetch_assoc()) {
        $clasificacion[$fila["id"]] = [
            "id" => $fila["id"],
            "grupo" => $fila["grupo"],
            "nombre" => $fila["nombre"],
            "imagen" => $fila["imagen"],
            "plaza_especial" => $fila["plaza_especial"],

            "com" => 0,
            "v" => 0,
            "e" => 0,
            "d" => 0,

            "ps_favor" => 0,
            "ps_contra" => 0,
            "diferencia" => 0,

            "puntos" => 0
        ];
    }

    $faseGrupo = "G" . $grupo;

    $sql = "SELECT
            id,
            local_id,
            visitante_id
        FROM partidos
        WHERE competicion_temporada_id = ?
        AND fase = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $competicion_temporada, $faseGrupo);
    $stmt->execute();
    $partidos = $stmt->get_result();

    while ($partido = $partidos->fetch_assoc()) {
         $sql = "SELECT
                vida_local,
                vida_visitante
            FROM sets
            WHERE partido_id = ?
            ORDER BY numero_set";

        $stmtSets = $conn->prepare($sql);
        $stmtSets->bind_param("i", $partido["id"]);
        $stmtSets->execute();
        $sets = $stmtSets->get_result();

        $setsGanadosLocal = 0;
        $setsGanadosVisitante = 0;

        $psLocal = 0;
        $psVisitante = 0;

        while ($set = $sets->fetch_assoc()) {

    $psLocal += $set["vida_local"];
    $psVisitante += $set["vida_visitante"];

    if ($set["vida_local"] == 0 && $set["vida_visitante"] > 0) {
        $setsGanadosVisitante++;
    } elseif ($set["vida_visitante"] == 0 && $set["vida_local"] > 0) {
        $setsGanadosLocal++;
    } else {
        // Empate de set
        $setsGanadosLocal++;
        $setsGanadosVisitante++;
    }
}

        // Participantes del partido
        $local = $partido["local_id"];
        $visitante = $partido["visitante_id"];

        // Un combate más para ambos
        $clasificacion[$local]["com"]++;
        $clasificacion[$visitante]["com"]++;

        // Puntos de vida
        $clasificacion[$local]["ps_favor"] += $psLocal;
        $clasificacion[$local]["ps_contra"] += $psVisitante;

        $clasificacion[$visitante]["ps_favor"] += $psVisitante;
        $clasificacion[$visitante]["ps_contra"] += $psLocal;

        // Diferencia
        $clasificacion[$local]["diferencia"] =
            $clasificacion[$local]["ps_favor"] -
            $clasificacion[$local]["ps_contra"];

        $clasificacion[$visitante]["diferencia"] =
            $clasificacion[$visitante]["ps_favor"] -
            $clasificacion[$visitante]["ps_contra"];

        // Resultado del combate
        if ($setsGanadosLocal > $setsGanadosVisitante) {
            $clasificacion[$local]["v"]++;
            $clasificacion[$visitante]["d"]++;
            $clasificacion[$local]["puntos"] += 3;
        } elseif ($setsGanadosVisitante > $setsGanadosLocal) {
            $clasificacion[$visitante]["v"]++;
            $clasificacion[$local]["d"]++;
            $clasificacion[$visitante]["puntos"] += 3;
        } else {
            $clasificacion[$local]["e"]++;
            $clasificacion[$visitante]["e"]++;
            $clasificacion[$local]["puntos"]++;
            $clasificacion[$visitante]["puntos"]++;
        }
    }
    usort($clasificacion, function($a, $b) {

        // 1º Puntos
        if ($a["puntos"] != $b["puntos"]) {
            return $b["puntos"] <=> $a["puntos"];
        }

        // 2º Diferencia de PS
        if ($a["diferencia"] != $b["diferencia"]) {
            return $b["diferencia"] <=> $a["diferencia"];
        }

        // 3º PS+
        return $b["ps_favor"] <=> $a["ps_favor"];
    });

    return $clasificacion;
}

function obtenerClasificacionHistorica($conn, $region) {

    $primera = [];
    $segunda = [];

    /*
     * ============================================================
     * PRIMERA DIVISIÓN
     * ============================================================
     */

    $sql = "SELECT
                pa.id,
                pa.pokemon_id,
                p.nombre,
                p.imagen,
                ct.id AS competicion_temporada_id
            FROM participantes pa
            JOIN pokemon p
                ON pa.pokemon_id = p.id
            JOIN competiciones_temporadas ct
                ON pa.competicion_temporada_id = ct.id
            JOIN competiciones c
                ON ct.competicion_id = c.id
            WHERE c.tipo = 'liga'
            AND c.region = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $region);
    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {

        $idParticipante = $fila["id"];

        $primera[$fila["pokemon_id"]] ??= [
            "pokemon_id" => $fila["pokemon_id"],
            "nombre" => $fila["nombre"],
            "imagen" => $fila["imagen"],
            "temporadas" => 0,
            "com" => 0,
            "v" => 0,
            "e" => 0,
            "d" => 0,
            "ps_favor" => 0,
            "ps_contra" => 0,
            "diferencia" => 0,
            "puntos" => 0
        ];

        $primera[$fila["pokemon_id"]]["temporadas"]++;

        /*
         * Partidos de esa temporada
         */

        $sqlPartidos = "SELECT
                            id,
                            local_id,
                            visitante_id,
                            fase
                        FROM partidos
                        WHERE competicion_temporada_id = ?";

        $stmtPartidos = $conn->prepare($sqlPartidos);
        $stmtPartidos->bind_param(
            "i",
            $fila["competicion_temporada_id"]
        );
        $stmtPartidos->execute();

        $partidos = $stmtPartidos->get_result();

        while ($partido = $partidos->fetch_assoc()) {

            if (
                $partido["local_id"] != $idParticipante &&
                $partido["visitante_id"] != $idParticipante
            ) {
                continue;
            }

            /*
             * Sets
             */

            $sqlSets = "SELECT
                            vida_local,
                            vida_visitante
                        FROM sets
                        WHERE partido_id = ?
                        ORDER BY numero_set";

            $stmtSets = $conn->prepare($sqlSets);
            $stmtSets->bind_param("i", $partido["id"]);
            $stmtSets->execute();

            $sets = $stmtSets->get_result();

            $setsGanadosLocal = 0;
            $setsGanadosVisitante = 0;

            $psLocal = 0;
            $psVisitante = 0;

            $totalSets = 0;

            while ($set = $sets->fetch_assoc()) {

                $totalSets++;

                $psLocal += $set["vida_local"];
                $psVisitante += $set["vida_visitante"];

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

                } else {

                    $setsGanadosLocal++;
                    $setsGanadosVisitante++;
                }
            }

            /*
             * Resultado de liga con un solo set
             */

            if ($partido["fase"] == "L" && $totalSets == 1) {

                if ($psLocal == 0 && $psVisitante == 0) {

                    $setsGanadosLocal = 0;
                    $setsGanadosVisitante = 0;

                } elseif ($psLocal == 0) {

                    $setsGanadosLocal = 0;
                    $setsGanadosVisitante = 1;

                } elseif ($psVisitante == 0) {

                    $setsGanadosLocal = 1;
                    $setsGanadosVisitante = 0;

                } else {

                    $setsGanadosLocal = 0;
                    $setsGanadosVisitante = 0;
                }
            }

            /*
             * Estadísticas
             */

            $primera[$fila["pokemon_id"]]["com"]++;

            if ($partido["local_id"] == $idParticipante) {

                $primera[$fila["pokemon_id"]]["ps_favor"] += $psLocal;
                $primera[$fila["pokemon_id"]]["ps_contra"] += $psVisitante;

                if ($setsGanadosLocal > $setsGanadosVisitante) {

                    $primera[$fila["pokemon_id"]]["v"]++;
                    $primera[$fila["pokemon_id"]]["puntos"] += 3;

                } elseif ($setsGanadosLocal < $setsGanadosVisitante) {

                    $primera[$fila["pokemon_id"]]["d"]++;

                } else {

                    $primera[$fila["pokemon_id"]]["e"]++;
                    $primera[$fila["pokemon_id"]]["puntos"]++;
                }

            } else {

                $primera[$fila["pokemon_id"]]["ps_favor"] += $psVisitante;
                $primera[$fila["pokemon_id"]]["ps_contra"] += $psLocal;

                if ($setsGanadosVisitante > $setsGanadosLocal) {

                    $primera[$fila["pokemon_id"]]["v"]++;
                    $primera[$fila["pokemon_id"]]["puntos"] += 3;

                } elseif ($setsGanadosVisitante < $setsGanadosLocal) {

                    $primera[$fila["pokemon_id"]]["d"]++;

                } else {

                    $primera[$fila["pokemon_id"]]["e"]++;
                    $primera[$fila["pokemon_id"]]["puntos"]++;
                }
            }

            $primera[$fila["pokemon_id"]]["diferencia"] =
                $primera[$fila["pokemon_id"]]["ps_favor"] -
                $primera[$fila["pokemon_id"]]["ps_contra"];
        }
    }


    /*
     * ============================================================
     * SEGUNDA DIVISIÓN
     * ============================================================
     *
     * Solo necesitamos Pokémon que NO hayan jugado Primera.
     *
     * RP = Ronda Previa → NO cuenta.
     */

    $sql = "SELECT
                pa.id,
                pa.pokemon_id,
                p.nombre,
                p.imagen,
                ct.id AS competicion_temporada_id
            FROM participantes pa
            JOIN pokemon p
                ON pa.pokemon_id = p.id
            JOIN competiciones_temporadas ct
                ON pa.competicion_temporada_id = ct.id
            JOIN competiciones c
                ON ct.competicion_id = c.id
            WHERE c.tipo = 'segunda'
            AND c.region = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $region);
    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {

        /*
         * Si ya jugó Primera, NO necesitamos sus datos de Segunda.
         */

        if (isset($primera[$fila["pokemon_id"]])) {
            continue;
        }

        $pokemonId = $fila["pokemon_id"];

        $segunda[$pokemonId] ??= [
            "pokemon_id" => $pokemonId,
            "nombre" => $fila["nombre"],
            "imagen" => $fila["imagen"],
            "temporadas" => 0,
            "com" => 0,
            "v" => 0,
            "e" => 0,
            "d" => 0,
            "ps_favor" => 0,
            "ps_contra" => 0,
            "diferencia" => 0,
            "puntos" => 0,
            "segunda" => true
        ];

        $segunda[$pokemonId]["temporadas"]++;

        /*
         * Partidos de Segunda.
         *
         * RP queda excluida.
         */

        $sqlPartidos = "SELECT
                            id,
                            local_id,
                            visitante_id,
                            fase
                        FROM partidos
                        WHERE competicion_temporada_id = ?
                        AND fase != 'RP'";

        $stmtPartidos = $conn->prepare($sqlPartidos);
        $stmtPartidos->bind_param(
            "i",
            $fila["competicion_temporada_id"]
        );
        $stmtPartidos->execute();

        $partidos = $stmtPartidos->get_result();

        while ($partido = $partidos->fetch_assoc()) {

            if (
                $partido["local_id"] != $fila["id"] &&
                $partido["visitante_id"] != $fila["id"]
            ) {
                continue;
            }

            $sqlSets = "SELECT
                            vida_local,
                            vida_visitante
                        FROM sets
                        WHERE partido_id = ?
                        ORDER BY numero_set";

            $stmtSets = $conn->prepare($sqlSets);
            $stmtSets->bind_param("i", $partido["id"]);
            $stmtSets->execute();

            $sets = $stmtSets->get_result();

            $setsGanadosLocal = 0;
            $setsGanadosVisitante = 0;

            $psLocal = 0;
            $psVisitante = 0;

            while ($set = $sets->fetch_assoc()) {

                $psLocal += $set["vida_local"];
                $psVisitante += $set["vida_visitante"];

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

                } else {

                    $setsGanadosLocal++;
                    $setsGanadosVisitante++;
                }
            }

            $segunda[$pokemonId]["com"]++;

            if ($partido["local_id"] == $fila["id"]) {

                $segunda[$pokemonId]["ps_favor"] += $psLocal;
                $segunda[$pokemonId]["ps_contra"] += $psVisitante;

                if ($setsGanadosLocal > $setsGanadosVisitante) {

                    $segunda[$pokemonId]["v"]++;
                    $segunda[$pokemonId]["puntos"] += 3;

                } elseif ($setsGanadosLocal < $setsGanadosVisitante) {

                    $segunda[$pokemonId]["d"]++;

                } else {

                    $segunda[$pokemonId]["e"]++;
                    $segunda[$pokemonId]["puntos"]++;
                }

            } else {

                $segunda[$pokemonId]["ps_favor"] += $psVisitante;
                $segunda[$pokemonId]["ps_contra"] += $psLocal;

                if ($setsGanadosVisitante > $setsGanadosLocal) {

                    $segunda[$pokemonId]["v"]++;
                    $segunda[$pokemonId]["puntos"] += 3;

                } elseif ($setsGanadosVisitante < $setsGanadosLocal) {

                    $segunda[$pokemonId]["d"]++;

                } else {

                    $segunda[$pokemonId]["e"]++;
                    $segunda[$pokemonId]["puntos"]++;
                }
            }

            $segunda[$pokemonId]["diferencia"] =
                $segunda[$pokemonId]["ps_favor"] -
                $segunda[$pokemonId]["ps_contra"];
        }
    }


    /*
     * ============================================================
     * % DE VICTORIAS
     * ============================================================
     */

    foreach ($primera as &$fila) {

        $fila["porcentaje_victorias"] =
            $fila["com"] > 0
                ? ($fila["v"] / $fila["com"]) * 100
                : 0;
    }

    foreach ($segunda as &$fila) {

        $fila["porcentaje_victorias"] =
            $fila["com"] > 0
                ? ($fila["v"] / $fila["com"]) * 100
                : 0;
    }

    unset($fila);


    /*
     * ============================================================
     * ORDENACIÓN
     * ============================================================
     */

    $ordenar = function($a, $b) {

    // Los Pokémon con 0 combates van siempre al final
    if ($a["com"] == 0 && $b["com"] > 0) {
        return 1;
    }

    if ($a["com"] > 0 && $b["com"] == 0) {
        return -1;
    }

    // Primero, puntos
    if ($a["puntos"] != $b["puntos"]) {
        return $b["puntos"] <=> $a["puntos"];
    }

    // Después, diferencia de PS
    if ($a["diferencia"] != $b["diferencia"]) {
        return $b["diferencia"] <=> $a["diferencia"];
    }

    // Finalmente, PS a favor
    return $b["ps_favor"] <=> $a["ps_favor"];
};

    usort($primera, $ordenar);
    usort($segunda, $ordenar);


    return [
        "primera" => $primera,
        "segunda" => $segunda
    ];
}

function obtenerClasificacionMembers($conn, $competicion_temporada) {

    $clasificacion = [];
    $killCount = [];

    /*
     * ============================================================
     * MIEMBROS
     * ============================================================
     */

    $sql = "
        SELECT
            id,
            nombre,
            imagen
        FROM members_miembros
        ORDER BY id
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($miembro = $resultado->fetch_assoc()) {

        $miembroId = (int)$miembro["id"];

        $clasificacion[$miembroId] = [
            "id" => $miembroId,
            "nombre" => $miembro["nombre"],
            "imagen" => $miembro["imagen"],

            "pj" => 0,
            "pg" => 0,
            "pe" => 0,
            "pp" => 0,

            "kaf" => 0,
            "kec" => 0,
            "dif" => 0,

            "pts" => 0
        ];
    }


    /*
     * ============================================================
     * PARTIDOS MEMBERS
     * ============================================================
     */

    $sql = "
        SELECT
            id,
            local_id,
            visitante_id,
            kills_local,
            kills_visitante
        FROM members_partidos
        WHERE competicion_temporada_id = ?
        ORDER BY jornada ASC, id ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $competicion_temporada);
    $stmt->execute();

    $partidos = $stmt->get_result();

    while ($partido = $partidos->fetch_assoc()) {

        $partidoId = (int)$partido["id"];

        $local = (int)$partido["local_id"];
        $visitante = (int)$partido["visitante_id"];

        $killsLocal = (int)$partido["kills_local"];
        $killsVisitante = (int)$partido["kills_visitante"];


        /*
         * Si alguno de los miembros ya no existe,
         * ignoramos el partido.
         */

        if (
            !isset($clasificacion[$local]) ||
            !isset($clasificacion[$visitante])
        ) {
            continue;
        }


        /*
         * PARTIDOS JUGADOS
         */

        $clasificacion[$local]["pj"]++;
        $clasificacion[$visitante]["pj"]++;


        /*
         * KILLS
         */

        $clasificacion[$local]["kaf"] += $killsLocal;
        $clasificacion[$local]["kec"] += $killsVisitante;

        $clasificacion[$visitante]["kaf"] += $killsVisitante;
        $clasificacion[$visitante]["kec"] += $killsLocal;


        /*
         * RESULTADO
         *
         * Si alguien llega a 4:
         *     gana.
         *
         * Si ninguno llega a 4:
         *     empate.
         */

        if ($killsLocal === 4 && $killsVisitante < 4) {

            $clasificacion[$local]["pg"]++;
            $clasificacion[$visitante]["pp"]++;

        } elseif ($killsVisitante === 4 && $killsLocal < 4) {

            $clasificacion[$visitante]["pg"]++;
            $clasificacion[$local]["pp"]++;

        } else {

            $clasificacion[$local]["pe"]++;
            $clasificacion[$visitante]["pe"]++;
        }
    }


    /*
     * ============================================================
     * DIFERENCIA Y PUNTOS
     * ============================================================
     */

    foreach ($clasificacion as &$fila) {

        $fila["dif"] =
            $fila["kaf"] -
            $fila["kec"];

        $fila["pts"] =
            ($fila["pg"] * 3) +
            $fila["pe"];
    }

    unset($fila);


    /*
     * ============================================================
     * ORDENACIÓN
     *
     * 1. Puntos
     * 2. Diferencia de kills
     * 3. Kills a favor
     * ============================================================
     */

    usort($clasificacion, function ($a, $b) {

        if ($a["pts"] != $b["pts"]) {
            return $b["pts"] <=> $a["pts"];
        }

        if ($a["dif"] != $b["dif"]) {
            return $b["dif"] <=> $a["dif"];
        }

        if ($a["kaf"] != $b["kaf"]) {
            return $b["kaf"] <=> $a["kaf"];
        }

        return strcmp(
            $a["nombre"],
            $b["nombre"]
        );
    });


    /*
 * ==========================================
 * KILL COUNT
 * ==========================================
 */

$killCount = [];

$sqlKillCount = "
    SELECT
        m.id AS miembro_id,
        m.nombre AS miembro,
        m.imagen AS imagen_miembro,

        p.id AS pokemon_id,
        p.nombre AS pokemon,
        p.imagen AS imagen_pokemon,

        COUNT(DISTINCT mp.id) AS combates,

        COALESCE(
            SUM(mk.kills),
            0
        ) AS kills

    FROM members_equipos e

    INNER JOIN members_miembros m
        ON m.id = e.miembro_id

    INNER JOIN members_pokemon mep
        ON mep.equipo_id = e.id

    INNER JOIN pokemon p
        ON p.id = mep.pokemon_id

    LEFT JOIN members_participantes mpte
        ON mpte.miembro_id = e.miembro_id
        AND mpte.pokemon_id = p.id

    LEFT JOIN members_partidos mp
        ON mp.id = mpte.partido_id
        AND mp.competicion_temporada_id = e.competicion_temporada_id

    LEFT JOIN members_kills mk
        ON mk.partido_id = mp.id
        AND mk.miembro_id = e.miembro_id
        AND mk.pokemon_id = p.id

    WHERE e.competicion_temporada_id = ?

    GROUP BY
        m.id,
        m.nombre,
        m.imagen,
        p.id,
        p.nombre,
        p.imagen

    HAVING COUNT(DISTINCT mp.id) > 0

    ORDER BY
        kills DESC,
        combates ASC,
        p.id ASC
";

$stmtKillCount = $conn->prepare($sqlKillCount);

$stmtKillCount->bind_param(
    "i",
    $competicion_temporada
);

$stmtKillCount->execute();

$resultadoKillCount = $stmtKillCount->get_result();

while ($fila = $resultadoKillCount->fetch_assoc()) {

    $fila["miembro_id"] = (int)$fila["miembro_id"];
    $fila["pokemon_id"] = (int)$fila["pokemon_id"];
    $fila["combates"] = (int)$fila["combates"];
    $fila["kills"] = (int)$fila["kills"];

    $killCount[] = $fila;
}
    /*
     * ============================================================
     * DEVOLVEMOS LAS DOS COSAS
     * ============================================================
     */

    return [
        "clasificacion" => $clasificacion,
        "killCount" => $killCount
    ];
}

function obtenerClasificacionHistoricaMembers($conn)
{
    $miembros = [];
    $killCount = [];

    /*
     * ============================================================
     * CLASIFICACIÓN HISTÓRICA DE MEMBERS
     * ============================================================
     *
     * Cada miembro se acumula entre todas las temporadas.
     *
     * Temp = temporadas disputadas
     * Com  = combates disputados
     * V    = victorias
     * E    = empates
     * D    = derrotas
     * Set+ = kills a favor
     * Set- = kills en contra
     * Dif  = kills a favor - kills en contra
     * Pts  = 3 victoria / 1 empate
     */

    $sql = "
        SELECT
            e.id AS equipo_id,
            e.miembro_id,
            e.competicion_temporada_id,
            m.nombre,
            m.imagen,
            t.numero AS temporada
        FROM members_equipos e

        INNER JOIN members_miembros m
            ON m.id = e.miembro_id

        INNER JOIN competiciones_temporadas ct
            ON ct.id = e.competicion_temporada_id

        INNER JOIN temporadas t
            ON t.id = ct.temporada_id

        INNER JOIN competiciones c
            ON c.id = ct.competicion_id

        WHERE c.tipo = 'members'

        ORDER BY
            t.numero ASC,
            e.miembro_id ASC
    ";

    $resultado = $conn->query($sql);

    while ($fila = $resultado->fetch_assoc()) {

        $miembroId = (int)$fila["miembro_id"];
        $ctId = (int)$fila["competicion_temporada_id"];

        if (!isset($miembros[$miembroId])) {

            $miembros[$miembroId] = [
                "miembro_id" => $miembroId,
                "nombre" => $fila["nombre"],
                "imagen" => $fila["imagen"],
                "temporadas" => 0,
                "com" => 0,
                "v" => 0,
                "e" => 0,
                "d" => 0,
                "kills_favor" => 0,
                "kills_contra" => 0,
                "diferencia" => 0,
                "puntos" => 0
            ];
        }

        /*
         * Una temporada solamente cuenta una vez.
         */

        $miembros[$miembroId]["temporadas"]++;

        /*
         * ========================================================
         * PARTIDOS DEL MIEMBRO EN ESA TEMPORADA
         * ========================================================
         */

        $sqlPartidos = "
            SELECT
                id,
                local_id,
                visitante_id,
                kills_local,
                kills_visitante
            FROM members_partidos
            WHERE competicion_temporada_id = ?
            AND (
                local_id = ?
                OR visitante_id = ?
            )
            ORDER BY jornada, id
        ";

        $stmtPartidos = $conn->prepare($sqlPartidos);

        $stmtPartidos->bind_param(
            "iii",
            $ctId,
            $miembroId,
            $miembroId
        );

        $stmtPartidos->execute();

        $partidos = $stmtPartidos->get_result();

        while ($partido = $partidos->fetch_assoc()) {

            $killsLocal = (int)$partido["kills_local"];
            $killsVisitante = (int)$partido["kills_visitante"];

            $miembros[$miembroId]["com"]++;

            /*
             * El miembro juega como LOCAL
             */

            if ((int)$partido["local_id"] === $miembroId) {

                $miembros[$miembroId]["kills_favor"] += $killsLocal;
                $miembros[$miembroId]["kills_contra"] += $killsVisitante;

                /*
                 * Si llega a 4 kills, gana.
                 */

                if ($killsLocal >= 4) {

                    $miembros[$miembroId]["v"]++;
                    $miembros[$miembroId]["puntos"] += 3;

                } elseif ($killsVisitante >= 4) {

                    $miembros[$miembroId]["d"]++;

                } else {

                    $miembros[$miembroId]["e"]++;
                    $miembros[$miembroId]["puntos"]++;
                }

            /*
             * El miembro juega como VISITANTE
             */

            } else {

                $miembros[$miembroId]["kills_favor"] += $killsVisitante;
                $miembros[$miembroId]["kills_contra"] += $killsLocal;

                /*
                 * Si llega a 4 kills, gana.
                 */

                if ($killsVisitante >= 4) {

                    $miembros[$miembroId]["v"]++;
                    $miembros[$miembroId]["puntos"] += 3;

                } elseif ($killsLocal >= 4) {

                    $miembros[$miembroId]["d"]++;

                } else {

                    $miembros[$miembroId]["e"]++;
                    $miembros[$miembroId]["puntos"]++;
                }
            }
        }

        /*
         * Diferencia
         */

        $miembros[$miembroId]["diferencia"] =
            $miembros[$miembroId]["kills_favor"] -
            $miembros[$miembroId]["kills_contra"];
    }


    /*
     * ============================================================
     * % DE VICTORIAS
     * ============================================================
     */

    foreach ($miembros as &$fila) {

        $fila["porcentaje_victorias"] =
            $fila["com"] > 0
                ? ($fila["v"] / $fila["com"]) * 100
                : 0;
    }

    unset($fila);


    /*
     * ============================================================
     * ORDENACIÓN
     * ============================================================
     *
     * 1. Puntos
     * 2. Diferencia de kills
     * 3. Kills a favor
     */

    uasort($miembros, function ($a, $b) {

        if ($a["puntos"] != $b["puntos"]) {
            return $b["puntos"] <=> $a["puntos"];
        }

        if ($a["diferencia"] != $b["diferencia"]) {
            return $b["diferencia"] <=> $a["diferencia"];
        }

        if ($a["kills_favor"] != $b["kills_favor"]) {
            return $b["kills_favor"] <=> $a["kills_favor"];
        }

        return strcasecmp($a["nombre"], $b["nombre"]);
    });


    /*
     * ============================================================
     * KILL COUNT HISTÓRICO
     * ============================================================
     *
     * Un mismo Pokémon puede pertenecer a miembros diferentes
     * en temporadas diferentes.
     *
     * Por eso agrupamos por pokemon_id y guardamos una lista
     * de todos los miembros que lo han utilizado.
     */

    $sqlKillCount = "
        SELECT
            p.id AS pokemon_id,
            p.nombre AS pokemon,
            p.imagen AS imagen_pokemon,

            COUNT(DISTINCT e.competicion_temporada_id) AS temporadas,

            COUNT(DISTINCT
                CASE
                    WHEN mpte.id IS NOT NULL
                    THEN CONCAT(e.competicion_temporada_id, '-', mpte.partido_id)
                END
            ) AS combates,

            COALESCE(
                SUM(
                    CASE
                        WHEN mk.id IS NOT NULL
                        THEN mk.kills
                        ELSE 0
                    END
                ),
                0
            ) AS kills

        FROM members_pokemon mep

        INNER JOIN members_equipos e
            ON e.id = mep.equipo_id

        INNER JOIN members_miembros m
            ON m.id = e.miembro_id

        INNER JOIN pokemon p
            ON p.id = mep.pokemon_id

        LEFT JOIN members_participantes mpte
            ON mpte.miembro_id = e.miembro_id
            AND mpte.pokemon_id = mep.pokemon_id

        LEFT JOIN members_partidos mp
            ON mp.id = mpte.partido_id
            AND mp.competicion_temporada_id = e.competicion_temporada_id

        LEFT JOIN members_kills mk
            ON mk.partido_id = mp.id
            AND mk.miembro_id = e.miembro_id
            AND mk.pokemon_id = mep.pokemon_id

        INNER JOIN competiciones_temporadas ct
            ON ct.id = e.competicion_temporada_id

        INNER JOIN competiciones c
            ON c.id = ct.competicion_id

        WHERE c.tipo = 'members'

        GROUP BY
            p.id,
            p.nombre,
            p.imagen

        ORDER BY
            kills DESC,
            p.id ASC
    ";

    $resultadoKillCount = $conn->query($sqlKillCount);

    while ($fila = $resultadoKillCount->fetch_assoc()) {

        $pokemonId = (int)$fila["pokemon_id"];

        $killCount[$pokemonId] = [
            "pokemon_id" => $pokemonId,
            "pokemon" => $fila["pokemon"],
            "imagen_pokemon" => $fila["imagen_pokemon"],
            "temporadas" => (int)$fila["temporadas"],
            "combates" => (int)$fila["combates"],
            "kills" => (int)$fila["kills"],
            "miembros" => []
        ];
    }


    /*
     * ============================================================
     * MIEMBROS QUE HAN TENIDO CADA POKÉMON
     * ============================================================
     */

    $sqlMiembrosPokemon = "
        SELECT DISTINCT
            mep.pokemon_id,
            m.id AS miembro_id,
            m.nombre AS miembro,
            m.imagen AS imagen_miembro
        FROM members_pokemon mep

        INNER JOIN members_equipos e
            ON e.id = mep.equipo_id

        INNER JOIN members_miembros m
            ON m.id = e.miembro_id

        INNER JOIN competiciones_temporadas ct
            ON ct.id = e.competicion_temporada_id

        INNER JOIN competiciones c
            ON c.id = ct.competicion_id

        WHERE c.tipo = 'members'

        ORDER BY
            mep.pokemon_id ASC,
            m.nombre ASC
    ";

    $resultadoMiembrosPokemon = $conn->query($sqlMiembrosPokemon);

    while ($fila = $resultadoMiembrosPokemon->fetch_assoc()) {

        $pokemonId = (int)$fila["pokemon_id"];

        if (!isset($killCount[$pokemonId])) {
            continue;
        }

        $killCount[$pokemonId]["miembros"][] = [
            "id" => (int)$fila["miembro_id"],
            "nombre" => $fila["miembro"],
            "imagen" => $fila["imagen_miembro"]
        ];
    }


    /*
     * ============================================================
     * KILLS POR COMBATE
     * ============================================================
     */

    foreach ($killCount as &$fila) {

        $fila["kills_por_combate"] =
            $fila["combates"] > 0
                ? $fila["kills"] / $fila["combates"]
                : 0;
    }

    unset($fila);


    return [
        "clasificacion" => array_values($miembros),
        "killCount" => array_values($killCount)
    ];
}

function obtenerClasificacionHistoricaLegendary($conn)
{
    $clasificacion = [];

    /*
     * ============================================================
     * CLASIFICACIÓN HISTÓRICA LEGENDARY LEAGUE
     * ============================================================
     */

    $sql = "SELECT
                pa.id,
                pa.pokemon_id,
                p.nombre,
                p.imagen,
                ct.id AS competicion_temporada_id
            FROM participantes pa

            JOIN pokemon p
                ON pa.pokemon_id = p.id

            JOIN competiciones_temporadas ct
                ON pa.competicion_temporada_id = ct.id

            JOIN competiciones c
                ON ct.competicion_id = c.id

            WHERE c.tipo = 'legendary'";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {

        $pokemonId = $fila["pokemon_id"];
        $idParticipante = $fila["id"];

        /*
         * Crear Pokémon si todavía no existe
         */

        if (!isset($clasificacion[$pokemonId])) {

            $clasificacion[$pokemonId] = [
                "pokemon_id" => $pokemonId,
                "nombre" => $fila["nombre"],
                "imagen" => $fila["imagen"],
                "temporadas" => 0,
                "com" => 0,
                "v" => 0,
                "e" => 0,
                "d" => 0,
                "ps_favor" => 0,
                "ps_contra" => 0,
                "diferencia" => 0,
                "puntos" => 0
            ];
        }

        /*
         * Una participación en una temporada
         */

        $clasificacion[$pokemonId]["temporadas"]++;


        /*
         * ========================================================
         * PARTIDOS DE ESA TEMPORADA
         * ========================================================
         */

        $sqlPartidos = "SELECT
                            id,
                            local_id,
                            visitante_id,
                            fase
                        FROM partidos
                        WHERE competicion_temporada_id = ?";

        $stmtPartidos = $conn->prepare($sqlPartidos);

        $stmtPartidos->bind_param(
            "i",
            $fila["competicion_temporada_id"]
        );

        $stmtPartidos->execute();

        $partidos = $stmtPartidos->get_result();


        while ($partido = $partidos->fetch_assoc()) {

            /*
             * Si este Pokémon no participa en el partido,
             * pasamos al siguiente.
             */

            if (
                $partido["local_id"] != $idParticipante &&
                $partido["visitante_id"] != $idParticipante
            ) {
                continue;
            }


            /*
             * ====================================================
             * SETS
             * ====================================================
             */

            $sqlSets = "SELECT
                            vida_local,
                            vida_visitante
                        FROM sets
                        WHERE partido_id = ?
                        ORDER BY numero_set";

            $stmtSets = $conn->prepare($sqlSets);

            $stmtSets->bind_param(
                "i",
                $partido["id"]
            );

            $stmtSets->execute();

            $sets = $stmtSets->get_result();


            $setsGanadosLocal = 0;
            $setsGanadosVisitante = 0;

            $psLocal = 0;
            $psVisitante = 0;

            $totalSets = 0;


            while ($set = $sets->fetch_assoc()) {

                $totalSets++;

                $psLocal += $set["vida_local"];
                $psVisitante += $set["vida_visitante"];


                /*
                 * Set ganado por local
                 */

                if (
                    $set["vida_local"] == 0 &&
                    $set["vida_visitante"] > 0
                ) {

                    $setsGanadosVisitante++;


                /*
                 * Set ganado por visitante
                 */

                } elseif (
                    $set["vida_visitante"] == 0 &&
                    $set["vida_local"] > 0
                ) {

                    $setsGanadosLocal++;


                /*
                 * Set con vidas restantes:
                 * ambos cuentan el set
                 */

                } else {

                    $setsGanadosLocal++;
                    $setsGanadosVisitante++;
                }
            }


            /*
             * ====================================================
             * RESULTADO DE LIGA CON UN SOLO SET
             * ====================================================
             *
             * Igual que la clasificación histórica de Kanto.
             */

            if (
                $partido["fase"] == "L" &&
                $totalSets == 1
            ) {

                if (
                    $psLocal == 0 &&
                    $psVisitante == 0
                ) {

                    $setsGanadosLocal = 0;
                    $setsGanadosVisitante = 0;

                } elseif ($psLocal == 0) {

                    $setsGanadosLocal = 0;
                    $setsGanadosVisitante = 1;

                } elseif ($psVisitante == 0) {

                    $setsGanadosLocal = 1;
                    $setsGanadosVisitante = 0;

                } else {

                    $setsGanadosLocal = 0;
                    $setsGanadosVisitante = 0;
                }
            }


            /*
             * ====================================================
             * ESTADÍSTICAS
             * ====================================================
             */

            $clasificacion[$pokemonId]["com"]++;


            /*
             * Pokémon LOCAL
             */

            if ($partido["local_id"] == $idParticipante) {

                $clasificacion[$pokemonId]["ps_favor"] += $psLocal;

                $clasificacion[$pokemonId]["ps_contra"] += $psVisitante;


                if ($setsGanadosLocal > $setsGanadosVisitante) {

                    $clasificacion[$pokemonId]["v"]++;

                    $clasificacion[$pokemonId]["puntos"] += 3;

                } elseif (
                    $setsGanadosLocal < $setsGanadosVisitante
                ) {

                    $clasificacion[$pokemonId]["d"]++;

                } else {

                    $clasificacion[$pokemonId]["e"]++;

                    $clasificacion[$pokemonId]["puntos"]++;
                }


            /*
             * Pokémon VISITANTE
             */

            } else {

                $clasificacion[$pokemonId]["ps_favor"] += $psVisitante;

                $clasificacion[$pokemonId]["ps_contra"] += $psLocal;


                if (
                    $setsGanadosVisitante > $setsGanadosLocal
                ) {

                    $clasificacion[$pokemonId]["v"]++;

                    $clasificacion[$pokemonId]["puntos"] += 3;

                } elseif (
                    $setsGanadosVisitante < $setsGanadosLocal
                ) {

                    $clasificacion[$pokemonId]["d"]++;

                } else {

                    $clasificacion[$pokemonId]["e"]++;

                    $clasificacion[$pokemonId]["puntos"]++;
                }
            }


            /*
             * Diferencia de PS
             */

            $clasificacion[$pokemonId]["diferencia"] =
                $clasificacion[$pokemonId]["ps_favor"] -
                $clasificacion[$pokemonId]["ps_contra"];
        }
    }


    /*
     * ============================================================
     * % DE VICTORIAS
     * ============================================================
     */

    foreach ($clasificacion as &$fila) {

        $fila["porcentaje_victorias"] =
            $fila["com"] > 0
                ? ($fila["v"] / $fila["com"]) * 100
                : 0;
    }

    unset($fila);


    /*
     * ============================================================
     * ORDENACIÓN
     * ============================================================
     *
     * 1. Puntos
     * 2. Diferencia de PS
     * 3. PS a favor
     */

    uasort($clasificacion, function ($a, $b) {

        /*
         * Pokémon sin combates al final
         */

        if ($a["com"] == 0 && $b["com"] > 0) {
            return 1;
        }

        if ($a["com"] > 0 && $b["com"] == 0) {
            return -1;
        }


        /*
         * Puntos
         */

        if ($a["puntos"] != $b["puntos"]) {
            return $b["puntos"] <=> $a["puntos"];
        }


        /*
         * Diferencia
         */

        if ($a["diferencia"] != $b["diferencia"]) {
            return $b["diferencia"] <=> $a["diferencia"];
        }


        /*
         * PS a favor
         */

        if ($a["ps_favor"] != $b["ps_favor"]) {
            return $b["ps_favor"] <=> $a["ps_favor"];
        }


        /*
         * Último desempate: Pokédex
         */

        return $a["pokemon_id"] <=> $b["pokemon_id"];
    });


    return array_values($clasificacion);
}