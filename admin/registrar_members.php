<?php
require_once '../config/conexion.php';
require_once 'proteger.php';

// Cargar las competiciones disponibles.
$sqlCompeticiones = "
    SELECT id, nombre
    FROM competiciones
    WHERE LOWER(nombre) LIKE '%members%'
       OR LOWER(nombre) LIKE '%elite%'
       OR LOWER(nombre) LIKE '%caos%'
    ORDER BY FIELD(LOWER(nombre),
        'members league',
        'elite league',
        'caos league'
    ), nombre
";

$competiciones = $conn->query($sqlCompeticiones);

// Competición seleccionada. Members League (17) es la predeterminada.
$competicion = isset($_GET['competicion'])
    ? (int) $_GET['competicion']
    : 17;

// Cargar las temporadas de la competición elegida.
$sql = "
    SELECT
        ct.id,
        t.numero,
        ct.jornadas,
        ct.sets_final
    FROM competiciones_temporadas ct
    INNER JOIN temporadas t
        ON ct.temporada_id = t.id
    WHERE ct.competicion_id = ?
    ORDER BY t.numero
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion);
$stmt->execute();

$temporadas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Registrar combate - Members League</title>

    <link
        rel="icon"
        href="../img/icono.png"
        type="image/png">

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<h1>Registrar combate - Members League</h1>

<a href="../index.php" class="btn-inicio">
    <img
        src="../img/inicio.png"
        alt="Inicio">
</a>

<a
    class="btn-registrar"
    href="index.php">
    Volver
</a>

<br><br>


<form
    action="guardar_members.php"
    method="post"
    id="formMembers">
    <input
    type="hidden"
    name="competicion_temporada"
    id="competicion_temporada">

    <!-- COMPETICIÓN -->
<label for="competicion">
    <strong>Competición</strong>
</label>

<select
    name="competicion"
    id="competicion"
    required
    onchange="cambiarCompeticion(this.value)"
>
    <option value="">...</option>

    <?php while ($filaComp = $competiciones->fetch_assoc()) { ?>
        <option
            value="<?php echo (int) $filaComp['id']; ?>"
            <?php echo ((int) $filaComp['id'] === $competicion)
                ? 'selected'
                : ''; ?>
        >
            <?php echo htmlspecialchars($filaComp['nombre']); ?>
        </option>
    <?php } ?>
</select>

<br><br>

    <!-- =========================================
         TEMPORADA
         ========================================= -->

    <label for="temporada">

        <strong>Temporada</strong>

    </label>

    <select
        name="temporada"
        id="temporada"
        required>

        <option value="">
            ...
        </option>

        <?php while ($fila = $temporadas->fetch_assoc()) { ?>

            <option
    value="<?php echo $fila['id']; ?>"
    data-jornadas="<?php echo $fila['jornadas']; ?>"
    data-sets-final="<?php echo $fila['sets_final']; ?>">

    Temporada <?php echo $fila['numero']; ?>

</option>

        <?php } ?>

    </select>


    <br><br>


    <!-- =========================================
     FASE
     ========================================= -->
<label for="fase">
    <strong>Fase</strong>
</label>

<select name="fase" id="fase" required>
    <option value="">...</option>
    <option value="L">Liga</option>
    <option value="SF">Semifinales</option>
    <option value="F">Final</option>
</select>

<br><br>
    
    <!-- =========================================
         JORNADA
         ========================================= -->

    <label for="jornada">

        <strong>Jornada</strong>

    </label>

    <select
        name="jornada"
        id="jornada"
        required>

        <option value="">
            ...
        </option>

    </select>


    <br><br>


    <!-- =========================================
         MIEMBROS
         ========================================= -->

    <div style="text-align:center;">

        <label for="local">

            <strong>Local</strong>

        </label>

        <select
            name="local"
            id="local"
            required>

            <option value="">
                ...
            </option>

        </select>


        &nbsp;&nbsp;&nbsp;

        <br><br>
        <label for="visitante">

            <strong>Visitante</strong>

        </label>

        <select
            name="visitante"
            id="visitante"
            required>

            <option value="">
                ...
            </option>

        </select>

    </div>


    <br>


