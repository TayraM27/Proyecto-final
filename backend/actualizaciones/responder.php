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
$rol = $usuario['rol'];

$datos = json_decode(file_get_contents('php://input'), true) ?? [];
$idActualizacion = intval($datos['idActualizacion'] ?? 0);
$respuesta = limpiar($datos['respuesta'] ?? '');

if (!$idActualizacion || !$respuesta) {
    respuestaError('Faltan datos.', 400);
}

$pdo = conectar();

$stmt = $pdo->prepare('SELECT a.idActualizacion, a.idProtectora, a.respondida_protectora FROM actualizaciones a WHERE a.idActualizacion = ? AND a.activo = 1 LIMIT 1');
$stmt->execute([$idActualizacion]);
$actualizacion = $stmt->fetch();

if (!$actualizacion) {
    respuestaError('Actualización no encontrada.', 404);
}

$esPadrinoDelAnimal = false;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM actualizacion_padrinos WHERE idActualizacion = ? AND idUsuario = ?');
$stmt->execute([$idActualizacion, $idUsuario]);
$esPadrinoDelAnimal = $stmt->fetchColumn() > 0;

$esProtectoraPropia = ($rol === 'protectora' && $actualizacion['idProtectora'] == ($usuario['idProtectora'] ?? 0));

if ($esPadrinoDelAnimal) {
    if ($actualizacion['respondida_protectora']) {
        respuestaError('El hilo está cerrado. La protectora ya respondió.', 403);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM respuestas_actualizacion WHERE idActualizacion = ? AND idUsuario = ? AND activo = 1');
    $stmt->execute([$idActualizacion, $idUsuario]);
    if ($stmt->fetchColumn() > 0) {
        respuestaError('Ya has respondido a esta actualización.', 403);
    }

    $stmt = $pdo->prepare('INSERT INTO respuestas_actualizacion (idActualizacion, idUsuario, respuesta) VALUES (?, ?, ?)');
    $stmt->execute([$idActualizacion, $idUsuario, $respuesta]);
    respuestaOk(['mensaje' => 'Respuesta enviada.']);
}

if ($esProtectoraPropia) {
    if ($actualizacion['respondida_protectora']) {
        respuestaError('Ya has respondido a esta actualización.', 403);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM respuestas_actualizacion WHERE idActualizacion = ? AND activo = 1');
    $stmt->execute([$idActualizacion]);
    $totalRespuestas = $stmt->fetchColumn();
    if ($totalRespuestas == 0) {
        respuestaError('No hay respuestas de padrinos a las que contestar.', 403);
    }

    $stmt = $pdo->prepare('INSERT INTO respuestas_actualizacion (idActualizacion, idUsuario, respuesta) VALUES (?, ?, ?)');
    $stmt->execute([$idActualizacion, $idUsuario, $respuesta]);

    $pdo->prepare('UPDATE actualizaciones SET respondida_protectora = 1 WHERE idActualizacion = ?')->execute([$idActualizacion]);

    $stmtM = $pdo->prepare('SELECT m.nombre FROM mascotas m JOIN actualizaciones a ON a.idMascota = m.idMascota WHERE a.idActualizacion = ?');
    $stmtM->execute([$idActualizacion]);
    $mascotaNombre = ($m = $stmtM->fetch()) ? $m['nombre'] : 'tu apadrinado';

    $stmtP = $pdo->prepare('SELECT idUsuario FROM actualizacion_padrinos WHERE idActualizacion = ?');
    $stmtP->execute([$idActualizacion]);
    foreach ($stmtP->fetchAll() as $padrino) {
        crearNotificacion($padrino['idUsuario'], 'respuesta', 'La protectora respondió a una actualización de ' . $mascotaNombre, 'perfil.html?tab=actualizaciones');
    }

    respuestaOk(['mensaje' => 'Respuesta enviada. Hilo cerrado.']);
}

respuestaError('No autorizado para responder.', 403);
