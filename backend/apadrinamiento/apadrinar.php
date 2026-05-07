<?php
/*--------------------------------------------------------------------------------------------
POST — registra un apadrinamiento */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos          = json_decode(file_get_contents('php://input'), true) ?? [];
$idMascota      = (int)($datos['idMascota']         ?? 0);
$cantidadMensual= trim($datos['cantidad_mensual']    ?? '');
$nombreCompleto = limpiar($datos['nombre_completo']  ?? '');
$email          = trim($datos['email']               ?? '');
$telefono       = limpiar($datos['telefono']         ?? '');
$metodoPago     = limpiar($datos['metodo_pago']      ?? '');
$mensaje        = limpiar($datos['mensaje']          ?? '');

if (!$idMascota || !$cantidadMensual || !$nombreCompleto || !$email) {
    respuestaError('Mascota, cantidad, nombre y email son obligatorios.');
}

$cantidadesValidas = ['3.00', '5.00', '8.00', '10.00'];
if (!in_array($cantidadMensual, $cantidadesValidas)) {
    respuestaError('Cantidad no valida. Elige entre 3, 5, 8 o 10 euros al mes.');
}

if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}

/* Teléfono opcional — solo validar si se proporciona */
if ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
    respuestaError('El telefono debe tener exactamente 9 digitos.');
}

$metodosValidos = ['tarjeta_simulada', 'transferencia_simulada', 'bizum_simulado'];
if (!in_array($metodoPago, $metodosValidos)) {
    respuestaError('Selecciona un metodo de pago valido.');
}

$pdo = conectar();

$stmt = $pdo->prepare(
    'SELECT idMascota, idProtectora FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND disponible_apadrinamiento = 1 LIMIT 1'
);
$stmt->execute([$idMascota]);
$mascota = $stmt->fetch();
if (!$mascota) {
    respuestaError('Esta mascota no esta disponible para apadrinamiento.');
}

iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;
session_write_close();

if ($idUsuario) {
    $stmt = $pdo->prepare(
        'SELECT idApadrinamiento FROM apadrinamientos
         WHERE idUsuario = ? AND idMascota = ? AND estado = "activo" LIMIT 1'
    );
    $stmt->execute([$idUsuario, $idMascota]);
    if ($stmt->fetch()) {
        respuestaError('Ya estas apadrinando a esta mascota.');
    }
}

$stmt = $pdo->prepare(
    'SELECT idApadrinamiento FROM apadrinamientos
     WHERE email = ? AND idMascota = ? AND estado = "activo" LIMIT 1'
);
$stmt->execute([$email, $idMascota]);
if ($stmt->fetch()) {
    respuestaError('Ya hay un apadrinamiento activo para esta mascota con ese email.');
}

$stmt = $pdo->prepare(
    'INSERT INTO apadrinamientos
     (idUsuario, idMascota, cantidad_mensual, metodo_pago, fecha_inicio, estado, nombre_completo, email, telefono, mensaje)
     VALUES (?, ?, ?, ?, CURDATE(), "activo", ?, ?, ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idMascota,
    $cantidadMensual,
    $metodoPago,
    $nombreCompleto,
    $email,
    $telefono ?: null,
    $mensaje  ?: null,
]);

$idApadrinamiento = (int)$pdo->lastInsertId();

/* Notificación a la protectora */
$idProtectora = (int)$mascota['idProtectora'];
if ($idProtectora) {
    $pdo->prepare(
        'INSERT INTO notificaciones (idProtectora, tipo, mensaje, ruta_destino)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $idProtectora,
        'solicitud_apadrinamiento',
        $nombreCompleto . ' ha iniciado un apadrinamiento',
        'admin/mi-protectora.html',
    ]);
}

/* Notificación al usuario si está logueado */
if ($idUsuario) {
    $pdo->prepare(
        'INSERT INTO notificaciones (idUsuario, tipo, mensaje, ruta_destino)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $idUsuario,
        'solicitud_apadrinamiento',
        'Tu apadrinamiento ha sido registrado correctamente.',
        'perfil.html?tab=apadrinamientos',
    ]);
}

respuestaOk([
    'mensaje'          => 'Apadrinamiento registrado correctamente (simulacion).',
    'idApadrinamiento' => $idApadrinamiento,
]);