<?php
/*--------------------------------------------------------------------------------------------
POST — registro publico de protectora
Recibe: POST multipart/form-data o JSON
Campos: nombre, email, password, telefono, localidad, descripcion, web
Devuelve: JSON ok con usuario protectora creado */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Metodo no permitido.', 405);
}

if (!empty($_POST)) {
    $datos = $_POST;
} else {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
}

$nombre     = trim($datos['nombre'] ?? '');
$email      = trim($datos['email'] ?? '');
$password   = $datos['password'] ?? '';
$confirm    = $datos['password_confirm'] ?? '';
$telefono   = trim($datos['telefono'] ?? '');
$localidad  = trim($datos['localidad'] ?? '');
$descripcion= trim($datos['descripcion'] ?? '');
$web        = trim($datos['web'] ?? '');
$terminos   = $datos['terminos'] ?? '';

if (!$nombre || !$email || !$password || !$localidad) {
    respuestaError('Faltan campos obligatorios: nombre, email, password y localidad.');
}

if (strlen($nombre) < 2) {
    respuestaError('El nombre de la protectora debe tener al menos 2 caracteres.');
}

if (!validarEmail($email)) {
    respuestaError('Email no valido.');
}

if (!validarPassword($password)) {
    respuestaError('La contrasena debe tener al menos 8 caracteres.');
}

if ($password !== $confirm) {
    respuestaError('Las contrasenas no coinciden.');
}

if (!$terminos) {
    respuestaError('Debes aceptar los terminos y condiciones.');
}

if ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
    respuestaError('El telefono debe tener exactamente 9 digitos.');
}

$pdo = conectar();

$stmt = $pdo->prepare('SELECT idProtectora FROM protectoras WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    respuestaError('Este email ya esta registrado como protectora.');
}

$stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE email = ? AND rol = "protectora" LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    respuestaError('Ya existe una cuenta asociada a este email.');
}

$rutaLogo = null;
if (!empty($_FILES['foto_logo']) && $_FILES['foto_logo']['error'] === UPLOAD_ERR_OK) {
    $archivo    = $_FILES['foto_logo'];
    $extension  = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $permitidos)) {
        respuestaError('Formato de imagen no permitido. Usa JPG, PNG, GIF o WebP.');
    }

    if ($archivo['size'] > 3 * 1024 * 1024) {
        respuestaError('La imagen no puede superar 3 MB.');
    }

    $directorio = __DIR__ . '/../../../img/protectoras/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombreArchivo = 'prot_' . uniqid() . '.' . $extension;
    move_uploaded_file($archivo['tmp_name'], $directorio . $nombreArchivo);
    $rutaLogo = 'img/protectoras/' . $nombreArchivo;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        'INSERT INTO protectoras (nombre, email, telefono, localidad, descripcion, web, foto_logo, activa, verificada)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)'
    );
    $stmt->execute([
        $nombre,
        $email,
        $telefono ?: null,
        $localidad,
        $descripcion,
        $web ?: null,
        $rutaLogo,
    ]);
    $idProtectora = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash, rol, idProtectora)
         VALUES (?, ?, ?, "protectora", ?)'
    );
    $stmt->execute([$nombre, $email, $hash, $idProtectora]);
    $idUsuario = (int)$pdo->lastInsertId();

    $pdo->commit();

    iniciarSesionSegura();
    session_regenerate_id(true);
    $_SESSION['idUsuario']    = $idUsuario;
    $_SESSION['nombre']       = $nombre;
    $_SESSION['rol']          = 'protectora';
    $_SESSION['idProtectora'] = $idProtectora;

    respuestaOk([
        'mensaje'   => 'Protectora registrada correctamente. Tu cuenta sera revisada por un administrador antes de activarse.',
        'idUsuario' => $idUsuario,
        'idProtectora' => $idProtectora,
        'redirigir' => 'admin/mi-protectora.html',
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    respuestaError('Error al registrar la protectora. Intentalo de nuevo.');
}
