<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

if (!esProtectora()) {
    respuestaError('Acceso denegado.', 403);
}

$usuario = obtenerUsuarioSesion();
$idProtectora = $usuario['idProtectora'];

$datos = json_decode(file_get_contents('php://input'), true) ?? [];
$idActualizacion = intval($datos['idActualizacion'] ?? 0);

if (!$idActualizacion) {
    respuestaError('ID no proporcionado.', 400);
}

$pdo = conectar();

$stmt = $pdo->prepare('SELECT idActualizacion FROM actualizaciones WHERE idActualizacion = ? AND idProtectora = ? AND activo = 1 LIMIT 1');
$stmt->execute([$idActualizacion, $idProtectora]);
if (!$stmt->fetch()) {
    respuestaError('Actualización no encontrada o no autorizada.', 404);
}

$pdo->prepare('UPDATE actualizaciones SET activo = 0 WHERE idActualizacion = ?')->execute([$idActualizacion]);

respuestaOk(['mensaje' => 'Actualización eliminada.']);
