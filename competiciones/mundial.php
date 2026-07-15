<?php
require_once '../config/conexion.php';
$competicion = 12;

$sql = "
SELECT
    ct.id,
    t.numero
FROM competiciones_temporadas ct
JOIN temporadas t ON ct.temporada_id=t.id
WHERE ct.competicion_id=12
ORDER BY t.numero DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$temporadas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mundial</title>
<link rel="stylesheet" href="../css/style.css">
</head>

<body>

<h1>Mundial</h1>

    <a href="../index.php" class="btn-inicio">
        <img src="../img/inicio.png" alt="Inicio">
    </a>

<div class="tarjetas">

<?php while ($fila = $temporadas->fetch_assoc()) { ?>

    <a href="mundial_temporada.php?id=<?php echo $fila["id"]; ?>">
        Temporada <?php echo $fila["numero"]; ?>
    </a>

<?php } ?>

</div>

</body>
</html>