<!-- =========================================
     COMBATE
     ========================================= -->

<div class="members-contenedor" id="contenedorLigaMembers">


    <!-- =========================
         LOCAL
         ========================= -->

    <div class="members-jugador">

        <h2 id="tituloLocal">
            Local
        </h2>

        <div
            id="pokemonLocal"
            class="pokemon-members">
        </div>

        <div class="contador">
    <span id="contadorLocal">0</span>
    /
    <span id="maximoLocal">4</span>
</div>

    </div>


    <!-- =========================
         MARCADOR
         ========================= -->

    <div class="resultado">

    <span id="marcadorLocal">0</span>

    <span class="separador-marcador">-</span>

    <span id="marcadorVisitante">0</span>

</div>


    <!-- =========================
         VISITANTE
         ========================= -->

    <div class="members-jugador">

        <h2 id="tituloVisitante">
            Visitante
        </h2>

        <div
            id="pokemonVisitante"
            class="pokemon-members">
        </div>

        <div class="contador">
    <span id="contadorVisitante">0</span>
    /
    <span id="maximoVisitante">4</span>
</div>

    </div>

</div>

<!-- =========================================
     COMBATE DE PLAYOFFS: TRES SETS
     ========================================= -->

<div id="contenedorSetsMembers" style="display:none;">

    <div class="set-members" data-set="1">
        <h3>Set 1</h3>

        <div class="members-contenedor">
            <div class="members-jugador">
                <h4 id="tituloLocalSet1">Local</h4>
                <div id="equipoLocalSet1" class="pokemon-members"></div>
                <div>Marcador local: <span id="marcadorLocalSet1">0</span></div>
            </div>

            <div class="members-jugador">
                <h4 id="tituloVisitanteSet1">Visitante</h4>
                <div id="equipoVisitanteSet1" class="pokemon-members"></div>
                <div>Marcador visitante: <span id="marcadorVisitanteSet1">0</span></div>
            </div>
        </div>
    </div>

    <hr>

    <div class="set-members" data-set="2">
        <h3>Set 2</h3>

        <div class="members-contenedor">
            <div class="members-jugador">
                <h4 id="tituloLocalSet2">Local</h4>
                <div id="equipoLocalSet2" class="pokemon-members"></div>
                <div>Marcador local: <span id="marcadorLocalSet2">0</span></div>
            </div>

            <div class="members-jugador">
                <h4 id="tituloVisitanteSet2">Visitante</h4>
                <div id="equipoVisitanteSet2" class="pokemon-members"></div>
                <div>Marcador visitante: <span id="marcadorVisitanteSet2">0</span></div>
            </div>
        </div>
    </div>

    <hr>

    <div class="set-members" data-set="3">
        <h3>Set 3 (solo si hace falta)</h3>

        <div class="members-contenedor">
            <div class="members-jugador">
                <h4 id="tituloLocalSet3">Local</h4>
                <div id="equipoLocalSet3" class="pokemon-members"></div>
                <div>Marcador local: <span id="marcadorLocalSet3">0</span></div>
            </div>

            <div class="members-jugador">
                <h4 id="tituloVisitanteSet3">Visitante</h4>
                <div id="equipoVisitanteSet3" class="pokemon-members"></div>
                <div>Marcador visitante: <span id="marcadorVisitanteSet3">0</span></div>
            </div>
        </div>
    </div>

</div>

    <div style="text-align:center;">

        <button
            type="submit"
            class="btn-registrar">

            Registrar combate

        </button>

    </div>


</form>


<script>

function cambiarCompeticion(competicionId) {
    if (!competicionId) {
        return;
    }

    window.location.href =
        "registrar_members.php?competicion=" +
        encodeURIComponent(competicionId);
}

