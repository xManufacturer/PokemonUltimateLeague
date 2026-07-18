<?php
require_once 'config/conexion.php';
session_start();

$sql = "SELECT
            p.competicion_temporada_id,
            c.nombre AS competicion,
            t.numero AS temporada
        FROM partidos p
        JOIN competiciones_temporadas ct
            ON p.competicion_temporada_id = ct.id
        JOIN competiciones c
            ON ct.competicion_id = c.id
        JOIN temporadas t
            ON ct.temporada_id = t.id
        ORDER BY p.id DESC
        LIMIT 1";

$resultado = $conn->query($sql);

$ultimaCompeticion = $resultado->fetch_assoc();

$sql = "SELECT
            nombre,
            ruta
        FROM competiciones
        WHERE activa = 1
        ORDER BY id";

$stmt = $conn->prepare($sql);
$stmt->execute();
$resultado = $stmt->get_result();

$sql = "SELECT
            p.id,
            pl.nombre AS local,
            pl.imagen AS imagen_local,
            pv.nombre AS visitante,
            pv.imagen AS imagen_visitante
        FROM partidos p
        JOIN participantes l
            ON p.local_id = l.id
        JOIN pokemon pl
            ON l.pokemon_id = pl.id
        JOIN participantes v
            ON p.visitante_id = v.id
        JOIN pokemon pv
            ON v.pokemon_id = pv.id
        WHERE p.competicion_temporada_id = ?
        ORDER BY p.id DESC
        LIMIT 3";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ultimaCompeticion["competicion_temporada_id"]);
$stmt->execute();

$ultimosPartidos = $stmt->get_result();

$partidosInicio = [];

while($partido = $ultimosPartidos->fetch_assoc()){

    $sql = "SELECT vida_local, vida_visitante
            FROM sets
            WHERE partido_id = ?";

    $stmtSets = $conn->prepare($sql);
    $stmtSets->bind_param("i",$partido["id"]);
    $stmtSets->execute();

    $sets = $stmtSets->get_result();

    $ganadosLocal = 0;
$ganadosVisitante = 0;

$marcadores = [];

while($set = $sets->fetch_assoc()){

    $marcadores[] = [
        "local" => $set["vida_local"],
        "visitante" => $set["vida_visitante"]
    ];

    if($set["vida_local"] > $set["vida_visitante"]){
        $ganadosLocal++;
    }elseif($set["vida_visitante"] > $set["vida_local"]){
        $ganadosVisitante++;
    }

}

if (count($marcadores) == 1) {

    $partido["marcador_local"] = $marcadores[0]["local"];
    $partido["marcador_visitante"] = $marcadores[0]["visitante"];

} else {

    $partido["marcador_local"] = $ganadosLocal;
    $partido["marcador_visitante"] = $ganadosVisitante;

}

    $partidosInicio[] = $partido;
}

$sql = "SELECT
            fecha_actualizacion
        FROM competiciones_temporadas
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ultimaCompeticion["competicion_temporada_id"]);
$stmt->execute();

$fila = $stmt->get_result()->fetch_assoc();

$fechaActualizacion = "";

if (!empty($fila["fecha_actualizacion"])) {
    $fecha = new DateTime($fila["fecha_actualizacion"], new DateTimeZone("UTC"));
$fecha->setTimezone(new DateTimeZone("Europe/Madrid"));

$fechaActualizacion = $fecha->format("d/m/Y");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pokémon Ultimate League</title>
    <link rel="icon" href="img/icono.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-TZL2J8ZT');</script>
<!-- End Google Tag Manager -->
</head>

<body>
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TZL2J8ZT"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <header class="cabecera-principal">
        <div class="contenedor-iconos-sociales">
            <a class="enlace-icono" href="https://discord.gg/5CzWTMf8aq" target="_blank">
                <img src="img/discord.png" alt="Discord">
            </a>
            <a class="enlace-icono" href="https://www.youtube.com/@PokemonUltimateLeague" target="_blank">
                <img src="img/youtube.png" alt="YouTube">
            </a>
        </div>
        
        <h1>Pokémon Ultimate League<span class="badge-beta">BETA</span></h1>
    </header>

    <div class="admin-icon">
    <?php if (isset($_SESSION["admin"])) { ?>
        <a href="admin/index.php">
            <img src="img/admin.png" alt="Administración">
        </a>
    <?php } else { ?>
        <a href="admin/login.php">
            <img src="img/admin.png" alt="Administración">
        </a>
    <?php } ?>
    </div>


    <h2>Últimos combates</h2>

<div class="jornada inicio-resultados">

    <h3>
        <?php echo $ultimaCompeticion["competicion"]; ?>
        -
        Temporada <?php echo $ultimaCompeticion["temporada"]; ?>
    </h3>

<?php foreach($partidosInicio as $partido){ ?>

    <div class="partido">

        <div class="equipo local">
            <img src="img/pokemon/<?php echo $partido["imagen_local"]; ?>">
            <span><?php echo $partido["local"]; ?></span>
        </div>

        <div class="resultado">
            <div class="resultado-sets">
                <?php echo $partido["marcador_local"]; ?>
                -
                <?php echo $partido["marcador_visitante"]; ?>
            </div>
        </div>

        <div class="equipo visitante">
            <span><?php echo $partido["visitante"]; ?></span>
            <img src="img/pokemon/<?php echo $partido["imagen_visitante"]; ?>">
        </div>

    </div>

<?php } ?>

    <div style="margin-top:20px;">
        <a class="btn-registrar"
           href="temporada.php?id=<?php echo $ultimaCompeticion["competicion_temporada_id"]; ?>">
            Ver competición
        </a>
    </div>

</div>
    <div class="tarjetas">
        <?php while ($fila = $resultado->fetch_assoc()) { ?>
            <a href="competiciones/<?php echo $fila['ruta']; ?>">
                <?php echo $fila['nombre']; ?>
            </a>
        <?php } ?>
    </div>

    <div class="futuras-adiciones">
        <h3>Web en fase BETA. Aspectos que faltan y futuras adiciones:</h3>
        <ul>
            <li>Adaptar la web a dispositivos móviles</li>
            <li>Mejora general de diseño de la web.</li>
            <li>Segunda División de cada región.</li>
            <li>Fichas individuales de cada Pokémon.</li>
            <li>Historial de enfrentamientos entre dos Pokémon.</li>
            <li>Clasificación histórica de las competiciones.</li>
            <li>Palmarés, historial y más estadísticas de las competiciones.</li>
        </ul>
    </div>

    <footer class="pie-pagina">
        <p><strong>Última actualización de resultados: </strong><?php echo $fechaActualizacion; ?></p>
        <p>Creado por <a href="https://discord.com/users/380751682356641793" target="_blank">Manufacturer</a></p>
    </footer>
</body>
</html>