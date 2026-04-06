<?php
/*--------------------------------------------------------------------------------------------
api/donaciones/donar.php
POST — registra una donacion puntual
No requiere login (dona.html no pide cuenta)
Recibe: { idProtectora, nombre_donante, email_donante, importe, mensaje } */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

$datos       = json_decode(file_get_contents('php://input'), true) ?? [];
$idProtectora= (int)($datos['idProtectora']  ?? 0);
$nombre      = limpiar($datos['nombre_donante']?? '');
$email       = trim($datos['email_donante']   ?? '');
$importe     = (float)($datos['importe']      ?? 0);
$mensajeTxt  = limpiar($datos['mensaje']      ?? '');

if (!$idProtectora) {
    respuestaError('Debes seleccionar una protectora.');
}

if ($importe < 1) {
    respuestaError('El importe minimo es de 1 euro.');
}

if ($email && !validarEmail($email)) {
    respuestaError('Email no valido.');
}

$pdo = conectar();

// Comprobar que la protectora existe
$stmt = $pdo->prepare('SELECT idProtectora FROM protectoras WHERE idProtectora = ? AND activa = 1 LIMIT 1');
$stmt->execute([$idProtectora]);
if (!$stmt->fetch()) {
    respuestaError('Protectora no encontrada.');
}

// Obtener usuario si esta logueado
iniciarSesionSegura();
$idUsuario = usuarioLogueado() ? (int)$_SESSION['idUsuario'] : null;

$stmt = $pdo->prepare(
    'INSERT INTO donaciones (idUsuario, idProtectora, nombre_donante, email_donante, importe, mensaje)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $idUsuario,
    $idProtectora,
    $nombre  ?: null,
    $email   ?: null,
    $importe,
    $mensajeTxt ?: null,
]);

respuestaOk(['mensaje' => 'Donacion registrada. Gracias por tu apoyo.']);