function esJornadaDobleCaos() {
    const selectCompeticion = document.getElementById('competicion');
    const selectFase = document.getElementById('fase');
    const selectJornada = document.getElementById('jornada');

    const nombreCompeticion =
        selectCompeticion?.options[selectCompeticion.selectedIndex]?.text || '';

    const jornada = parseInt(selectJornada?.value, 10);

    return (
        nombreCompeticion.toLowerCase().includes('caos') &&
        selectFase?.value === 'L' &&
        Number.isInteger(jornada) &&
        jornada % 2 === 0
    );
}

function maxPokemonLiga() {
    return esJornadaDobleCaos() ? 6 : 4;
}

function maxKillsEquipoLiga() {
    return esJornadaDobleCaos() ? 6 : 4;
}

function actualizarLimitesKillsLiga() {
    const maximo = maxKillsEquipoLiga();

    [pokemonLocal, pokemonVisitante].forEach(contenedor => {
        contenedor.querySelectorAll('input[name^="kills_"]').forEach(input => {
            input.max = String(maximo);
            const valor = parseInt(input.value, 10) || 0;
            if (valor > maximo) input.value = String(maximo);
        });
    });

    actualizarMarcador();
}

/*
 * Elementos
 */

const temporada =
    document.getElementById("temporada");

const fase = document.getElementById("fase");

const jornada =
    document.getElementById("jornada");

const competicionTemporada =
    document.getElementById("competicion_temporada");

const local =
    document.getElementById("local");

const visitante =
    document.getElementById("visitante");

const pokemonLocal =
    document.getElementById("pokemonLocal");

const pokemonVisitante =
    document.getElementById("pokemonVisitante");

const contadorLocal =
    document.getElementById("contadorLocal");

const contadorVisitante =
    document.getElementById("contadorVisitante");

const tituloLocal =
    document.getElementById("tituloLocal");

const tituloVisitante =
    document.getElementById("tituloVisitante");

const marcadorLocal =
    document.getElementById("marcadorLocal");

const marcadorVisitante =
    document.getElementById("marcadorVisitante");

const contenedorSetsMembers = document.getElementById('contenedorSetsMembers');
const contenedorLigaMembers = document.getElementById('contenedorLigaMembers');

function actualizarContadoresMaximos() {
    const maximo = maxPokemonLiga();

    document.getElementById("maximoLocal").textContent = maximo;
    document.getElementById("maximoVisitante").textContent = maximo;
}

jornada.addEventListener("change", actualizarContadoresMaximos);
fase.addEventListener("change", actualizarContadoresMaximos);

function actualizarCampoJornada() {
    const esFaseLiga = fase.value === "L";

    const opcionTemporada =
        temporada.options[temporada.selectedIndex];

    const setsFinal =
        parseInt(opcionTemporada?.dataset.setsFinal) || 0;

    // Los playoffs solo están disponibles si sets_final > 0.
    Array.from(fase.options).forEach(opcion => {
        if (opcion.value === "SF" || opcion.value === "F") {
            opcion.disabled = !temporada.value || setsFinal <= 0;
        }
    });

    if (fase.selectedOptions[0]?.disabled) {
        fase.value = "";
    }

    jornada.disabled = !esFaseLiga;
    jornada.required = esFaseLiga;

    jornada.innerHTML = '<option value="">...</option>';

    if (!esFaseLiga) {
        return;
    }

    const numeroJornadas =
        parseInt(opcionTemporada?.dataset.jornadas) || 0;

    for (let i = 1; i <= numeroJornadas; i++) {
        const opcion = document.createElement("option");
        opcion.value = i;
        opcion.textContent = "Jornada " + i;
        jornada.appendChild(opcion);
    }
}

