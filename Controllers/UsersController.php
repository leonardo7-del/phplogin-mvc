<?php

require_once __DIR__ . '/../Models/Users.models.php';

class UsersController
{
    private UsersModel $modelo;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->modelo = new UsersModel();
    }

    public function procesarRegistro(): array
    {
        $data = [
            'mensaje' => '',
            'tipoMensaje' => 'error',
        ];

        if (isset($_SESSION['auth_message'])) {
            $data['mensaje'] = (string) $_SESSION['auth_message'];
            unset($_SESSION['auth_message']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $this->modelo->registrarUsuario(
                $_POST['nombre'] ?? '',
                $_POST['email'] ?? '',
                $_POST['password'] ?? ''
            );

            if ($resultado['ok']) {
                $_SESSION['otp_user_id'] = $resultado['user_id'];
                $_SESSION['otp_email'] = $resultado['email'];

                header('Location: /patron-mvc/Views/verificar_otp/verificar_otp.php');
                exit;
            }

            $data['mensaje'] = $resultado['message'];
            $data['tipoMensaje'] = $resultado['ok'] ? 'success' : 'error';
        }

        return $data;
    }

    public function procesarLogin(): array
    {
        $data = [
            'mensaje' => '',
            'tipoMensaje' => 'error',
        ];

        if (isset($_GET['logout'])) {
            session_unset();
            session_destroy();
            session_start();
            $data['mensaje'] = 'Sesion cerrada correctamente.';
            $data['tipoMensaje'] = 'success';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $this->modelo->iniciarLogin($_POST['email'] ?? '', $_POST['password'] ?? '');

            if ($resultado['ok']) {
                $_SESSION['otp_user_id'] = $resultado['user_id'];
                $_SESSION['otp_email'] = $resultado['email'];

                header('Location: /patron-mvc/Views/verificar_otp/verificar_otp.php');
                exit;
            }

            $data['mensaje'] = $resultado['message'];
        }

        return $data;
    }

    public function procesarVerificacionOtp(): array
    {
        if (isset($_GET['logout'])) {
            session_unset();
            session_destroy();
            header('Location: /patron-mvc/Views/login/index.php?logout=1');
            exit;
        }

        if (!empty($_SESSION['auth_user_id']) && !isset($_SESSION['otp_user_id'])) {
            header('Location: /patron-mvc/Views/dashboard/index.php');
            exit;
        }

        $data = [
            'mensaje' => $_SESSION['auth_message'] ?? '',
            'tipoMensaje' => 'error',
            'usuarioAutenticado' => null,
            'emailPendiente' => $_SESSION['otp_email'] ?? null,
            'hayOtpPendiente' => isset($_SESSION['otp_user_id']),
        ];

        if (isset($_SESSION['auth_message'])) {
            unset($_SESSION['auth_message']);
        }

        if (!empty($_SESSION['auth_user_id'])) {
            $data['usuarioAutenticado'] = $this->modelo->obtenerUsuarioPorId((int) $_SESSION['auth_user_id']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = isset($_SESSION['otp_user_id']) ? (int) $_SESSION['otp_user_id'] : 0;
            $resultado = $this->modelo->verificarOtp($userId, $_POST['otp'] ?? '');

            if ($resultado['ok']) {
                $_SESSION['auth_user_id'] = $userId;
                unset($_SESSION['otp_user_id'], $_SESSION['otp_email']);
                $_SESSION['auth_message'] = $resultado['message'];

                header('Location: /patron-mvc/Views/dashboard/index.php');
                exit;
            } else {
                $data['mensaje'] = $resultado['message'];
            }
        }

        return $data;
    }

    public function procesarDashboard(): array
    {
        if (isset($_GET['logout'])) {
            session_unset();
            session_destroy();
            header('Location: /patron-mvc/Views/login/index.php?logout=1');
            exit;
        }

        if (empty($_SESSION['auth_user_id'])) {
            header('Location: /patron-mvc/Views/login/index.php');
            exit;
        }

        $usuario = $this->modelo->obtenerUsuarioPorId((int) $_SESSION['auth_user_id']);

        if (!$usuario) {
            session_unset();
            session_destroy();
            header('Location: /patron-mvc/Views/login/index.php');
            exit;
        }

        $mensaje = $_SESSION['auth_message'] ?? '';
        unset($_SESSION['auth_message']);

        return [
            'usuario' => $usuario,
            'mensaje' => $mensaje,
            'tipoMensaje' => $mensaje !== '' ? 'success' : '',
            'iniciales' => $this->obtenerIniciales($usuario['nombre']),
            'miembroDesde' => date('d/m/Y', strtotime((string) $usuario['creado_en'])),
        ];
    }

    private function obtenerIniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre)) ?: [];
        $iniciales = '';

        foreach ($partes as $parte) {
            if ($parte !== '') {
                $iniciales .= strtoupper(substr($parte, 0, 1));
            }

            if (strlen($iniciales) === 2) {
                break;
            }
        }

        return $iniciales !== '' ? $iniciales : 'US';
    }
}
