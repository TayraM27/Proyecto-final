<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesión.', 401);
}

$usuario = obtenerUsuarioSesion();
$idUsuario = $usuario['idUsuario'];

$datos = json_decode(file_get_contents('php://input'), true) ?? [];
$idActualizacion = intval($datos['idActualizacion'] ?? 0);
$respuesta = limpiar($datos['respuesta'] ?? '');

if (!$idActualizacion || !$respuesta) {
    respuestaError('Faltan datos.', 400);
}

$pdo = conectar();

/* Verificar que el usuario sea padrino de esa actualización */
$stmt = $pdo->prepare('SELECT ap.idUsuario FROM actualizaciones a INNER JOIN actualizacion_padrinos ap ON a.idActualizacion = ap.idActualizacion WHERE a.idActualizacion = ? AND ap.idUsuario = ? AND a.activo = 1 LIMIT 1');
$stmt->execute([$idActualizacion, $idUsuario]);
if (!$stmt->fetch()) {
    respuestaError('No autorizado para responder.', 403);
}

$stmt = $pdo->prepare('INSERT INTO respuestas_actualizacion (idActualizacion, idUsuario, respuesta) VALUES (?, ?, ?)');
$stmt->execute([$idActualizacion, $idUsuario, $respuesta]);

respuestaOk(['mensaje' => 'Respuesta enviada.']);
