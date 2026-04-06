<?php
require_once __DIR__ . '/../../Controllers/UsersController.php';

$controller = new UsersController();
$viewData = $controller->procesarVerificacionOtp();
$mensaje = $viewData['mensaje'];
$tipoMensaje = $viewData['tipoMensaje'];
$usuarioAutenticado = $viewData['usuarioAutenticado'];
$emailPendiente = $viewData['emailPendiente'];
$hayOtpPendiente = $viewData['hayOtpPendiente'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar OTP</title>
    <link rel="stylesheet" href="/patron-mvc/Assets/css/auth.css">
</head>
<body>
    <div class="card">
        <?php if ($usuarioAutenticado): ?>
            <h1>Acceso concedido</h1>
            <?php if ($mensaje !== ''): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <p>Has iniciado sesion correctamente con doble factor OTP.</p>
            <ul>
                <li>Nombre: <?php echo htmlspecialchars($usuarioAutenticado['nombre'], ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Correo: <?php echo htmlspecialchars($usuarioAutenticado['email'], ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Rol: <?php echo htmlspecialchars($usuarioAutenticado['rol'], ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Estado OTP: Verificado</li>
            </ul>
            <a class="link-button" href="/patron-mvc/Views/verificar_otp/verificar_otp.php?logout=1">Cerrar sesion</a>
        <?php else: ?>
            <h1>Validar OTP</h1>
            <p>Escribe el codigo de 6 digitos generado despues del login.</p>

            <?php if ($mensaje !== ''): ?>
                <div class="message <?php echo $tipoMensaje; ?>">
                    <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!$hayOtpPendiente): ?>
                <div class="message error">No hay un login pendiente. Vuelve a iniciar sesion.</div>
                <a class="link-button" href="/patron-mvc/Views/login/index.php">Ir al login</a>
            <?php else: ?>
                <?php if ($emailPendiente): ?>
                    <p>Revisa el correo enviado a <?php echo htmlspecialchars((string) $emailPendiente, ENT_QUOTES, 'UTF-8'); ?>.</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="otp" maxlength="6" minlength="6" placeholder="000000" required>
                    <button type="submit">Verificar acceso</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="/patron-mvc/Assets/js/auth.js"></script>
</body>
</html>
