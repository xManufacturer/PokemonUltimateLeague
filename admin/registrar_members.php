<?php

require_once '../config/conexion.php';
require_once 'proteger.php';


/*
 * Cargar temporadas de Members League
 *
 * competicion_id = 17
 */

$competicion = 17;

$sql = "SELECT
            ct.id,
            t.numero,
            ct.jornadas
        FROM competiciones_temporadas ct
        INNER JOIN temporadas t
            ON ct.temporada_id = t.id
        WHERE ct.competicion_id = ?
        ORDER BY t.numero";

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
                data-jornadas="<?php echo $fila['jornadas']; ?>">

                Temporada <?php echo $fila['numero']; ?>

            </option>

        <?php } ?>

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

<div class="members-contenedor">


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

            <span id="contadorLocal">
                0
            </span>

            / 4

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

            <span id="contadorVisitante">
                0
            </span>

            / 4

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


/*
 * Elementos
 */

const temporada =
    document.getElementById("temporada");

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


/*
 * Temporada
 *
 * Generar jornadas según la temporada
 */

temporada.addEventListener(
    "change",
    function () {

        jornada.innerHTML =
            '<option value="">...</option>';

        const opcion =
            temporada.options[
                temporada.selectedIndex
            ];

        if (!opcion.value) {

            limpiarEquipos();

            return;
        }
        competicionTemporada.value = temporada.value;

        cargarMiembrosTemporada();

        const numeroJornadas =
            parseInt(
                opcion.dataset.jornadas
            ) || 0;


        for (
            let i = 1;
            i <= numeroJornadas;
            i++
        ) {

            jornada.innerHTML +=
                `<option value="${i}">
                    Jornada ${i}
                </option>`;

        }


        limpiarEquipos();

    }
);


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


                inputKills.addEventListener(
                    "input",
                    function () {

                        if (this.value < 0) {
                            this.value = 0;
                        }

                        if (this.value > 4) {
                            this.value = 4;
                        }

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

                            if (
                                seleccionados >= 4
                            ) {

                                alert(
                                    "Solo puedes seleccionar 4 Pokémon."
                                );

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

document
    .getElementById("formMembers")
    .addEventListener(
        "submit",
        function (e) {


            if (!temporada.value) {

                e.preventDefault();

                alert(
                    "Debes seleccionar una temporada."
                );

                return;

            }


            if (!jornada.value) {

                e.preventDefault();

                alert(
                    "Debes seleccionar una jornada."
                );

                return;

            }


            if (
                local.value ===
                visitante.value
            ) {

                e.preventDefault();

                alert(
                    "El local y el visitante deben ser diferentes."
                );

                return;

            }


            const seleccionadosLocal =
                pokemonLocal.querySelectorAll(
                    ".pokemon-member.seleccionado"
                ).length;


            const seleccionadosVisitante =
                pokemonVisitante.querySelectorAll(
                    ".pokemon-member.seleccionado"
                ).length;


            if (seleccionadosLocal.length < 1 || seleccionadosLocal.length > 4) {
    alert("Debes seleccionar entre 1 y 4 Pokémon para el equipo local.");
    return;
}

if (seleccionadosVisitante.length < 1 || seleccionadosVisitante.length > 4) {
    alert("Debes seleccionar entre 1 y 4 Pokémon para el equipo visitante.");
    return;
}

        }
    );

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