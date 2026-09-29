<?php
require_once '../config/conexion.php';
require_once '../includes/clasificacion.php';
$competicion = 17;

$sql = "SELECT
            ct.id,
            t.numero
        FROM competiciones_temporadas ct
        JOIN temporadas t ON ct.temporada_id = t.id
        WHERE ct.competicion_id = ?
        ORDER BY t.numero";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion);
$stmt->execute();
$resultado = $stmt->get_result();

/*
 * ============================================================
 * CLASIFICACIÓN HISTÓRICA
 * ============================================================
 */

$historicaMembers = obtenerClasificacionHistoricaMembers($conn);

$clasificacionHistoricaMembers =
    $historicaMembers["clasificacion"];

$killCountHistoricoMembers =
    $historicaMembers["killCount"];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Members League</title>
    <link rel="icon" href="../img/icono.png" type="image/png">
    <header>    
    <div>
            <img src="../img/logos/members_league.png" alt="Members League" class="logos">
        </div>
</header>
    <link rel="icon" href="../img/icono.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
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
        
    <a href="../index.php" class="btn-inicio">
        <img src="../img/inicio.png" alt="Inicio">
    </a>
    <div class="tarjetas">
        <?php while ($fila = $resultado->fetch_assoc()) { ?>
            <a href="../temporada.php?id=<?php echo $fila['id']; ?>">
                Temporada <?php echo $fila['numero']; ?>
            </a>
        <?php } ?>
    </div>
<br><br><br>
    <h1>Clasificación histórica</h1>

<div class="clasificacion-contenedor clasificacion-members historica-members">

    <div class="clasificacion-columna">

        <table>

            <tr>

                <th>Pos</th>
                <th>Entrenador</th>
                <th>Temp</th>
                <th>Com</th>
                <th>V</th>
                <th>E</th>
                <th>D</th>
                <th>% V</th>
                <th>Set+</th>
                <th>Set-</th>
                <th>Dif</th>
                <th>Pts</th>

            </tr>

            <?php

            $posicion = 1;

            foreach ($clasificacionHistoricaMembers as $fila) {

            ?>

            <tr>

                <td>
                    <strong>
                        <?php echo $posicion; ?>
                    </strong>
                </td>

                <td>

                    <div class="pokemon-clasificacion members-miembro-historico">

                        <?php if (!empty($fila["imagen"])) { ?>

                            <img
                                src="../img/members/<?php echo htmlspecialchars($fila["imagen"]); ?>"
                                alt="<?php echo htmlspecialchars($fila["nombre"]); ?>"
                            >

                        <?php } ?>

                        <span>
                            <?php echo htmlspecialchars($fila["nombre"]); ?>
                        </span>

                    </div>

                </td>

                <td>
                    <?php echo $fila["temporadas"]; ?>
                </td>

                <td>
                    <?php echo $fila["com"]; ?>
                </td>

                <td>
                    <?php echo $fila["v"]; ?>
                </td>

                <td>
                    <?php echo $fila["e"]; ?>
                </td>

                <td>
                    <?php echo $fila["d"]; ?>
                </td>

                <td>

                    <?php
                    echo number_format(
                        $fila["porcentaje_victorias"],
                        1,
                        ",",
                        "."
                    );
                    ?>%

                </td>

                <td>
                    <?php echo $fila["kills_favor"]; ?>
                </td>

                <td>
                    <?php echo $fila["kills_contra"]; ?>
                </td>

                <td>
                    <strong>
                        <?php echo $fila["diferencia"]; ?>
                    </strong>
                </td>

                <td>
                    <strong>
                        <?php echo $fila["puntos"]; ?>
                    </strong>
                </td>

            </tr>

            <?php

                $posicion++;

            }

            ?>

        </table>

    </div>

</div>

<br><br>

<h1>Kill Count histórico</h1>

<div class="clasificacion-contenedor">

    <div class="clasificacion-columna">

        <table>

            <tr>

                <th>Pos</th>
                <th>Pokémon</th>
                <th>Entrenadores</th>
                <th>Temp</th>
                <th>K/C</th>
                <th>Com</th>
                <th>Kills</th>

            </tr>

            <?php

            $posicionKill = 1;

            foreach ($killCountHistoricoMembers as $fila) {

            ?>

            <tr>

                <td>
                    <strong>
                        <?php echo $posicionKill; ?>
                    </strong>
                </td>

                <td>

                    <div class="pokemon-clasificacion">

                        <img
                            src="../img/pokemon/<?php echo htmlspecialchars($fila["imagen_pokemon"]); ?>"
                            alt="<?php echo htmlspecialchars($fila["pokemon"]); ?>"
                        >

                        <span>
                            <?php echo htmlspecialchars($fila["pokemon"]); ?>
                        </span>

                    </div>

                </td>

                <td>

                    <div class="members-historico-lista">

                        <?php foreach ($fila["miembros"] as $miembro) { ?>

                            <div class="members-historico-miembro">

                                <?php if (!empty($miembro["imagen"])) { ?>

                                    <img
                                        src="../img/members/<?php echo htmlspecialchars($miembro["imagen"]); ?>"
                                        alt="<?php echo htmlspecialchars($miembro["nombre"]); ?>"
                                    >

                                <?php } ?>

                                <span>
                                    <?php echo htmlspecialchars($miembro["nombre"]); ?>
                                </span>

                            </div>

                        <?php } ?>

                    </div>

                </td>

                <td>
                    <?php echo $fila["temporadas"]; ?>
                </td>

                <td>
                    <?php
                    echo number_format(
                        $fila["kills_por_combate"],
                        2,
                        ",",
                        "."
                    );
                    ?>
                </td>

                <td>
                    <?php echo $fila["combates"]; ?>
                </td>

                <td>
                    <strong>
                        <?php echo $fila["kills"]; ?>
                    </strong>
                </td>

            </tr>

            <?php

                $posicionKill++;

            }

            ?>

        </table>

    </div>

</div>

    <?php
$sql = "SELECT MAX(ct.fecha_actualizacion) AS fecha_actualizacion
        FROM competiciones_temporadas ct
        JOIN competiciones c
            ON ct.competicion_id = c.id
        WHERE c.tipo = 'members'";

$resultadoFecha = $conn->query($sql);
$filaActualizacion = $resultadoFecha->fetch_assoc();

$fechaHistorica = $filaActualizacion["fecha_actualizacion"];
?>

<?php include '../includes/footer.php'; ?>
</body>
</html>