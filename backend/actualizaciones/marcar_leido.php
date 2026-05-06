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

if (!$idActualizacion) {
    respuestaError('ID no proporcionado.', 400);
}

$pdo = conectar();

$stmt = $pdo->prepare('UPDATE actualizacion_padrinos SET leido = 1 WHERE idActualizacion = ? AND idUsuario = ?');
$stmt->execute([$idActualizacion, $idUsuario]);

respuestaOk(['mensaje' => 'Marcado como leído.']);
