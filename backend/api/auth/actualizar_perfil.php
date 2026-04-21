<?php
/*--------------------------------------------------------------------------------------------
POST — el usuario actualiza sus propios datos de perfil
Recibe: { nombre, localidad, telefono }
Requiere login */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

requerirLogin();

$datos    = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre   = limpiar($datos['nombre']    ?? '');
$localidad= limpiar($datos['localidad'] ?? '');
$telefono = limpiar($datos['telefono']  ?? '');

if (!$nombre || strlen($nombre) < 2) {
    respuestaError('El nombre debe tener al menos 2 caracteres.');
}

if ($telefono && !preg_match('/^[6-9]\d{8}$/', preg_replace('/\s/', '', $telefono))) {
    respuestaError('Teléfono no válido.');
}

$idUsuario = (int)$_SESSION['idUsuario'];
$pdo       = conectar();

$pdo->prepare(
    'UPDATE usuarios SET nombre = ?, localidad = ?, telefono = ? WHERE idUsuario = ?'
)->execute([$nombre, $localidad ?: null, $telefono ?: null, $idUsuario]);

$_SESSION['nombre'] = $nombre;

respuestaOk(['mensaje' => 'Perfil actualizado correctamente.']);