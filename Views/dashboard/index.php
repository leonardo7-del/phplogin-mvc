<?php
require_once __DIR__ . '/../../Controllers/UsersController.php';

$controller = new UsersController();
$viewData = $controller->procesarDashboard();
$usuario = $viewData['usuario'];
$mensaje = $viewData['mensaje'];
$tipoMensaje = $viewData['tipoMensaje'];
$iniciales = $viewData['iniciales'];
$miembroDesde = $viewData['miembroDesde'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/patron-mvc/Assets/css/dashboard.css">
</head>
<body>
    <main class="dashboard-shell">
        <section class="hero-card">
            <div class="hero-copy">
                <span class="eyebrow">Panel principal</span>
                <div class="connection-state">Conectado</div>
                <h1>Bienvenido, <?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>
                    Tu cuenta ya paso la validacion OTP y esta lista para usar el sistema.
                    Aqui puedes ver tu perfil, tu rol y el estado general de tu acceso.
                </p>

                <?php if ($mensaje !== ''): ?>
                    <div class="message <?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="hero-actions">
                    <a class="primary-btn" href="/patron-mvc/Views/dashboard/index.php">Actualizar panel</a>
                    <a class="ghost-btn" href="/patron-mvc/Views/dashboard/index.php?logout=1">Cerrar sesion</a>
                </div>
            </div>

            <aside class="profile-panel">
                <div class="profile-avatar"><?php echo htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?></div>
                <h2><?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="profile-mail"><?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="profile-badge"><?php echo strtoupper(htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8')); ?></div>
                <p class="profile-note">
                    Estado de la cuenta: acceso seguro con verificacion OTP y sesion activa.
                </p>
            </aside>
        </section>
    </main>
</body>
</html>
