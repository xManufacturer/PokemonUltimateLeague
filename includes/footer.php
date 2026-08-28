<?php

$fechaActualizacion = "";
$filaActualizacion = null;

if (isset($id)) {

    $sql = "SELECT fecha_actualizacion
            FROM competiciones_temporadas
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $filaActualizacion = $stmt->get_result()->fetch_assoc();

} elseif (isset($ultimaCompeticion["competicion_temporada_id"])) {

    $sql = "SELECT fecha_actualizacion
            FROM competiciones_temporadas
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "i",
        $ultimaCompeticion["competicion_temporada_id"]
    );
    $stmt->execute();

    $filaActualizacion = $stmt->get_result()->fetch_assoc();

} elseif (isset($fechaHistorica)) {

    $filaActualizacion = [
        "fecha_actualizacion" => $fechaHistorica
    ];

}

if (!empty($filaActualizacion["fecha_actualizacion"])) {

    $fecha = new DateTime(
        $filaActualizacion["fecha_actualizacion"],
        new DateTimeZone("UTC")
    );

    $fecha->setTimezone(new DateTimeZone("Europe/Madrid"));

    $fechaActualizacion = $fecha->format("d/m/Y");
}

?>

<footer class="pie-pagina">
    <p>
        <strong>Última actualización de resultados: </strong>
        <?php echo $fechaActualizacion; ?>
    </p>

    <p>
        Creado por
        <a href="https://discord.com/users/380751682356641793" target="_blank">
            Manufacturer
        </a>
    </p>
</footer>