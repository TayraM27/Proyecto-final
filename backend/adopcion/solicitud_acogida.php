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
$nombre     = trim($post['nombre']                       ?? '');
$email      = trim($post['email']                           ?? '');
$telefono   = trim($post['telefono']                     ?? '');
$vivienda   = $post['tipo_vivienda']                        ?? '';   /* enum: piso|casa_con_jardin|casa_sin_jardin|finca */
$experiencia= trim($post['experiencia_acogida']          ?? '');
$tiempo     = $post['tiempo_fuera_casa']                    ?? '';   /* enum: menos_de_2h|2_a_4h|mas_de_4h|disponibilidad_completa */
$motivo     = trim($post['motivo_acogida']               ?? '');
$acepta_politica = !empty($post['aceptar_politica_privacidad']) ? 1 : 0;
$mensaje    = trim($post['mensaje']                      ?? '');
/* Extra fields from frontend (acoge.html / fichaAnimal.html) */
$dni                = trim($post['dni']                  ?? '');
$fecha_nacimiento   = trim($post['fecha_nacimiento']        ?? '');
$direccion_completa = trim($post['direccion_completa']   ?? '');
$vivienda_en_propiedad = $post['vivienda_en_propiedad']     ?? '';
$disponibilidad_tiempo  = trim($post['disponibilidad_tiempo'] ?? '');
$animales_en_hogar  = $post['animales_en_hogar']            ?? '';
$descripcion_animales   = trim($post['descripcion_animales'] ?? '');
$posibilidad_gastos = $post['posibilidad_gastos']           ?? '';

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

/* Manejar fichero permiso_propietario */
$permisoRuta = null;
if (!empty($files['permiso_propietario']) && $files['permiso_propietario']['error'] === UPLOAD_ERR_OK) {
    $dir = __DIR__ . '/../../uploads/acogida/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ext = strtolower(pathinfo($files['permiso_propietario']['name'], PATHINFO_EXTENSION));
    $nombreArchivo = uniqid('permiso_') . '.' . $ext;
    move_uploaded_file($files['permiso_propietario']['tmp_name'], $dir . $nombreArchivo);
    $permisoRuta = 'uploads/acogida/' . $nombreArchivo;
}

/* Componer texto completo de experiencia con todos los campos extra */
$bloques = [];
if ($experiencia) $bloques[] = 'Experiencia: ' . $experiencia;
if ($motivo) $bloques[] = 'Motivo: ' . $motivo;
if ($dni) $bloques[] = 'DNI: ' . $dni;
if ($fecha_nacimiento) $bloques[] = 'Fecha de nacimiento: ' . $fecha_nacimiento;
if ($direccion_completa) $bloques[] = 'Dirección: ' . $direccion_completa;
if ($vivienda_en_propiedad) $bloques[] = 'Vivienda en propiedad: ' . $vivienda_en_propiedad;
if ($disponibilidad_tiempo) $bloques[] = 'Disponibilidad de tiempo: ' . $disponibilidad_tiempo;
if ($animales_en_hogar) $bloques[] = 'Animales en hogar: ' . $animales_en_hogar;
if ($descripcion_animales) $bloques[] = 'Descripción animales: ' . $descripcion_animales;
if ($posibilidad_gastos) $bloques[] = 'Posibilidad gastos: ' . $posibilidad_gastos;
if ($permisoRuta) $bloques[] = 'Permiso propietario: ' . $permisoRuta;
$textoExperiencia = implode("\n\n", $bloques);

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