<?php
/*--------------------------------------------------------------------------------------------
POST — registra un apadrinamiento
No requiere login (visitantes tambien pueden apadrinar)
Recibe: { idMascota, cantidad_mensual, nombre_completo, email, telefono, metodo_pago, mensaje } */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos          = json_decode(file_get_contents('php://input'), true) ?? [];
$idMascota      = (int)($datos['idMascota']        ?? 0);
$cantidadMensual= trim($datos['cantidad_mensual']   ?? '');
$nombreCompleto = limpiar($datos['nombre_completo']  ?? '');
$email          = trim($datos['email']              ?? '');
$telefono       = limpiar($datos['telefono']         ?? '');
$metodoPago     = limpiar($datos['metodo_pago']      ?? '');
$mensaje        = limpiar($datos['mensaje']          ?? '');

if (!$idMascota || !$cantidadMensual || !$nombreCompleto || !$email) {
    respuestaError('Mascota, cantidad, nombre y email son obligatorios.');
}

// Cantidades validas segun el frontend
$cantidadesValidas = ['3.00', '5.00', '8.00', '10.00'];
if (!in_array($cantidadMensual, $cantidadesValidas)) {
    respuestaError('Cantidad no valida. Elige entre 3, 5, 8 o 10 euros al mes.');
}

if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}

if ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
    respuestaError('El telefono debe tener exactamente 9 digitos.');
}

$metodosValidos = ['tarjeta', 'transferencia', 'bizum'];
if (!in_array($metodoPago, $metodosValidos)) {
    respuestaError('Selecciona un metodo de pago valido.');
}

$pdo = conectar();

// Comprobar que la mascota esta disponible para apadrinamiento
$stmt = $pdo->prepare(
    'SELECT idMascota, idProtectora FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND disponible_apadrinamiento = 1
     LIMIT 1'
);
$stmt->execute([$idMascota]);
$mascota = $stmt->fetch();
if (!$mascota) {
    respuestaError('Esta mascota no está disponible para apadrinamiento.');
}

/* Verificar protectora activa */
$stmtProt = $pdo->prepare('SELECT activa FROM protectoras WHERE idProtectora = ? LIMIT 1');
$stmtProt->execute([$mascota['idProtectora']]);
$prot = $stmtProt->fetch();
if (!$prot || !$prot['activa']) {
    respuestaError('Esta protectora está temporalmente suspendida. No se pueden registrar apadrinamientos en este momento.');
}

iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;

// Si esta logueado, evitar duplicados por usuario
if ($idUsuario) {
    $stmt = $pdo->prepare(
        'SELECT idApadrinamiento FROM apadrinamientos
         WHERE idUsuario = ? AND idMascota = ? AND estado = "activo"
         LIMIT 1'
    );
    $stmt->execute([$idUsuario, $idMascota]);
    if ($stmt->fetch()) {
        respuestaError('Ya estas apadrinando a esta mascota.');
    }
}

// Evitar duplicados por email
$stmt = $pdo->prepare(
    'SELECT idApadrinamiento FROM apadrinamientos
     WHERE email_pagador = ? AND idMascota = ? AND estado = "activo"
     LIMIT 1'
);
$stmt->execute([$email, $idMascota]);
if ($stmt->fetch()) {
    respuestaError('Ya hay un apadrinamiento activo para esta mascota con ese email.');
}

$stmt = $pdo->prepare(
    'INSERT INTO apadrinamientos
        (idUsuario, idMascota, cuota, metodo_pago, fecha_inicio, estado, nombre_pagador, email_pagador, telefono, mensaje)
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
    $mensaje ?: null,
]);

respuestaOk([
    'mensaje'          => 'Apadrinamiento registrado. La protectora se pondra en contacto contigo.',
    'idApadrinamiento' => $pdo->lastInsertId(),
]);