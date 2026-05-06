<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesión.', 401);
}

$usuario = obtenerUsuarioSesion();
$idUsuario = $usuario['idUsuario'];
$idActualizacion = intval($_GET['idActualizacion'] ?? 0);

if (!$idActualizacion) {
    respuestaError('ID no proporcionado.', 400);
}

$pdo = conectar();

$stmt = $pdo->prepare('SELECT a.idActualizacion, a.mensaje, a.fotos, a.video_url, a.fecha, a.idAnimal, an.nombre as animalNombre, ap.leido
        FROM actualizaciones a
        INNER JOIN actualizacion_padrinos ap ON a.idActualizacion = ap.idActualizacion
        INNER JOIN animales an ON a.idAnimal = an.idAnimal
        WHERE a.idActualizacion = ? AND ap.idUsuario = ? AND a.activo = 1
        LIMIT 1');
$stmt->execute([$idActualizacion, $idUsuario]);
$actualizacion = $stmt->fetch();

if (!$actualizacion) {
    respuestaError('Actualización no encontrada.', 404);
}

if ($actualizacion['leido'] == 0) {
    $pdo->prepare('UPDATE actualizacion_padrinos SET leido = 1 WHERE idActualizacion = ? AND idUsuario = ?')->execute([$idActualizacion, $idUsuario]);
    $actualizacion['leido'] = 1;
}

$stmt = $pdo->prepare('SELECT r.idRespuesta, r.respuesta, r.fecha, u.nombre as usuarioNombre
        FROM respuestas_actualizacion r
        INNER JOIN usuarios u ON r.idUsuario = u.idUsuario
        WHERE r.idActualizacion = ? AND r.activo = 1
        ORDER BY r.fecha ASC');
$stmt->execute([$idActualizacion]);
$respuestas = $stmt->fetchAll();

respuestaOk(['actualizacion' => $actualizacion, 'respuestas' => $respuestas]);
