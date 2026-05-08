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

$pdo = conectar();

$idMascota = intval($_POST['idMascota'] ?? 0);
$mensaje = limpiar($_POST['mensaje'] ?? '');

if (!$idMascota || !$mensaje) {
    respuestaError('Faltan datos obligatorios.', 400);
}

$stmt = $pdo->prepare('SELECT idMascota FROM mascotas WHERE idMascota = ? AND idProtectora = ? LIMIT 1');
$stmt->execute([$idMascota, $idProtectora]);
if (!$stmt->fetch()) {
    respuestaError('Animal no encontrado o no autorizado.', 404);
}

$fotos = [];
if (!empty($_FILES['fotos']['name'][0])) {
    $directorio = __DIR__ . '/../uploads/actualizaciones/';
    if (!is_dir($directorio)) mkdir($directorio, 0755, true);
    foreach ($_FILES['fotos']['name'] as $key => $name) {
        if ($_FILES['fotos']['error'][$key] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $permitidos)) continue;
        if ($_FILES['fotos']['size'][$key] > 2 * 1024 * 1024) continue;
        $nombreArchivo = uniqid('act_', true) . '.' . $ext;
        move_uploaded_file($_FILES['fotos']['tmp_name'][$key], $directorio . $nombreArchivo);
        $fotos[] = 'uploads/actualizaciones/' . $nombreArchivo;
    }
}

$videoUrl = filter_var($_POST['video_url'] ?? '', FILTER_SANITIZE_URL) ?: null;

$stmt = $pdo->prepare('INSERT INTO actualizaciones (idMascota, idProtectora, mensaje, fotos, video_url) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$idMascota, $idProtectora, $mensaje, $fotos ? json_encode($fotos) : null, $videoUrl]);
$idActualizacion = $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT idUsuario FROM apadrinamientos WHERE idMascota = ? AND estado = "activo"');
$stmt->execute([$idMascota]);
$padrinos = $stmt->fetchAll();

$insertPadrino = $pdo->prepare('INSERT INTO actualizacion_padrinos (idActualizacion, idUsuario, leido) VALUES (?, ?, 0)');
foreach ($padrinos as $padrino) {
    $insertPadrino->execute([$idActualizacion, $padrino['idUsuario']]);
}

respuestaOk(['mensaje' => 'Actualización enviada.', 'idActualizacion' => $idActualizacion]);
