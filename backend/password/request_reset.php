<?php
require_once __DIR__ . '/../includes/funciones.php';

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

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$enlace    = $protocolo . '://' . $host . '/backend/password/reset.php?token=' . $token;

$apiKey = getenv('RESEND_API_KEY') ?: '';

$cuerpoHtml = '
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:2em;background:#f9f9f9;border-radius:12px;">
    <div style="text-align:center;margin-bottom:1.5em;">
        <span style="font-size:1.5rem;font-weight:700;color:#1B358F;">Pet<span style="color:#F8BA56;">Family</span></span>
    </div>
    <h2 style="color:#1B358F;font-size:1.1rem;">Restablecer contrasena</h2>
    <p style="color:#444;font-size:0.92rem;">Has solicitado restablecer tu contrasena. Haz clic para continuar:</p>
    <p style="text-align:center;margin:2em 0;">
        <a href="' . htmlspecialchars($enlace) . '"
           style="background:#1B358F;color:#fff;padding:0.9em 2em;border-radius:8px;
                  text-decoration:none;font-weight:700;font-size:0.95rem;display:inline-block;">
            Restablecer contrasena
        </a>
    </p>
    <p style="font-size:0.82rem;color:#888;text-align:center;">
        Caduca en <strong>1 hora</strong>. Si no lo solicitaste, ignora este mensaje.
    </p>
    <p style="font-size:0.78rem;color:#bbb;text-align:center;">PetFamily &mdash; Protectoras de Asturias</p>
</div>';

$payload = json_encode([
    'from'    => 'PetFamily <onboarding@resend.dev>',
    'to'      => [$email],
    'subject' => 'Recuperar contrasena - PetFamily',
    'html'    => $cuerpoHtml,
    'text'    => "Restablece tu contrasena:\n\n" . $enlace . "\n\nCaduca en 1 hora.",
]);

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
]);

$respuesta  = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log('[PetFamily] Resend curl error: ' . $curlError);
    respuestaError('Error al enviar el correo. Intentalo de nuevo mas tarde.');
}

$resultado = json_decode($respuesta, true);

if ($httpCode !== 200 && $httpCode !== 201) {
    error_log('[PetFamily] Resend error: ' . $respuesta);
    respuestaError('Error al enviar el correo. Inténtalo de nuevo más tarde.');
}

respuestaOk(['mensaje' => 'Si el email esta registrado recibirás un enlace en breve.']);