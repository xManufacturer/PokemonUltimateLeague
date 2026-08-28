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