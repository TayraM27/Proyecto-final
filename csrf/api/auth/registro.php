<?php
/*--------------------------------------------------------------------------------------------

Recibe: POST multipart/form-data o JSON
Campos: nombre, username, email, localidad, telefono, password, password_confirm, terminos
Devuelve: JSON ok o error */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

/* Puede venir como form-data (con foto) o JSON */
if (!empty($_POST)) {
    $datos = $_POST;
} else {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
}

$nombre   = limpiar($datos['nombre'] ?? '');
$username = limpiar($datos['username'] ?? '');
$email    = limpiar($datos['email'] ?? '');
$localidad= limpiar($datos['localidad'] ?? '');
$telefono = limpiar($datos['telefono'] ?? '');
$password = $datos['password'] ?? '';
$confirm  = $datos['password_confirm'] ?? '';
$terminos = $datos['terminos'] ?? '';

/*--------------------------------------------------------------------------------------------
validaciones */

if (!$nombre || !$username || !$email || !$localidad || !$password) {
    respuestaError('Faltan campos obligatorios.');
}

if (strlen($nombre) < 2) {
    respuestaError('El nombre debe tener al menos 2 caracteres.');
}

if (strlen($username) < 3 || preg_match('/\s/', $username)) {
    respuestaError('El nombre de usuario debe tener al menos 3 caracteres y sin espacios.');
}

if (!validarEmail($email)) {
    respuestaError('Email no válido.');
}

if (!validarPassword($password)) {
    respuestaError('La contraseña debe tener al menos 8 caracteres.');
}

if ($password !== $confirm) {
    respuestaError('Las contraseñas no coinciden.');
}

if (!$terminos) {
    respuestaError('Debes aceptar los términos y condiciones.');
}

if ($telefono && !preg_match('/^[6-9]\d{8}$/', preg_replace('/\s/', '', $telefono))) {
    respuestaError('Teléfono no válido.');
}

$pdo = conectar();

/*--------------------------------------------------------------------------------------------
verificar unicidad */

/* Comprobar email único */
$stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    respuestaError('Este email ya está registrado.');
}

/* Comprobar username único */
$stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    respuestaError('Este nombre de usuario ya está en uso.');
}

/*--------------------------------------------------------------------------------------------
subida de foto */

$rutaFoto = null;
if (!empty($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    $archivo    = $_FILES['foto_perfil'];
    $extension  = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
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
    move_uploaded_file($archivo['tmp_name'], $directorio . $nombreArchivo);
    $rutaFoto = 'uploads/usuarios/' . $nombreArchivo;
}

/*--------------------------------------------------------------------------------------------
insertar usuario */

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nombre, username, email, password_hash, localidad, telefono, foto_perfil)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$nombre, $username, $email, $hash, $localidad, $telefono ?: null, $rutaFoto]);

$idNuevo = $pdo->lastInsertId();

/* Iniciar sesión */
iniciarSesionSegura();
session_regenerate_id(true);
$_SESSION['idUsuario']   = $idNuevo;
$_SESSION['nombre']      = $nombre;
$_SESSION['username']    = $username;
$_SESSION['rol']         = 'usuario';
$_SESSION['foto_perfil'] = $rutaFoto;

respuestaOk([
    'mensaje'  => 'Cuenta creada correctamente.',
    'usuario'  => [
        'idUsuario' => $idNuevo,
        'nombre'    => $nombre,
        'username'  => $username,
        'rol'       => 'usuario',
    ],
    'redirigir' => 'index.html',
]);