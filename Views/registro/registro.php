<?php
require_once __DIR__ . '/../../Controllers/UsersController.php';

$controller = new UsersController();
$viewData = $controller->procesarRegistro();
$mensaje = $viewData['mensaje'];
$tipoMensaje = $viewData['tipoMensaje'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="/patron-mvc/Assets/css/auth.css">
</head>
<body>
    <div class="card">
        <h1>Crear cuenta</h1>
        <p>Al registrarte se enviara un codigo OTP a tu correo y tambien podras iniciar sesion con OTP.</p>

        <?php if ($mensaje !== ''): ?>
            <div class="message <?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="email" name="email" placeholder="Correo electronico" required>
            <input type="password" name="password" placeholder="Contrasena" required>
            <button type="submit">Registrar usuario</button>
        </form>

        <p>Si ya tienes cuenta, entra desde <a href="/patron-mvc/Views/login/index.php">login</a>.</p>
    </div>
</body>
</html>