function actualizarVistaSetsMembers() {
    const faseActual = fase.value;
    const esPlayoff = faseActual === "SF" || faseActual === "F";
    const esLiga = faseActual === "L";

    // !important para imponerse a las reglas CSS existentes.
    contenedorLigaMembers.style.setProperty(
        "display",
        esLiga ? "flex" : "none",
        "important"
    );

    contenedorSetsMembers.style.setProperty(
        "display",
        esPlayoff ? "block" : "none",
        "important"
    );

    if (esPlayoff) {
        cargarEquiposSets();
    }
}

fase.addEventListener("change", function () {
actualizarCampoJornada();
actualizarVistaSetsMembers();
});

// Aplicar la vista correcta al cargar el formulario.
actualizarCampoJornada();
actualizarVistaSetsMembers();
actualizarContadoresMaximos();

/*
 * Temporada
 *
 * Generar jornadas según la temporada
 */

temporada.addEventListener("change", function () {
    const opcion = temporada.options[temporada.selectedIndex];

    if (!opcion.value) {
        competicionTemporada.value = "";
        limpiarEquipos();
        actualizarCampoJornada();
        return;
    }

    competicionTemporada.value = temporada.value;

    // Generar las jornadas según la temporada y la fase seleccionadas
    actualizarCampoJornada();

    // Cargar los miembros de la temporada
    cargarMiembrosTemporada();

    // Limpiar los equipos del combate anterior
    limpiarEquipos();
});

function cargarEquiposSets() {
    if (!local.value || !visitante.value || !temporada.value) {
        return;
    }

    [1, 2, 3].forEach(numeroSet => {
        cargarEquipoSet(
            local.value,
            numeroSet,
            "local"
        );

        cargarEquipoSet(
            visitante.value,
            numeroSet,
            "visitante"
        );

        const tituloL = document.getElementById("tituloLocalSet" + numeroSet);
        const tituloV = document.getElementById("tituloVisitanteSet" + numeroSet);

        tituloL.textContent = local.options[local.selectedIndex].text;
        tituloV.textContent = visitante.options[visitante.selectedIndex].text;
    });
}

function cargarEquipoSet(miembroId, numeroSet, lado) {
    const contenedorId = lado === "local"
        ? "equipoLocalSet" + numeroSet
        : "equipoVisitanteSet" + numeroSet;

    const marcadorId = lado === "local"
        ? "marcadorLocalSet" + numeroSet
        : "marcadorVisitanteSet" + numeroSet;

    const contenedor = document.getElementById(contenedorId);

    contenedor.innerHTML = "<p>Cargando Pokémon...</p>";

    fetch(
        "obtener_members_equipo.php?miembro_id=" +
        encodeURIComponent(miembroId) +
        "&competicion_temporada=" +
        encodeURIComponent(temporada.value)
    )
    .then(respuesta => {
        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }
        return respuesta.json();
    })
    .then(pokemon => {
        contenedor.innerHTML = "";

        if (!Array.isArray(pokemon) || pokemon.length === 0) {
            contenedor.innerHTML = "<p>Este miembro no tiene Pokémon en esta temporada.</p>";
            return;
        }

        pokemon.forEach(p => {
            const tarjeta = document.createElement("div");
            tarjeta.className = "pokemon-member";

            const nombrePokemon = document.createElement("strong");
            nombrePokemon.textContent = p.nombre;

            const imagen = document.createElement("img");
            imagen.src = "../img/pokemon/" + p.nombre.toLowerCase() + ".png";
            imagen.alt = p.nombre;

            const campoKills = document.createElement("input");
            campoKills.type = "number";
            campoKills.name = `sets[${numeroSet}][kills_${lado}][]`;
            campoKills.className = "kills";
            campoKills.min = "0";
            campoKills.max = "4";
            campoKills.value = "0";
            campoKills.disabled = true;

            const campoPokemon = document.createElement("input");
            campoPokemon.type = "hidden";
            campoPokemon.name = `sets[${numeroSet}][pokemon_${lado}][]`;
            campoPokemon.value = p.id;
            campoPokemon.disabled = true;

            tarjeta.appendChild(imagen);
            tarjeta.appendChild(nombrePokemon);
            tarjeta.appendChild(campoKills);
            tarjeta.appendChild(campoPokemon);

            campoKills.addEventListener("input", () => {
                let valor = parseInt(campoKills.value, 10);

                if (Number.isNaN(valor) || valor < 0) valor = 0;
                if (valor > 4) valor = 4;

                campoKills.value = valor;
                actualizarMarcadorSet(numeroSet, lado);
            });

            tarjeta.addEventListener("click", evento => {
                if (evento.target.tagName === "INPUT") {
                    return;
                }

                if (tarjeta.classList.contains("seleccionado")) {
                    tarjeta.classList.remove("seleccionado");
                    campoKills.disabled = true;
                    campoPokemon.disabled = true;
                    campoKills.value = 0;
                } else {
                    const seleccionados = contenedor.querySelectorAll(
                        ".pokemon-member.seleccionado"
                    ).length;

                    if (seleccionados >= maxPokemonLiga()) {
                        alert("Solo puedes seleccionar 4 Pokémon por equipo y por set.");
                        return;
                    }

                    tarjeta.classList.add("seleccionado");
                    campoKills.disabled = false;
                    campoPokemon.disabled = false;
                }

                actualizarMarcadorSet(numeroSet, lado);
            });

            contenedor.appendChild(tarjeta);
        });
    })
    .catch(error => {
        console.error(error);
        contenedor.innerHTML = "<p>Error al cargar los Pokémon.</p>";
    });
}

