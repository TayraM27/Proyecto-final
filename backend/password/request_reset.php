<?php
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

/* Respuesta siempre igual para no revelar si el email existe */
if (!$usuario) {
    respuestaOk(['mensaje' => 'Si el email esta registrado recibiras un enlace en breve.']);
}

$idUsuario = (int)$usuario['idUsuario'];

/*--------------------------------------------------------------------------------------------
generar token */
$pdo->prepare('DELETE FROM password_resets WHERE idUsuario = ? AND used = 0')
    ->execute([$idUsuario]);

$token     = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

$pdo->prepare('INSERT INTO password_resets (idUsuario, token, expires_at) VALUES (?, ?, ?)')
    ->execute([$idUsuario, $token, $expiresAt]);

/*--------------------------------------------------------------------------------------------
construir enlace */
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$enlace    = $protocolo . '://' . $host . '/ProyectoCat/backend/password/reset.php?token=' . $token;

/*--------------------------------------------------------------------------------------------
enviar email via Mailtrap sandbox */
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'fa679f6a046324';
    $mail->Password   = '61d8b14693f68b';
    $mail->Port       = 2525;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('noreply@petfamily.local', 'PetFamily');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Recuperar contrasena - PetFamily';
    $mail->Body    = '
        <div style="font-family:Poppins,Arial,sans-serif;max-width:520px;margin:0 auto;padding:2em;">
            <h2 style="color:#1B358F;">&#128062; PetFamily</h2>
            <p>Hola,</p>
            <p>Has solicitado restablecer tu contrasena. Haz clic en el boton para continuar:</p>
            <p style="text-align:center;margin:2em 0;">
                <a href="' . htmlspecialchars($enlace) . '"
                   style="background:#1B358F;color:#fff;padding:0.8em 1.8em;border-radius:8px;text-decoration:none;font-weight:600;">
                    Restablecer contrasena
                </a>
            </p>
            <p style="font-size:0.85em;color:#888;">
                Este enlace caduca en <strong>1 hora</strong>.<br>
                Si no solicitaste esto, ignora este mensaje.
            </p>
            <hr style="border:none;border-top:1px solid #eee;margin:1.5em 0;">
            <p style="font-size:0.8em;color:#aaa;">PetFamily - Protectoras de Asturias</p>
        </div>';
    $mail->AltBody = "Restablece tu contrasena:\n\n" . $enlace . "\n\nCaduca en 1 hora.";

    $mail->send();
    respuestaOk(['mensaje' => 'Si el email esta registrado recibiras un enlace en breve.']);

} catch (Exception $e) {
    error_log('[PetFamily] PHPMailer error: ' . $mail->ErrorInfo);
    respuestaError('Error al enviar el email: ' . $mail->ErrorInfo);
}