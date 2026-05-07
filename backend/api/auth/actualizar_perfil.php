<?php
require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

requerirLogin();

if (!empty($_FILES)) {
    $datos   = $_POST;
    $hayFoto = isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK;
} else {
    $datos   = json_decode(file_get_contents('php://input'), true) ?? [];
    $hayFoto = false;
}

$nombre    = limpiar($datos['nombre']    ?? '');
$username  = limpiar($datos['username']  ?? '');
$localidad = limpiar($datos['localidad'] ?? '');
$telefono  = limpiar($datos['telefono']  ?? '');

if (!$nombre || strlen($nombre) < 2) {
    respuestaError('El nombre debe tener al menos 2 caracteres.');
}

if (!$username || strlen($username) < 3) {
    respuestaError('El nombre de usuario debe tener al menos 3 caracteres.');
}

if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
    respuestaError('El usuario solo puede contener letras, números, puntos y guiones bajos.');
}

if ($telefono !== '') {
    $telLimpio = preg_replace('/[\s\-\(\)\+]/', '', $telefono);
    if (!preg_match('/^\d{9}$/', $telLimpio)) {
        respuestaError('Teléfono no válido. Debe contener exactamente 9 dígitos.');
    }
    $telefono = $telLimpio;
}

$idUsuario = (int)$_SESSION['idUsuario'];
$pdo       = conectar();

$stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE username = ? AND idUsuario != ? LIMIT 1');
$stmt->execute([$username, $idUsuario]);
if ($stmt->fetch()) {
    respuestaError('Ese nombre de usuario ya está en uso.');
}

$foto = null;
if ($hayFoto) {
    $archivo   = $_FILES['foto_perfil'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($extension, $permitidos)) {
        respuestaError('Formato de imagen no permitido. Usa JPG, PNG o GIF.');
    }

    if ($archivo['size'] > 2 * 1024 * 1024) {
        respuestaError('La imagen no puede superar 2 MB.');
    }

    $directorio = __DIR__ . '/uploads/usuarios/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombreArchivo = uniqid('user_', true) . '.' . $extension;
    $rutaDestino   = $directorio . $nombreArchivo;

    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        $foto = 'uploads/usuarios/' . $nombreArchivo;

        if (!empty($_SESSION['foto_perfil']) && file_exists(__DIR__ . '/../../' . $_SESSION['foto_perfil'])) {
            @unlink(__DIR__ . '/../../' . $_SESSION['foto_perfil']);
        }

        $pdo->prepare(
            'UPDATE usuarios SET nombre = ?, username = ?, localidad = ?, telefono = ?, foto_perfil = ? WHERE idUsuario = ?'
        )->execute([$nombre, $username, $localidad ?: null, $telefono ?: null, $foto, $idUsuario]);

        $_SESSION['foto_perfil'] = $foto;
    } else {
        respuestaError('Error al subir la imagen.');
    }
} else {
    $pdo->prepare(
        'UPDATE usuarios SET nombre = ?, username = ?, localidad = ?, telefono = ? WHERE idUsuario = ?'
    )->execute([$nombre, $username, $localidad ?: null, $telefono ?: null, $idUsuario]);
}

$_SESSION['nombre']    = $nombre;
$_SESSION['username']  = $username;
$_SESSION['localidad'] = $localidad ?: null;
$_SESSION['telefono']  = $telefono ?: null;

respuestaOk([
    'mensaje' => 'Perfil actualizado correctamente.',
    'usuario' => [
        'nombre'      => $nombre,
        'username'    => $username,
        'localidad'   => $localidad ?: null,
        'telefono'    => $telefono ?: null,
        'foto_perfil' => $_SESSION['foto_perfil'] ?? null,
    ],
]);
