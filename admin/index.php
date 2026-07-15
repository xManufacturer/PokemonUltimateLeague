<?php
require_once '../config/conexion.php';
require_once 'proteger.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <h1>Panel de Administración</h1>
    <a href="../index.php" class="btn-inicio">
        <img src="../img/inicio.png" alt="Inicio">
    </a>
    <a class="btn-registrar" href="../index.php">Volver</a>
    <div class="tarjetas">
        <a href="registrar_combate.php">Registrar combate</a>
        <a href="editar_combate.php">Editar combate</a>
        <a href="eliminar_combate.php">Eliminar combate</a>
    </div><br><br>
    <a class="btn-registrar" href="logout.php" class="btn-logout">Cerrar sesión</a>
</body>
</html>