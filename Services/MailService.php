<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private array $config;
    private string $lastError = '';

    public function __construct()
    {
        $configPath = __DIR__ . '/../config/mail.php';
        $examplePath = __DIR__ . '/../config/mail.example.php';

        if (is_file($configPath)) {
            $this->config = require $configPath;
            return;
        }

        $this->config = require $examplePath;
    }

    public function enviarCorreo(string $destinatario, string $asunto, string $mensajeHtml, array $contexto = []): bool
    {
        $mail = new PHPMailer(true);
        $this->lastError = '';
        $body = $this->envolverPlantilla($asunto, $mensajeHtml, $contexto);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port = (int) $this->config['port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($destinatario);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));

            return $mail->send();
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    private function envolverPlantilla(string $asunto, string $mensajeHtml, array $contexto): string
    {
        $nombre = htmlspecialchars((string) ($contexto['nombre'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
        $subtitulo = htmlspecialchars((string) ($contexto['subtitulo'] ?? 'Informacion de tu cuenta'), ENT_QUOTES, 'UTF-8');
        $avatarUrl = htmlspecialchars((string) ($contexto['avatar_url'] ?? ''), ENT_QUOTES, 'UTF-8');
        $items = $contexto['items'] ?? [];

        $itemsHtml = '';

        foreach ($items as $label => $valor) {
            $itemsHtml .= '
                <tr>
                    <td style="padding: 10px 0; color: #6b7280; font-size: 14px; border-bottom: 1px solid #eef2f7;">
                        ' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '
                    </td>
                    <td style="padding: 10px 0; color: #111827; font-size: 14px; font-weight: 700; text-align: right; border-bottom: 1px solid #eef2f7;">
                        ' . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') . '
                    </td>
                </tr>';
        }

        $avatarHtml = $avatarUrl !== ''
            ? '<img src="' . $avatarUrl . '" alt="Avatar" width="72" height="72" style="display:block;border-radius:50%;border:4px solid #dbe7ff;">'
            : '';

        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') . '</title>
        </head>
        <body style="margin:0;padding:0;background:#eef3fb;font-family:Arial,sans-serif;color:#172033;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3fb;padding:24px 12px;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 18px 48px rgba(23,32,51,0.12);">
                            <tr>
                                <td style="background:linear-gradient(135deg,#172033,#30456f);padding:28px 32px;color:#ffffff;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="vertical-align:middle;">
                                                <div style="font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.85;">Patron MVC</div>
                                                <h1 style="margin:10px 0 6px;font-size:28px;line-height:1.2;">' . htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') . '</h1>
                                                <p style="margin:0;font-size:15px;line-height:1.6;color:rgba(255,255,255,0.85);">' . $subtitulo . '</p>
                                            </td>
                                            <td align="right" style="width:92px;vertical-align:middle;">' . $avatarHtml . '</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:28px 32px 8px;">
                                    <p style="margin:0 0 18px;font-size:16px;color:#475569;">Hola ' . $nombre . ',</p>
                                    <div style="font-size:16px;line-height:1.75;color:#172033;">' . $mensajeHtml . '</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 32px 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fbff;border:1px solid #e4ecf8;border-radius:18px;padding:18px;">
                                        <tr>
                                            <td style="padding-bottom:10px;font-size:15px;font-weight:700;color:#172033;">Resumen</td>
                                        </tr>
                                        ' . $itemsHtml . '
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 32px 28px;font-size:13px;line-height:1.6;color:#64748b;">
                                    Este correo fue generado automaticamente por Patron MVC.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
}
