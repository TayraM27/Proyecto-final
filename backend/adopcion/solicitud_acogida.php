<?php
/*--------------------------------------------------------------------------------------------
POST — guarda una solicitud de acogida
Tabla real: idSolicitud, idUsuario, idMascota, nombre, email, telefono,
            vivienda (enum), experiencia, tiempo (enum), mensaje, estado */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$esJson      = strpos($contentType, 'application/json') !== false;

if ($esJson) {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $post  = $datos;
    $files = [];
} else {
    $post  = $_POST;
    $files = $_FILES;
}

$idMascota  = (int)($post['idMascota']                      ?? 0);
$nombre     = limpiar($post['nombre']                       ?? '');
$email      = trim($post['email']                           ?? '');
$telefono   = limpiar($post['telefono']                     ?? '');
$vivienda   = $post['tipo_vivienda']                        ?? '';   /* enum: piso|casa_con_jardin|casa_sin_jardin|finca */
$experiencia= limpiar($post['experiencia_acogida']          ?? '');
$tiempo     = $post['tiempo_fuera_casa']                    ?? '';   /* enum: menos_de_2h|2_a_4h|mas_de_4h|disponibilidad_completa */
$motivo     = limpiar($post['motivo_acogida']               ?? '');
$acepta_politica = !empty($post['aceptar_politica_privacidad']) ? 1 : 0;
$mensaje    = limpiar($post['mensaje']                      ?? '');

/* Mapear valor del formulario al ENUM de la tabla */
$viviendaMap = [
    'piso'         => 'piso',
    'casa'         => 'casa_sin_jardin',
    'casa_jardin'  => 'casa_con_jardin',
    'finca'        => 'finca',
    'casa_con_jardin'  => 'casa_con_jardin',
    'casa_sin_jardin'  => 'casa_sin_jardin',
];
$vivienda = $viviendaMap[$vivienda] ?? 'piso';

$tiempoMap = [
    'menos_2h'             => 'menos_de_2h',
    'menos_de_2h'          => 'menos_de_2h',
    '2_4h'                 => '2_a_4h',
    '2_a_4h'               => '2_a_4h',
    'mas_4h'               => 'mas_de_4h',
    'mas_de_4h'            => 'mas_de_4h',
    'disponible'           => 'disponibilidad_completa',
    'disponibilidad_completa' => 'disponibilidad_completa',
];
$tiempo = $tiempoMap[$tiempo] ?? 'menos_de_2h';

/* Validaciones */
if (!$idMascota || !$nombre || !$email) {
    respuestaError('Faltan campos obligatorios: mascota, nombre y email.');
}
if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}
if ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
    respuestaError('El telefono debe tener exactamente 9 digitos.');
}
if (!$acepta_politica) {
    respuestaError('Debes aceptar la politica de privacidad.');
}
if ($experiencia && strlen($experiencia) < 20) {
    respuestaError('La experiencia debe tener al menos 20 caracteres.');
}
if ($motivo && strlen($motivo) < 20) {
    respuestaError('El motivo debe tener al menos 20 caracteres.');
}

$pdo = conectar();

$stmt = $pdo->prepare(
    'SELECT idMascota, idProtectora FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND disponible_acogida = 1 LIMIT 1'
);
$stmt->execute([$idMascota]);
$mascota = $stmt->fetch();
if (!$mascota) {
    respuestaError('Esta mascota no está disponible para acogida.');
}

/* Verificar protectora activa */
$stmtProt = $pdo->prepare('SELECT activa FROM protectoras WHERE idProtectora = ? LIMIT 1');
$stmtProt->execute([$mascota['idProtectora']]);
$prot = $stmtProt->fetch();
if (!$prot || !$prot['activa']) {
    respuestaError('Esta protectora está temporalmente suspendida. No se pueden enviar solicitudes en este momento.');
}

$stmt = $pdo->prepare(
    'SELECT idSolicitud FROM solicitudes_acogida
     WHERE idMascota = ? AND email = ? AND estado IN ("pendiente","en_revision") LIMIT 1'
);
$stmt->execute([$idMascota, $email]);
if ($stmt->fetch()) {
    respuestaError('Ya tienes una solicitud de acogida activa para esta mascota.');
}

iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;
session_write_close();

/* Concatenar experiencia + motivo en el campo texto disponible */
$textoExperiencia = trim(($experiencia ? 'Experiencia: ' . $experiencia . "\n\n" : '') . ($motivo ? 'Motivo: ' . $motivo : ''));

$stmt = $pdo->prepare(
    'INSERT INTO solicitudes_acogida
     (idUsuario, idMascota, nombre, email, telefono, vivienda, experiencia, tiempo, mensaje)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idMascota,
    $nombre,
    $email,
    $telefono ?: null,
    $vivienda,
    $textoExperiencia ?: null,
    $tiempo,
    $mensaje ?: null,
]);

/* Notificación a la protectora */
$idProtectora = (int)$mascota['idProtectora'];
if ($idProtectora) {
    $pdo->prepare(
        'INSERT INTO notificaciones (idProtectora, tipo, mensaje, ruta_destino)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $idProtectora,
        'solicitud_acogida',
        $nombre . ' ha enviado una solicitud de acogida',
        'admin/solicitudes-protectora.html',
    ]);
}

respuestaOk(['mensaje' => 'Solicitud de acogida enviada correctamente. La protectora se pondra en contacto contigo por email.']);