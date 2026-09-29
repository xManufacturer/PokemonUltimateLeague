<?php
require_once '../config/conexion.php';
require_once '../includes/clasificacion.php';
$competicion = 2;

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

$historica = obtenerClasificacionHistorica($conn, "Johto");

$clasificacionHistoricaPrimera = $historica["primera"];
$clasificacionHistoricaSegunda = $historica["segunda"];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Liga Johto</title>
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
    <h1>Liga Johto</h1>
        <div>
            <img src="../img/logos/liga_johto.png" alt="Liga Johto" class="logos">
        </div>
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

<div class="clasificacion-contenedor">

    <div class="clasificacion-columna">

        <table>

            <tr>
                <th>Pos</th>
                <th>Pokémon</th>
                <th>Temp</th>
                <th>Com</th>
                <th>V</th>
                <th>E</th>
                <th>D</th>
                <th>% V</th>
                <th>PS+</th>
                <th>PS-</th>
                <th>Dif</th>
                <th>Pts</th>
            </tr>

            <?php

            // ==================================================
            // PRIMERA DIVISIÓN
            // ==================================================

            $posicion = 1;

            foreach ($clasificacionHistoricaPrimera as $fila) {

            ?>

            <tr>

                <td>
                    <strong><?php echo $posicion; ?></strong>
                </td>

                <td>
                    <div class="pokemon-clasificacion">

                        <img src="../img/pokemon/<?php echo $fila["imagen"]; ?>">

                        <span>
                            <?php echo $fila["nombre"]; ?>
                        </span>

                    </div>
                </td>

                <td><?php echo $fila["temporadas"]; ?></td>
                <td><?php echo $fila["com"]; ?></td>
                <td><?php echo $fila["v"]; ?></td>
                <td><?php echo $fila["e"]; ?></td>
                <td><?php echo $fila["d"]; ?></td>

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

                <td><?php echo $fila["ps_favor"]; ?></td>
                <td><?php echo $fila["ps_contra"]; ?></td>

                <td>
                    <strong><?php echo $fila["diferencia"]; ?></strong>
                </td>

                <td>
                    <strong><?php echo $fila["puntos"]; ?></strong>
                </td>

            </tr>

            <?php

                $posicion++;

            }


            // ==================================================
            // SEGUNDA DIVISIÓN
            // ==================================================

            foreach ($clasificacionHistoricaSegunda as $fila) {

            ?>

            <tr class="historica-segunda">

                <td>
                    <strong><?php echo $posicion; ?></strong>
                </td>

                <td>
                    <div class="pokemon-clasificacion">

                        <img src="../img/pokemon/<?php echo $fila["imagen"]; ?>">

                        <span>
                            <?php echo $fila["nombre"]; ?>
                        </span>

                    </div>
                </td>

                <td><?php echo $fila["temporadas"]; ?></td>
                <td><?php echo $fila["com"]; ?></td>
                <td><?php echo $fila["v"]; ?></td>
                <td><?php echo $fila["e"]; ?></td>
                <td><?php echo $fila["d"]; ?></td>

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

                <td><?php echo $fila["ps_favor"]; ?></td>
                <td><?php echo $fila["ps_contra"]; ?></td>

                <td>
                    <strong><?php echo $fila["diferencia"]; ?></strong>
                </td>

                <td>
                    <strong><?php echo $fila["puntos"]; ?></strong>
                </td>

            </tr>

            <?php

                $posicion++;

            }

            ?>

        </table>

    </div>

</div>

<?php
$sql = "SELECT MAX(fecha_actualizacion) AS fecha_actualizacion
        FROM competiciones_temporadas
        WHERE competicion_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $competicion);
$stmt->execute();

$filaHistorica = $stmt->get_result()->fetch_assoc();

$fechaHistorica = $filaHistorica["fecha_actualizacion"];
?>

<?php include '../includes/footer.php'; ?>

</body>
</html>
