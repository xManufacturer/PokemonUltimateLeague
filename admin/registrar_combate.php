<?php
require_once '../config/conexion.php';
require_once 'proteger.php';

$sql = "SELECT 
            id,
            nombre
        FROM competiciones
        WHERE activa = 1
        ORDER BY id";

$stmt = $conn->prepare($sql);
$stmt->execute();
$competiciones = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registrar combate</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <h1>Registrar combate</h1>
    <a href="../index.php" class="btn-inicio">
        <img src="../img/inicio.png" alt="Inicio">
    </a>
    <a class="btn-registrar" href="index.php">Volver</a><br><br>

    <form action="guardar_combate.php" method="post">
        <label for="competicion"><strong>Competición</strong></label>
        <select name="competicion" id="competicion">
            <option value="">Selecciona competición</option>
            <?php
            while ($fila = $competiciones->fetch_assoc()) {
            ?>
                <option value="<?php echo $fila['id']; ?>">
                    <?php echo $fila['nombre']; ?>
                </option>
            <?php
            }
            ?>
        </select>
        <br><br>

        <label for="temporada"><strong>Temporada</strong></label>
        <select name="temporada" id="temporada">
            <option value="">...</option>
        </select>
        <br><br>

        <label for="fase"><strong>Fase</strong></label>
        <select name="fase" id="fase">
            <option value="">...</option>
        </select>
        <br><br>

        <label for="jornada"><strong>Jornada</strong></label>
        <select name="jornada" id="jornada">
            <option value="">...</option>
        </select>
        <br><br>

        <label for="local"><strong>Pokémon local</strong></label>
        <select name="local" id="local">
            <option value="">...</option>
        </select>
        <br><br>

        <label for="visitante"><strong>Pokémon visitante</strong></label>
        <select name="visitante" id="visitante">
            <option value="">...</option>
        </select>
        <br><br>

        <input
            type="hidden"
            name="competicion_temporada"
            id="competicion_temporada">

        <div id="contenedorSets"></div>
        <br>
        <button type="submit" class="btn-registrar">Registrar</button>
    </form>

    <script>

    const competicion = document.getElementById("competicion");
    const temporada = document.getElementById("temporada");
    const local = document.getElementById("local");
    const visitante = document.getElementById("visitante");
    const fase = document.getElementById("fase");
    const jornada = document.getElementById("jornada");
    const contenedorSets = document.getElementById("contenedorSets");
    const competicionTemporada = document.getElementById("competicion_temporada");

    let datosTemporadas = null;
    let datosTemporada = null;

    competicion.addEventListener("change", function() {
        const idCompeticion = this.value;
        fetch("obtener_temporadas.php?competicion=" + idCompeticion)
            .then(response => response.json())
            .then(datos => {
                datosTemporadas = datos;
                temporada.innerHTML =
                    '<option value="">...</option>';
                datos.forEach(function(fila) {
                    temporada.innerHTML +=
                        `<option value="${fila.id}">
                            Temporada ${fila.numero}
                        </option>`;
                });
            });
    });

    temporada.addEventListener("change", function () {
        const idCompeticionTemporada = this.value;
        competicionTemporada.value = idCompeticionTemporada;

        datosTemporada = datosTemporadas.find(
            t => t.id == this.value
        );

        fase.innerHTML =
            '<option value="">...</option>';
        jornada.innerHTML =
            '<option value="">...</option>';

        for (let i = 1; i <= datosTemporada.jornadas; i++) {
            jornada.innerHTML +=
                `<option value="${i}">
                    Jornada ${i}
                </option>`;
        }

        if (datosTemporada.tipo == "liga") {

    fase.innerHTML +=
        `<option value="L">Liga</option>`;

}

else if (datosTemporada.tipo == "legendary") {

    if (datosTemporada.jornadas == 1) {
        fase.innerHTML +=
            `<option value="F">Final</option>`;
    } else {
        fase.innerHTML +=
            `<option value="L">Liga</option>`;
    }
} else if (datosTemporada.tipo == "copa") {

    for (let i = 0; i < datosTemporada.grupos; i++) {
        const letra = String.fromCharCode(65 + i);
        fase.innerHTML +=
            `<option value="G${letra}">
                Grupo ${letra}
            </option>`;
    }
    if (datosTemporada.grupos >= 4) {
        fase.innerHTML +=
            `<option value="SF">Semifinal</option>`;
    }
    fase.innerHTML +=
        `<option value="F">Final</option>`;
} else if (datosTemporada.tipo == "mundial") {

    fase.innerHTML +=
        `<option value="F">Final</option>`;
}

        local.innerHTML =
            '<option value="">...</option>';

        visitante.innerHTML =
            '<option value="">...</option>';

        if (idCompeticionTemporada === "") {
            return;
        }

        cargarParticipantes();
    });

    fase.addEventListener("change", function () {
        cargarParticipantes();
        actualizarSets();
    });

    local.addEventListener("change", function () {
        actualizarSets();
    });

    visitante.addEventListener("change", function () {
        actualizarSets();
    });

    function cargarParticipantes() {

    if (
        competicionTemporada.value == "" ||
        fase.value == ""
    ) {
        return;
    }

    fetch(
        "obtener_participantes.php?competicion_temporada=" +
        competicionTemporada.value +
        "&fase=" +
        fase.value
    )
    .then(response => response.json())
    .then(datos => {

        local.innerHTML =
            '<option value="">...</option>';

        visitante.innerHTML =
            '<option value="">...</option>';

        datos.forEach(function(fila) {

            local.innerHTML +=
                `<option value="${fila.id}">
                    ${fila.nombre}
                </option>`;

            visitante.innerHTML +=
                `<option value="${fila.id}">
                    ${fila.nombre}
                </option>`;
        });

    });

}

    function actualizarSets() {

        if (fase.value == "" || local.value == "" || visitante.value == "") {
            contenedorSets.innerHTML = "";
            return;
        }

        let cantidad;

        if (fase.value == "F") {
            cantidad = datosTemporada.sets_final;
        } else {
            cantidad = datosTemporada.sets_fase;
        }

        const nombreLocal = 
            local.options[local.selectedIndex].text;

        const nombreVisitante = 
            visitante.options[visitante.selectedIndex].text;

        const imagenLocal =
            "../img/pokemon/" + nombreLocal.toLowerCase() + ".png";

        const imagenVisitante =
            "../img/pokemon/" + nombreVisitante.toLowerCase() + ".png";

        contenedorSets.innerHTML = "";

        for (let i = 1; i <= cantidad; i++) {
            let obligatorio = i < cantidad ? "required" : "";
            contenedorSets.innerHTML += `

                <div class="tarjeta-set">

                    <h3>SET ${i}</h3>

                    <div class="pokemon-set">
                        <img src="${imagenLocal}" alt="${nombreLocal}">
                        <span>${nombreLocal}</span>

                        <input
                            class="vida"
                            type="number"
                            name="vida_local[]"
                            min="0"
                            max="100"
                            ${obligatorio}>
                    </div>

                    <div class="pokemon-set">
                        <img src="${imagenVisitante}" alt="${nombreVisitante}">
                        <span>${nombreVisitante}</span>

                        <input
                            class="vida"
                            type="number"
                            name="vida_visitante[]"
                            min="0"
                            max="100"
                            ${obligatorio}>
                    </div>
                </div>
            `;
        }
    }
    </script>

</body>
</html>