function actualizarMarcadorSet(numeroSet, lado) {
    const contenedorId = lado === "local"
        ? "equipoLocalSet" + numeroSet
        : "equipoVisitanteSet" + numeroSet;

    const marcadorId = lado === "local"
        ? "marcadorLocalSet" + numeroSet
        : "marcadorVisitanteSet" + numeroSet;

    const contenedor = document.getElementById(contenedorId);
    let total = 0;

    contenedor.querySelectorAll('input[type="number"]:not(:disabled)').forEach(input => {
        total += parseInt(input.value, 10) || 0;
    });

    document.getElementById(marcadorId).textContent = total;
}


/*
 * Cargar equipo
 */

function cargarEquipo(
    miembroId,
    contenedor,
    contador
) {

    if (
        miembroId === "" ||
        temporada.value === ""
    ) {

        contenedor.innerHTML = "";

        contador.textContent = "0";

        return;

    }


    fetch(
        "obtener_members_equipo.php" +
        "?miembro_id=" +
        encodeURIComponent(miembroId) +
        "&competicion_temporada=" +
        encodeURIComponent(temporada.value)
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                "Error HTTP: " +
                response.status
            );

        }

        return response.json();

    })

    .then(datos => {

        contenedor.innerHTML = "";


        if (datos.length === 0) {

            contenedor.innerHTML =
                "<p>Este miembro no tiene equipo para esta temporada.</p>";

            return;

        }


        datos.forEach(
            function (pokemon) {

                const tarjeta =
                    document.createElement("div");

                tarjeta.className =
                    "pokemon-member";


                const nombreCampoKills =
                    contenedor === pokemonLocal
                        ? "kills_local[]"
                        : "kills_visitante[]";


                const nombreCampoPokemon =
                    contenedor === pokemonLocal
                        ? "pokemon_local[]"
                        : "pokemon_visitante[]";


                tarjeta.innerHTML = `

                    <img
                        src="../img/pokemon/${pokemon.nombre.toLowerCase()}.png"
                        alt="${pokemon.nombre}">

                    <div>
                        <strong>
                            ${pokemon.nombre}
                        </strong>
                    </div>

                    <input
                        type="number"
                        class="kills"
                        name="${nombreCampoKills}"
                        min="0"
                        max="4"
                        value="0"
                        disabled>

                    <input
                        type="hidden"
                        name="${nombreCampoPokemon}"
                        value="${pokemon.id}"
                        disabled>

                `;


                const inputKills =
                    tarjeta.querySelector(
                        'input[type="number"]'
                    );


                inputKills.max = String(maxKillsEquipoLiga());

                inputKills.addEventListener(
                    "input",
                    function () {
                        const maximo = maxKillsEquipoLiga();
                        let valor = parseInt(this.value, 10);

                        if (Number.isNaN(valor) || valor < 0) valor = 0;
                        if (valor > maximo) valor = maximo;

                        // El total de kills del equipo tampoco puede superar el límite.
                        const otrosCampos = Array.from(
                            contenedor.querySelectorAll('input[name^="kills_"]:not(:disabled)')
                        ).filter(input => input !== this);
                        const totalOtros = otrosCampos.reduce(
                            (suma, input) => suma + (parseInt(input.value, 10) || 0),
                            0
                        );

                        if (totalOtros + valor > maximo) {
                            valor = Math.max(0, maximo - totalOtros);
                        }

                        this.value = String(valor);
                        actualizarMarcador();
                    }
                );


                tarjeta.addEventListener(
                    "click",
                    function (e) {

                        if (
                            e.target.tagName ===
                            "INPUT"
                        ) {

                            return;

                        }


                        const seleccionados =
                            contenedor.querySelectorAll(
                                ".pokemon-member.seleccionado"
                            ).length;


                        /*
                         * DESELECCIONAR
                         */

                        if (
                            tarjeta.classList.contains(
                                "seleccionado"
                            )
                        ) {

                            tarjeta.classList.remove(
                                "seleccionado"
                            );


                            tarjeta
                                .querySelectorAll("input")
                                .forEach(
                                    function (input) {

                                        input.disabled =
                                            true;

                                    }
                                );


                            inputKills.value = 0;

                        }


                        /*
                         * SELECCIONAR
                         */

                        else {

                            const maximo = maxPokemonLiga();

if (seleccionados >= maximo) {
    alert("Solo puedes seleccionar " + maximo + " Pokémon en esta jornada.");
    return;
}

                            tarjeta.classList.add(
                                "seleccionado"
                            );


                            tarjeta
                                .querySelectorAll("input")
                                .forEach(
                                    function (input) {

                                        input.disabled =
                                            false;

                                    }
                                );

                        }


                        actualizarContador(
                            contenedor,
                            contador
                        );

                        actualizarMarcador();

                    }
                );


                contenedor.appendChild(
                    tarjeta
                );

            }
        );

    })

    .catch(error => {

        console.error(
            "Error cargando el equipo:",
            error
        );

        contenedor.innerHTML =
            "<p>Error al cargar los Pokémon.</p>";

    });

}


