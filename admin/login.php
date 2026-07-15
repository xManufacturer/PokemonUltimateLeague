<?php
session_start();
require_once "../config/conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = $_POST["usuario"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM admin WHERE usuario = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {

        $admin = $resultado->fetch_assoc();

        if (password_verify($password, $admin["password"])) {

            $_SESSION["admin"] = true;
            $_SESSION["usuario"] = $admin["usuario"];

            header("Location: index.php");
            exit;
        }
    }

    $error = "Usuario o contraseña incorrectos.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Acceso administrador</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<h1>Administración</h1>

<form method="post">

    <label class="login-label">Usuario</label><br>
    <input class="login-input" type="text" name="usuario" required><br><br>

    <label class="login-label">Contraseña</label><br>
    <input class="login-input" type="password" name="password" required><br><br>

    <button class="btn-registrar">Entrar</button>

</form>

<?php
if ($error != "") {
    echo "<p style='color:red'><strong>$error</strong></p>";
}
?>

</body>
</html>