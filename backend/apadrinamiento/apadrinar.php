<?php
/*--------------------------------------------------------------------------------------------
POST — registra un apadrinamiento
Requiere login (apadrina.html lo indica con aviso)
Recibe: { idMascota, cuota, nombre_pagador, email_pagador } */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

requerirLogin();

$datos        = json_decode(file_get_contents('php://input'), true) ?? [];
$idMascota    = (int)($datos['idMascota']     ?? 0);
$cuota        = trim($datos['cuota']          ?? '');
$nombrePagador= limpiar($datos['nombre_pagador'] ?? '');
$emailPagador = trim($datos['email_pagador']  ?? '');

if (!$idMascota || !$cuota) {
    respuestaError('Mascota y cuota son obligatorios.');
}

// Cuotas validas segun el frontend
$cuotasValidas = ['3.00', '5.00', '8.00', '10.00'];
if (!in_array($cuota, $cuotasValidas)) {
    respuestaError('Cuota no valida. Elige entre 3, 5, 8 o 10 euros al mes.');
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

$idUsuario = (int)$_SESSION['idUsuario'];

// Un usuario no puede apadrinar la misma mascota dos veces (activo)
$stmt = $pdo->prepare(
    'SELECT idApadrinamiento FROM apadrinamientos
     WHERE idUsuario = ? AND idMascota = ? AND estado = "activo"
     LIMIT 1'
);
$stmt->execute([$idUsuario, $idMascota]);
if ($stmt->fetch()) {
    respuestaError('Ya estas apadrinando a esta mascota.');
}

$stmt = $pdo->prepare(
    'INSERT INTO apadrinamientos
        (idUsuario, idMascota, cuota, fecha_inicio, nombre_pagador, email_pagador)
     VALUES (?, ?, ?, CURDATE(), ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idMascota,
    $cuota,
    $nombrePagador ?: null,
    $emailPagador  ?: null,
]);

respuestaOk([
    'mensaje'          => 'Apadrinamiento registrado correctamente.',
    'idApadrinamiento' => $pdo->lastInsertId(),
]);