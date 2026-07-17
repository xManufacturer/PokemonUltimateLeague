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
        WHERE competicion_temporada_id = ?";

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