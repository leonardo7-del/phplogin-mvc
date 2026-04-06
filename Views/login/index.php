<?php
require_once __DIR__ . '/../../Controllers/UsersController.php';

$controller = new UsersController();
$viewData = $controller->procesarLogin();
$mensaje = $viewData['mensaje'];
$tipoMensaje = $viewData['tipoMensaje'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP</title>
    <link rel="stylesheet" href="/patron-mvc/Assets/css/auth.css">
</head>
<body>
    <div class="card">
        <h1>Iniciar sesion</h1>
        <p>Ingresa tus credenciales. Si son correctas, se enviara un OTP al correo del usuario o administrador.</p>

        <?php if ($mensaje !== ''): ?>
            <div class="message <?php echo $tipoMensaje === 'success' ? 'success' : ''; ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Correo electronico" required>
            <input type="password" name="password" placeholder="Contrasena" required>
            <button type="submit">Continuar con OTP</button>
        </form>

        <p>Si todavia no tienes cuenta, registrate en <a href="/patron-mvc/Views/registro/registro.php">registro</a>.</p>
    </div>
</body>
</html>
