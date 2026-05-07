<?php
/*--------------------------------------------------------------------------------------------
POST — guarda una solicitud de adopción */

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

$idMascota      = (int)($post['idMascota']                  ?? 0);
$nombre         = limpiar($post['nombre']                   ?? '');
$dni            = limpiar($post['dni']                      ?? '');
$fecha_nac      = $post['fecha_nacimiento']                 ?? '';
$email          = trim($post['email']                       ?? '');
$telefono       = limpiar($post['telefono']                 ?? '');
$direccion      = limpiar($post['direccion_completa']       ?? '');
$localidad      = limpiar($post['localidad']                ?? '');
$tipo_vivienda  = $post['tipo_vivienda']                    ?? '';
$vivienda_prop  = $post['vivienda_en_propiedad']            ?? '';
$personas       = (int)($post['personas_en_hogar']          ?? 0);
$ninos          = $post['ninos_en_hogar']                   ?? '';
$otros_animales = $post['otros_animales']                   ?? '';
$desc_otros     = limpiar($post['descripcion_otros_animales']?? '');
$exp_animales   = limpiar($post['experiencia_animales']     ?? '');
$tiempo_fuera   = limpiar($post['tiempo_fuera_casa']        ?? '');
$motivo         = limpiar($post['motivo_adopcion']          ?? '');
$compromiso     = (int)($post['compromiso_visitas']         ?? 0);
$acepta_politica= !empty($post['aceptar_politica_privacidad']) ? 1 : 0;
$mensaje        = limpiar($post['mensaje']                  ?? '');

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
if ($dni && !preg_match('/^[XYZ]?[0-9]{7,8}[A-Z]$/i', $dni)) {
    respuestaError('DNI no valido.');
}
if ($vivienda_prop === 'no' && empty($files['permiso_propietario']['name'])) {
    respuestaError('Si la vivienda no es en propiedad, debes subir el permiso del propietario (PDF).');
}
if (!$acepta_politica) {
    respuestaError('Debes aceptar la politica de privacidad.');
}
foreach ([$exp_animales, $motivo] as $texto) {
    if ($texto !== '' && strlen($texto) < 20) {
        respuestaError('Los campos de texto deben tener al menos 20 caracteres.');
    }
}

$pdo = conectar();

$stmt = $pdo->prepare(
    'SELECT idMascota, idProtectora FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND estado_adopcion = "disponible" LIMIT 1'
);
$stmt->execute([$idMascota]);
$mascota = $stmt->fetch();
if (!$mascota) {
    respuestaError('Esta mascota no esta disponible para adopcion.');
}

$stmt = $pdo->prepare(
    'SELECT idSolicitud FROM solicitudes_adopcion
     WHERE idMascota = ? AND email = ? AND estado IN ("pendiente","en_revision") LIMIT 1'
);
$stmt->execute([$idMascota, $email]);
if ($stmt->fetch()) {
    respuestaError('Ya tienes una solicitud activa para esta mascota.');
}

/* Subir permiso PDF si existe */
$permisoPath = null;
if (!empty($files['permiso_propietario']['name'])) {
    $file = $files['permiso_propietario'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf')               respuestaError('El permiso del propietario debe ser un archivo PDF.');
    if ($file['size'] > 5 * 1024 * 1024) respuestaError('El archivo PDF no debe superar los 5 MB.');
    $uploadDir = __DIR__ . '/../../uploads/permisos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $fileName = 'permiso_' . $idMascota . '_' . time() . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName))
        respuestaError('Error al subir el archivo.');
    $permisoPath = 'uploads/permisos/' . $fileName;
}

iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;
session_write_close();

$stmt = $pdo->prepare(
    'INSERT INTO solicitudes_adopcion
     (idUsuario, idMascota, nombre, dni, fecha_nacimiento, email, telefono,
      direccion_completa, localidad, tipo_vivienda, vivienda_en_propiedad,
      permiso_propietario, personas_en_hogar, ninos_en_hogar, otros_animales,
      descripcion_otros_animales, experiencia_animales, tiempo_fuera_casa,
      motivo_adopcion, compromiso_visitas, aceptar_politica_privacidad, mensaje)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idMascota,
    $nombre,
    $dni         ?: null,
    $fecha_nac   ?: null,
    $email,
    $telefono    ?: null,
    $direccion   ?: null,
    $localidad   ?: null,
    $tipo_vivienda ?: null,
    $vivienda_prop ?: null,
    $permisoPath,
    $personas    ?: null,
    $ninos       ?: null,
    $otros_animales ?: null,
    $desc_otros  ?: null,
    $exp_animales ?: null,
    $tiempo_fuera ?: null,
    $motivo      ?: null,
    $compromiso,
    $acepta_politica,
    $mensaje     ?: null,
]);

/* Notificación a la protectora si tiene usuario con idProtectora */
$idProtectora = (int)$mascota['idProtectora'];
if ($idProtectora) {
    $pdo->prepare(
        'INSERT INTO notificaciones (idProtectora, tipo, mensaje, ruta_destino)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $idProtectora,
        'solicitud_adopcion',
        $nombre . ' ha enviado una solicitud de adopcion',
        'admin/solicitudes.html',
    ]);
}

respuestaOk(['mensaje' => 'Solicitud enviada correctamente. La protectora se pondra en contacto contigo por email.']);