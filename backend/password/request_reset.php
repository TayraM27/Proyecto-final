<?php
/*--------------------------------------------------------------------------------------------
POST -- solicitud de recuperacion de contrasena
SMTP: Gmail (smtp.gmail.com:587 STARTTLS)
Render: configurar MAIL_USER y MAIL_PASS como variables de entorno */

require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/PHPmailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPmailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($datos['email'] ?? '');

if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}

$pdo  = conectar();
$stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1');
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario) {
    respuestaOk(['mensaje' => 'Si el email esta registrado recibiras un enlace en breve.']);
}

$idUsuario = (int)$usuario['idUsuario'];

$pdo->prepare('DELETE FROM password_resets WHERE idUsuario = ? AND used = 0')
    ->execute([$idUsuario]);

$token     = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

$pdo->prepare('INSERT INTO password_resets (idUsuario, token, expires_at) VALUES (?, ?, ?)')
    ->execute([$idUsuario, $token, $expiresAt]);

/* Render usa X-Forwarded-Proto para indicar HTTPS */
$esHttps   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocolo = $esHttps ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$enlace    = $protocolo . '://' . $host . '/backend/password/reset.php?token=' . $token;

$mailUser = getenv('MAIL_USER') ?: 'lpsdiscs@gmail.com';
$mailPass = getenv('MAIL_PASS') ?: '';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailUser;
    $mail->Password   = $mailPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($mailUser, 'PetFamily');
    $mail->addAddress($email);
    $mail->isHTML(true);

    /* Subject codificado en base64 para evitar problemas con tildes */
    $mail->Subject = '=?UTF-8?B?' . base64_encode('Recupera tu contrasena - PetFamily') . '?=';

    /* Body con entidades HTML -- no depende del encoding del archivo PHP */
    $mail->Body = '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body>
<div style="font-family:Poppins,Arial,sans-serif;max-width:520px;margin:0 auto;padding:2em;background:#f9f9f9;border-radius:12px;">
    <div style="text-align:center;margin-bottom:1.5em;">
        <span style="font-size:1.5rem;font-weight:700;color:#1B358F;">Pet<span style="color:#F8BA56;">Family</span></span>
    </div>
    <h2 style="color:#1B358F;font-size:1.1rem;margin-bottom:0.5em;">Restablecer contrase&ntilde;a</h2>
    <p style="color:#444;font-size:0.92rem;">Hola,</p>
    <p style="color:#444;font-size:0.92rem;">
        Has solicitado restablecer tu contrase&ntilde;a en PetFamily.<br>
        Haz clic en el bot&oacute;n para continuar:
    </p>
    <p style="text-align:center;margin:2em 0;">
        <a href="' . htmlspecialchars($enlace) . '"
           style="background:#1B358F;color:#fff;padding:0.9em 2em;border-radius:8px;
                  text-decoration:none;font-weight:700;font-size:0.95rem;display:inline-block;">
            Restablecer contrase&ntilde;a
        </a>
    </p>
    <p style="font-size:0.82rem;color:#888;text-align:center;">
        Este enlace caduca en <strong>1 hora</strong>.<br>
        Si no solicitaste esto, ignora este mensaje.
    </p>
    <hr style="border:none;border-top:1px solid #eee;margin:1.5em 0;">
    <p style="font-size:0.78rem;color:#bbb;text-align:center;">
        PetFamily &mdash; Protectoras de Asturias
    </p>
</div>
</body>
</html>';

    /* Texto plano sin tildes para clientes que no soporten HTML */
    $mail->AltBody = "Hola,\n\nRestablece tu contrasena en PetFamily:\n\n"
                   . $enlace
                   . "\n\nEste enlace caduca en 1 hora.\n"
                   . "Si no lo solicitaste, ignora este mensaje.\n\n"
                   . "PetFamily - Protectoras de Asturias";

    $mail->send();
    respuestaOk(['mensaje' => 'Si el email esta registrado recibiras un enlace en breve.']);

} catch (Exception $e) {
    error_log('[PetFamily] PHPMailer error: ' . $mail->ErrorInfo);
    respuestaError('Error al enviar el correo. Intentalo de nuevo mas tarde.');
}