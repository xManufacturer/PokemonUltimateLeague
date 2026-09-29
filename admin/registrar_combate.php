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
    <link rel="icon" href="../img/icono.png" type="image/png">
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

<input
    type="text"
    id="local_busqueda"
    list="lista_local"
    placeholder=""
    autocomplete="off">

<datalist id="lista_local"></datalist>

<input type="hidden" name="local" id="local">

<br><br>

        <label for="visitante"><strong>Pokémon visitante</strong></label>

<input
    type="text"
    id="visitante_busqueda"
    list="lista_visitante"
    placeholder=""
    autocomplete="off">

<datalist id="lista_visitante"></datalist>

<input type="hidden" name="visitante" id="visitante">

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
    const localBusqueda = document.getElementById("local_busqueda");
    const visitanteBusqueda = document.getElementById("visitante_busqueda");
    const listaLocal = document.getElementById("lista_local");
    const listaVisitante = document.getElementById("lista_visitante");

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

} else if (datosTemporada.tipo == "legendary") {

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

} else if (datosTemporada.tipo == "segunda") {

    fase.innerHTML +=
        `<option value="RP">Ronda Previa</option>`;

    fase.innerHTML +=
        `<option value="R1">Ronda 1</option>`;

    fase.innerHTML +=
        `<option value="R2">Ronda 2</option>`;

    fase.innerHTML +=
        `<option value="R3">Ronda 3</option>`;

    fase.innerHTML +=
        `<option value="OCT">Octavos</option>`;

    fase.innerHTML +=
        `<option value="QF">Cuartos</option>`;

    fase.innerHTML +=
        `<option value="SF">Semifinales</option>`;

    fase.innerHTML +=
        `<option value="F">Final</option>`;

} else if (datosTemporada.tipo == "promocion") {

    fase.innerHTML +=
        `<option value="F">Final</option>`;

} else if (datosTemporada.tipo == "mundial") {

    fase.innerHTML +=
        `<option value="F">Final</option>`;
}

        local.value = "";
visitante.value = "";

localBusqueda.value = "";
visitanteBusqueda.value = "";

listaLocal.innerHTML = "";
listaVisitante.innerHTML = "";

        if (idCompeticionTemporada === "") {
            return;
        }

        cargarParticipantes();
    });

    fase.addEventListener("change", function () {
        cargarParticipantes();
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

        listaLocal.innerHTML = "";
listaVisitante.innerHTML = "";

datos.forEach(function(fila) {

    listaLocal.innerHTML +=
        `<option value="${fila.nombre}" data-id="${fila.id}"></option>`;

    listaVisitante.innerHTML +=
        `<option value="${fila.nombre}" data-id="${fila.id}"></option>`;

});

    });

}

    function actualizarSets() {

        if (fase.value == "" || local.value == "" || visitante.value == "") {
            contenedorSets.innerHTML = "";
            return;
        }

        let cantidad;

if (datosTemporada.tipo == "segunda") {

    if (
        fase.value == "R2" ||
        fase.value == "R3" ||
        fase.value == "OCT" ||
        fase.value == "QF" ||
        fase.value == "SF" ||
        fase.value == "F"
    ) {
        cantidad = 3;
    } else {
        cantidad = 1;
    }

} else {

    if (fase.value == "F") {
        cantidad = datosTemporada.sets_final;
    } else {
        cantidad = datosTemporada.sets_fase;
    }

}

        const nombreLocal = localBusqueda.value;

        const nombreVisitante = visitanteBusqueda.value;

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

    localBusqueda.addEventListener("input", function () {

    const nombre = this.value.trim().toLowerCase();

    local.value = "";

    for (const opcion of listaLocal.options) {

        if (opcion.value.toLowerCase() === nombre) {

            local.value = opcion.dataset.id;
            break;

        }
    }

    actualizarSets();
});


visitanteBusqueda.addEventListener("input", function () {

    const nombre = this.value.trim().toLowerCase();

    visitante.value = "";

    for (const opcion of listaVisitante.options) {

        if (opcion.value.toLowerCase() === nombre) {

            visitante.value = opcion.dataset.id;
            break;

        }
    }

    actualizarSets();
});
    </script>

</body>
</html>