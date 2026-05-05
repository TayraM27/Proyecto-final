<?php
/*--------------------------------------------------------------------------------------------
POST — guarda una solicitud de acogida
No requiere login (visitantes tambien pueden solicitarla)
Recibe: { idMascota, nombre, email, telefono, vivienda, experiencia, tiempo, mensaje } */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos       = json_decode(file_get_contents('php://input'), true) ?? [];
$idMascota   = (int)($datos['idMascota']   ?? 0);
$nombre      = limpiar($datos['nombre']    ?? '');
$email       = trim($datos['email']        ?? '');
$telefono    = limpiar($datos['telefono']  ?? '');
$vivienda    = limpiar($datos['vivienda']  ?? '');
$experiencia = limpiar($datos['experiencia'] ?? '');
$tiempo      = limpiar($datos['tiempo']    ?? '');
$mensaje     = limpiar($datos['mensaje']   ?? '');

if (!$idMascota || !$nombre || !$email || !$vivienda) {
    respuestaError('Faltan campos obligatorios: mascota, nombre, email y tipo de vivienda.');
}

if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}

if ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
    respuestaError('El telefono debe tener exactamente 9 digitos.');
}

$viviendas_validas = ['piso', 'casa_con_jardin', 'casa_sin_jardin', 'finca'];
if (!in_array($vivienda, $viviendas_validas, true)) {
    respuestaError('Tipo de vivienda no valido.');
}

if ($tiempo) {
    $tiempos_validos = ['menos_de_2h', '2_a_4h', 'mas_de_4h', 'disponibilidad_completa'];
    if (!in_array($tiempo, $tiempos_validos, true)) {
        respuestaError('Valor de tiempo no valido.');
    }
}

$pdo = conectar();

// Comprobar que la mascota existe y esta disponible para acogida
$stmt = $pdo->prepare(
    'SELECT idMascota FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND disponible_acogida = 1
     LIMIT 1'
);
$stmt->execute([$idMascota]);
if (!$stmt->fetch()) {
    respuestaError('Esta mascota no esta disponible para acogida.');
}

// Evitar solicitudes duplicadas del mismo email para la misma mascota
$stmt = $pdo->prepare(
    'SELECT idSolicitud FROM solicitudes_acogida
     WHERE idMascota = ? AND email = ? AND estado IN ("pendiente","en_revision")
     LIMIT 1'
);
$stmt->execute([$idMascota, $email]);
if ($stmt->fetch()) {
    respuestaError('Ya tienes una solicitud de acogida activa para esta mascota.');
}

// Obtener idUsuario si esta logueado
iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;

$stmt = $pdo->prepare(
    'INSERT INTO solicitudes_acogida (idUsuario, idMascota, nombre, email, telefono, vivienda, experiencia, tiempo, mensaje)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idMascota,
    $nombre,
    $email,
    $telefono ?: null,
    $vivienda,
    $experiencia ?: null,
    $tiempo ?: null,
    $mensaje ?: null
]);

respuestaOk(['mensaje' => 'Solicitud de acogida enviada correctamente. La protectora se pondra en contacto contigo.']);
