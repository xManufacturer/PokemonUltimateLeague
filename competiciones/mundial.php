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