/*
 * Contadores
 */

function actualizarContador(
    contenedor,
    contador
) {

    contador.textContent =
        contenedor.querySelectorAll(
            ".pokemon-member.seleccionado"
        ).length;

}


function actualizarMarcador() {

    let totalLocal = 0;
    let totalVisitante = 0;


    pokemonLocal
        .querySelectorAll(
            'input[name="kills_local[]"]:not(:disabled)'
        )
        .forEach(
            function (input) {

                totalLocal +=
                    parseInt(input.value) || 0;

            }
        );


    pokemonVisitante
        .querySelectorAll(
            'input[name="kills_visitante[]"]:not(:disabled)'
        )
        .forEach(
            function (input) {

                totalVisitante +=
                    parseInt(input.value) || 0;

            }
        );


    marcadorLocal.textContent =
        totalLocal;

    marcadorVisitante.textContent =
        totalVisitante;

}


/*
 * Local
 */

local.addEventListener(
    "change",
    function () {

        const opcion =
            local.options[
                local.selectedIndex
            ];


        tituloLocal.textContent =
            opcion.value
                ? opcion.text
                : "Local";


        cargarEquipo(
            local.value,
            pokemonLocal,
            contadorLocal
        );

        if (fase.value === "SF" || fase.value === "F") {
    cargarEquiposSets();
}

    }
);


