<?php
require_once '../config/conexion.php';
$competicion = 11;

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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Champions League</title>
    <link rel="icon" href="../img/icono.png" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <h1>Champions League</h1>
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
</body>
</html>