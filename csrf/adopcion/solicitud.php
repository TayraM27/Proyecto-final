<?php
/*--------------------------------------------------------------------------------------------
POST — guarda una solicitud de adopcion
No requiere login (visitantes tambien pueden solicitarla)
Recibe: { idMascota, nombre, email, telefono, mensaje } */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos    = json_decode(file_get_contents('php://input'), true) ?? [];
$idMascota= (int)($datos['idMascota'] ?? 0);
$nombre   = limpiar($datos['nombre']  ?? '');
$email    = trim($datos['email']      ?? '');
$telefono = limpiar($datos['telefono']?? '');
$mensaje  = limpiar($datos['mensaje'] ?? '');

if (!$idMascota || !$nombre || !$email) {
    respuestaError('Faltan campos obligatorios: mascota, nombre y email.');
}

if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}

$pdo = conectar();

// Comprobar que la mascota existe y esta disponible
$stmt = $pdo->prepare(
    'SELECT idMascota FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND estado_adopcion = "disponible"
     LIMIT 1'
);
$stmt->execute([$idMascota]);
if (!$stmt->fetch()) {
    respuestaError('Esta mascota no esta disponible para adopcion.');
}

// Evitar solicitudes duplicadas del mismo email para la misma mascota
$stmt = $pdo->prepare(
    'SELECT idSolicitud FROM solicitudes_adopcion
     WHERE idMascota = ? AND email = ? AND estado IN ("pendiente","en_revision")
     LIMIT 1'
);
$stmt->execute([$idMascota, $email]);
if ($stmt->fetch()) {
    respuestaError('Ya tienes una solicitud activa para esta mascota.');
}

// Obtener idUsuario si esta logueado
iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;

$stmt = $pdo->prepare(
    'INSERT INTO solicitudes_adopcion (idUsuario, idMascota, nombre, email, telefono, mensaje)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$idUsuario, $idMascota, $nombre, $email, $telefono ?: null, $mensaje ?: null]);

respuestaOk(['mensaje' => 'Solicitud enviada correctamente. La protectora se pondra en contacto contigo.']);