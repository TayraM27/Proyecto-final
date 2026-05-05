<?php
/*--------------------------------------------------------------------------------------------
POST — registra un apadrinamiento
No requiere login (visitantes tambien pueden apadrinar)
Recibe: { idMascota, cuota, nombre_pagador, email_pagador, telefono, mensaje } */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos        = json_decode(file_get_contents('php://input'), true) ?? [];
$idMascota    = (int)($datos['idMascota']     ?? 0);
$cuota        = trim($datos['cuota']          ?? '');
$nombrePagador= limpiar($datos['nombre_pagador'] ?? '');
$emailPagador = trim($datos['email_pagador']  ?? '');
$telefono     = limpiar($datos['telefono']    ?? '');
$mensaje      = limpiar($datos['mensaje']     ?? '');

if (!$idMascota || !$cuota || !$nombrePagador || !$emailPagador) {
    respuestaError('Mascota, cuota, nombre y email son obligatorios.');
}

// Cuotas validas segun el frontend
$cuotasValidas = ['3.00', '5.00', '8.00', '10.00'];
if (!in_array($cuota, $cuotasValidas)) {
    respuestaError('Cuota no valida. Elige entre 3, 5, 8 o 10 euros al mes.');
}

if (!validarEmail($emailPagador)) {
    respuestaError('Email no valido.');
}

if ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
    respuestaError('El telefono debe tener exactamente 9 digitos.');
}

$pdo = conectar();

// Comprobar que la mascota esta disponible para apadrinamiento
$stmt = $pdo->prepare(
    'SELECT idMascota FROM mascotas
     WHERE idMascota = ? AND activa = 1 AND disponible_apadrinamiento = 1
     LIMIT 1'
);
$stmt->execute([$idMascota]);
if (!$stmt->fetch()) {
    respuestaError('Esta mascota no esta disponible para apadrinamiento.');
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
$stmt->execute([$emailPagador, $idMascota]);
if ($stmt->fetch()) {
    respuestaError('Ya hay un apadrinamiento activo para esta mascota con ese email.');
}

$stmt = $pdo->prepare(
    'INSERT INTO apadrinamientos
        (idUsuario, idMascota, cuota, fecha_inicio, nombre_pagador, email_pagador, telefono, mensaje)
     VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idMascota,
    $cuota,
    $nombrePagador,
    $emailPagador,
    $telefono ?: null,
    $mensaje ?: null,
]);

respuestaOk([
    'mensaje'          => 'Apadrinamiento registrado correctamente.',
    'idApadrinamiento' => $pdo->lastInsertId(),
]);