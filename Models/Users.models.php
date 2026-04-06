<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../Services/MailService.php';

class UsersModel
{
    private const ADMIN_EMAILS = [
    'leonardohuarachadelvillar@gmail.com',
    'isistemas2022@gmail.com',
    ];
    private const APP_TIMEZONE = 'America/Lima';

    private PDO $db;
    private MailService $mailService;

    public function __construct()
    {
        $this->db = Conexion::conectar();
        $this->mailService = new MailService();
    }

    public function registrarUsuario(string $nombre, string $email, string $password): array
    {
        $nombre = trim($nombre);
        $email = strtolower(trim($email));
        $rol = $this->resolverRol($email);

        if ($nombre === '' || $email === '' || $password === '') {
            return ['ok' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'El correo no tiene un formato valido.'];
        }

        $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            return ['ok' => false, 'message' => 'Ya existe una cuenta registrada con ese correo.'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password, rol, creado_en)
             VALUES (:nombre, :email, :password, :rol, NOW())'
        );

        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => $rol,
        ]);

        $userId = (int) $this->db->lastInsertId();
        $otpInfo = $this->generarOtpParaUsuario($userId);
        $mailInfo = '';

        if ($otpInfo['ok']) {
            $correoOtpEnviado = $this->enviarOtpPorCorreo($nombre, $email, $otpInfo['otp'], $rol, 'registro');
            $mailInfo .= $correoOtpEnviado
                ? ' Se envio un OTP de registro al correo del usuario.'
                : ' No se pudo enviar el OTP de registro. ' . $this->obtenerDetalleMail();
        } else {
            $mailInfo .= ' No se pudo generar el OTP de registro.';
        }

        if ($rol === 'usuario') {
            $notificado = $this->notificarRegistroAlAdmin($nombre, $email);
            $mailInfo = $notificado
                ? $mailInfo . ' Se notifico al administrador por correo.'
                : $mailInfo . ' No se pudo notificar al administrador por correo. ' . $this->obtenerDetalleMail();
        }

        return [
            'ok' => true,
            'message' => 'Usuario registrado correctamente con rol ' . $rol . '.' . $mailInfo,
            'user_id' => $userId,
            'email' => $email,
        ];
    }

    public function iniciarLogin(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'Ingresa tu correo y tu contrasena.'];
        }

        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $expiraEn = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET otp_codigo = :otp_codigo,
                 otp_expira_en = :otp_expira_en,
                 otp_verificado = 0
             WHERE id = :id'
        );

        $stmt->execute([
            'otp_codigo' => $otpHash,
            'otp_expira_en' => $expiraEn,
            'id' => $usuario['id'],
        ]);

        $correoEnviado = $this->enviarOtpPorCorreo($usuario['nombre'], $usuario['email'], $otp, $usuario['rol'], 'acceso');

        return [
            'ok' => $correoEnviado,
            'message' => $correoEnviado
                ? 'OTP generado y enviado al correo correctamente.'
                : 'Se genero el OTP, pero no se pudo enviar el correo. ' . $this->obtenerDetalleMail(),
            'user_id' => (int) $usuario['id'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
        ];
    }

    public function verificarOtp(int $userId, string $otpIngresado): array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return ['ok' => false, 'message' => 'El usuario no existe.'];
        }

        if (empty($usuario['otp_codigo']) || empty($usuario['otp_expira_en'])) {
            return ['ok' => false, 'message' => 'No hay un OTP activo para este usuario.'];
        }

        if (strtotime((string) $usuario['otp_expira_en']) < time()) {
            return ['ok' => false, 'message' => 'El OTP ha expirado. Vuelve a iniciar sesion.'];
        }

        if (!password_verify(trim($otpIngresado), $usuario['otp_codigo'])) {
            return ['ok' => false, 'message' => 'El codigo OTP es incorrecto.'];
        }

        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET otp_codigo = NULL,
                 otp_expira_en = NULL,
                 otp_verificado = 1
             WHERE id = :id'
        );

        $stmt->execute(['id' => $usuario['id']]);

        return [
            'ok' => true,
            'message' => 'Autenticacion completada correctamente.',
            'usuario' => $usuario,
        ];
    }

    public function obtenerUsuarioPorId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, rol, otp_verificado, creado_en
             FROM usuarios WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    private function resolverRol(string $email): ?string
    {
        if (in_array($email, self::ADMIN_EMAILS, true)) {
            return 'admin';
        }

        return 'usuario';
    }

    private function generarOtpParaUsuario(int $userId): array
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $expiraEn = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET otp_codigo = :otp_codigo,
                 otp_expira_en = :otp_expira_en,
                 otp_verificado = 0
             WHERE id = :id'
        );

        $ok = $stmt->execute([
            'otp_codigo' => $otpHash,
            'otp_expira_en' => $expiraEn,
            'id' => $userId,
        ]);

        return [
            'ok' => $ok,
            'otp' => $otp,
        ];
    }

    private function enviarOtpPorCorreo(string $nombre, string $email, string $otp, string $rol, string $motivo): bool
    {
        $asunto = 'Tu codigo OTP de acceso';
        $mensajeHtml = '
            <p>Tu codigo OTP es:</p>
            <div style="display:inline-block;margin:8px 0 14px;padding:14px 18px;border-radius:16px;background:#172033;color:#ffffff;font-size:28px;font-weight:700;letter-spacing:4px;">
                ' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '
            </div>
            <p>Usa este codigo para completar la verificacion del ' . htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') . '.</p>
            <p>Este OTP vence en 5 minutos. Si no reconoces esta solicitud, ignora este correo.</p>
        ';

        return $this->mailService->enviarCorreo($email, $asunto, $mensajeHtml, [
            'nombre' => $nombre,
            'subtitulo' => 'Verificacion OTP para tu cuenta',
            'avatar_url' => $this->generarAvatarUrl($nombre),
            'items' => [
                'Nombre' => $nombre,
                'Correo' => $email,
                'Rol' => $rol,
                'Motivo' => ucfirst($motivo),
                'Expira en' => '5 minutos',
            ],
        ]);
    }

    private function notificarRegistroAlAdmin(string $nombre, string $email): bool
    {
        $asunto = 'Nuevo usuario registrado';
        $mensajeHtml = '
            <p>Se ha registrado un nuevo usuario en la plataforma.</p>
            <p>Revisa el detalle del registro en el resumen inferior.</p>
        ';

        $enviado = true;

        foreach (self::ADMIN_EMAILS as $adminEmail) {
            $ok = $this->mailService->enviarCorreo($adminEmail, $asunto, $mensajeHtml, [
                'nombre' => 'Administrador',
                'subtitulo' => 'Se detecto un nuevo registro en la aplicacion',
                'avatar_url' => $this->generarAvatarUrl($nombre),
                'items' => [
                    'Nombre del usuario' => $nombre,
                    'Correo' => $email,
                    'Rol asignado' => 'usuario',
                    'Registrado en' => $this->obtenerFechaActualLima(),
                    'Notificado a' => $adminEmail,
                ],
            ]);

            if (!$ok) {
                $enviado = false;
            }
        }

        return $enviado;
    }

    private function generarAvatarUrl(string $nombre): string
    {
        return 'https://ui-avatars.com/api/?background=172033&color=ffffff&size=160&name=' . rawurlencode($nombre);
    }

    private function obtenerFechaActualLima(): string
    {
        $fecha = new DateTimeImmutable('now', new DateTimeZone(self::APP_TIMEZONE));

        return $fecha->format('d/m/Y H:i');
    }

    private function obtenerDetalleMail(): string
    {
        $detalle = trim($this->mailService->getLastError());

        if ($detalle === '') {
            return 'Revisa la configuracion SMTP de Gmail en config/mail.php.';
        }

        return 'Detalle: ' . $detalle;
    }
}