/*
 * Visitante
 */

visitante.addEventListener(
    "change",
    function () {

        const opcion =
            visitante.options[
                visitante.selectedIndex
            ];


        tituloVisitante.textContent =
            opcion.value
                ? opcion.text
                : "Visitante";


        cargarEquipo(
            visitante.value,
            pokemonVisitante,
            contadorVisitante
        );

        if (fase.value === "SF" || fase.value === "F") {
    cargarEquiposSets();
}

    }
);


/*
 * Limpiar equipos
 */

function limpiarEquipos() {

    pokemonLocal.innerHTML = "";
    pokemonVisitante.innerHTML = "";

    contadorLocal.textContent = "0";
    contadorVisitante.textContent = "0";

    marcadorLocal.textContent = "0";
    marcadorVisitante.textContent = "0";

    tituloLocal.textContent = "Local";
    tituloVisitante.textContent = "Visitante";

}


/*
 * Validación antes de guardar
 */

document.getElementById("formMembers").addEventListener("submit", function (e) {
    if (!temporada.value) {
        e.preventDefault();
        alert("Debes seleccionar una temporada.");
        return;
    }

    if (!fase.value) {
        e.preventDefault();
        alert("Debes seleccionar una fase.");
        return;
    }

    if (fase.value === "L" && !jornada.value) {
        e.preventDefault();
        alert("Debes seleccionar una jornada para la fase liga.");
        return;
    }

    if (!local.value || !visitante.value || local.value === visitante.value) {
        e.preventDefault();
        alert("Debes seleccionar dos miembros diferentes.");
        return;
    }

    // Validación para semifinales y final
    if (fase.value === "SF" || fase.value === "F") {
        const sets = [1, 2, 3];
        let setsGanadosLocal = 0;
        let setsGanadosVisitante = 0;

        for (const numeroSet of sets) {
            const equipoL = document.getElementById("equipoLocalSet" + numeroSet);
            const equipoV = document.getElementById("equipoVisitanteSet" + numeroSet);

            const seleccionadosL = equipoL.querySelectorAll(
                ".pokemon-member.seleccionado"
            ).length;

            const seleccionadosV = equipoV.querySelectorAll(
                ".pokemon-member.seleccionado"
            ).length;

            const marcadorL = parseInt(
                document.getElementById("marcadorLocalSet" + numeroSet).textContent,
                10
            ) || 0;

            const marcadorV = parseInt(
                document.getElementById("marcadorVisitanteSet" + numeroSet).textContent,
                10
            ) || 0;

            // Si el tercer set está completamente vacío, se permite omitirlo.
            if (numeroSet === 3 && seleccionadosL === 0 && seleccionadosV === 0) {
                continue;
            }

            if (
                seleccionadosL < 1 || seleccionadosL > 4 ||
                seleccionadosV < 1 || seleccionadosV > 4
            ) {
                e.preventDefault();
                alert("En el Set " + numeroSet +
                    " debes seleccionar entre 1 y 4 Pokémon por equipo.");
                return;
            }

            if (marcadorL === marcadorV) {
                e.preventDefault();
                alert("El Set " + numeroSet + " no puede terminar empatado.");
                return;
            }

            if (marcadorL > 4 || marcadorV > 4) {
                e.preventDefault();
                alert("En cada set, las kills de cada equipo no pueden superar 4.");
                return;
            }

            if (marcadorL > marcadorV) {
                setsGanadosLocal++;
            } else {
                setsGanadosVisitante++;
            }

            // No se puede jugar otro set cuando alguien ya ha ganado dos.
            if (setsGanadosLocal === 2 || setsGanadosVisitante === 2) {
                if (numeroSet < 3) {
                    const siguienteSet = numeroSet + 1;
                    const equipoLSiguiente = document.getElementById("equipoLocalSet" + siguienteSet);
                    const equipoVSiguiente = document.getElementById("equipoVisitanteSet" + siguienteSet);

                    if (
                        equipoLSiguiente.querySelectorAll(".pokemon-member.seleccionado").length > 0 ||
                        equipoVSiguiente.querySelectorAll(".pokemon-member.seleccionado").length > 0
                    ) {
                        e.preventDefault();
                        alert("No puedes registrar más sets después de que un miembro haya ganado dos.");
                        return;
                    }
                }
                break;
            }
        }

        if (setsGanadosLocal < 2 && setsGanadosVisitante < 2) {
            e.preventDefault();
            alert("El enfrentamiento debe terminar con dos sets ganados. Rellena el Set 3 si hace falta.");
            return;
        }

        return;
    }

    // Validación original para la fase de liga
    const seleccionadosLocal = pokemonLocal.querySelectorAll(
        ".pokemon-member.seleccionado"
    ).length;

    const seleccionadosVisitante = pokemonVisitante.querySelectorAll(
        ".pokemon-member.seleccionado"
    ).length;

    const maximo = maxPokemonLiga();

if (seleccionadosLocal < 1 || seleccionadosLocal > maximo) {
    e.preventDefault();
    alert(
        "Debes seleccionar entre 1 y " + maximo +
        " Pokémon para el equipo local."
    );
    return;
}

if (seleccionadosVisitante < 1 || seleccionadosVisitante > maximo) {
    e.preventDefault();
    alert(
        "Debes seleccionar entre 1 y " + maximo +
        " Pokémon para el equipo visitante."
    );
    return;
}

    const maxKills = maxKillsEquipoLiga();
    const totalKillsLocal = Array.from(
        pokemonLocal.querySelectorAll('input[name="kills_local[]"]:not(:disabled)')
    ).reduce((suma, input) => suma + (parseInt(input.value, 10) || 0), 0);
    const totalKillsVisitante = Array.from(
        pokemonVisitante.querySelectorAll('input[name="kills_visitante[]"]:not(:disabled)')
    ).reduce((suma, input) => suma + (parseInt(input.value, 10) || 0), 0);

    const killsIndividualesInvalidas = Array.from(
        document.querySelectorAll('#pokemonLocal input[name="kills_local[]"]:not(:disabled), #pokemonVisitante input[name="kills_visitante[]"]:not(:disabled)')
    ).some(input => {
        const valor = parseInt(input.value, 10) || 0;
        return valor < 0 || valor > maxKills;
    });

    if (killsIndividualesInvalidas ||
        totalKillsLocal > maxKills ||
        totalKillsVisitante > maxKills) {
        e.preventDefault();
        alert("Las kills de cada Pokémon y el total de cada equipo no pueden superar " + maxKills + " en esta jornada.");
        return;
    }
});

async function cargarMiembrosTemporada() {

    const temporadaId = temporada.value;

    local.innerHTML = '<option value="">...</option>';
    visitante.innerHTML = '<option value="">...</option>';

    limpiarEquipos();

    if (!temporadaId) {
        return;
    }

    try {

        const respuesta = await fetch(
            "obtener_members_temporada.php?competicion_temporada="
            + encodeURIComponent(temporadaId)
        );

        const miembros = await respuesta.json();

        miembros.forEach(miembro => {

            const opcionLocal = document.createElement("option");
            opcionLocal.value = miembro.id;
            opcionLocal.textContent = miembro.nombre;

            const opcionVisitante = document.createElement("option");
            opcionVisitante.value = miembro.id;
            opcionVisitante.textContent = miembro.nombre;

            local.appendChild(opcionLocal);
            visitante.appendChild(opcionVisitante);
        });

    } catch (error) {

        console.error(error);

        local.innerHTML =
            '<option value="">Error al cargar miembros</option>';

        visitante.innerHTML =
            '<option value="">Error al cargar miembros</option>';
    }
}

</script>

</body>
</html>