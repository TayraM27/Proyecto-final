<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!esProtectora()) {
    respuestaError('Acceso denegado.', 403);
}

$usuario = obtenerUsuarioSesion();
$idProtectora = $usuario['idProtectora'];

$pdo = conectar();

$idActualizacion = intval($_GET['idActualizacion'] ?? 0);

if ($idActualizacion) {
    $stmt = $pdo->prepare('SELECT a.idActualizacion, a.mensaje, a.fotos, a.video_url, a.fecha, a.idMascota, a.respondida_protectora, m.nombre as animalNombre
        FROM actualizaciones a
        INNER JOIN mascotas m ON a.idMascota = m.idMascota
        WHERE a.idActualizacion = ? AND a.idProtectora = ? AND a.activo = 1
        LIMIT 1');
    $stmt->execute([$idActualizacion, $idProtectora]);
    $actualizacion = $stmt->fetch();
    if (!$actualizacion) {
        respuestaError('Actualización no encontrada.', 404);
    }
    $stmt = $pdo->prepare('SELECT r.idRespuesta, r.respuesta, r.fecha, u.nombre as usuarioNombre, u.idUsuario
        FROM respuestas_actualizacion r
        INNER JOIN usuarios u ON r.idUsuario = u.idUsuario
        WHERE r.idActualizacion = ? AND r.activo = 1
        ORDER BY r.fecha ASC');
    $stmt->execute([$idActualizacion]);
    $respuestas = $stmt->fetchAll();
    respuestaOk(['actualizacion' => $actualizacion, 'respuestas' => $respuestas]);
} else {
    $stmt = $pdo->prepare('SELECT a.idActualizacion, a.mensaje, a.fecha, a.idMascota, m.nombre as animalNombre,
            (SELECT COUNT(*) FROM actualizacion_padrinos ap WHERE ap.idActualizacion = a.idActualizacion) as totalPadrinos,
            (SELECT COUNT(*) FROM actualizacion_padrinos ap WHERE ap.idActualizacion = a.idActualizacion AND ap.leido = 1) as leidos
        FROM actualizaciones a
        INNER JOIN mascotas m ON a.idMascota = m.idMascota
        WHERE a.idProtectora = ? AND a.activo = 1
        ORDER BY a.fecha DESC');
    $stmt->execute([$idProtectora]);
    $actualizaciones = $stmt->fetchAll();
    respuestaOk(['actualizaciones' => $actualizaciones]);
}
