<?php
/*--------------------------------------------------------------------------------------------
Autenticación de usuarios
Recibe: POST { email, password, rol }
Devuelve: JSON con datos del usuario o error */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos) {
    respuestaError('Datos inválidos.');
}

$email    = limpiar($datos['email']    ?? '');
$password = trim($datos['password']   ?? '');
$rol      = limpiar($datos['rol']     ?? 'usuario');

if (!$email || !$password) {
    respuestaError('Email y contraseña son obligatorios.');
}

if (!validarEmail($email)) {
    respuestaError('Email no válido.');
}

$pdo  = conectar();
$stmt = $pdo->prepare(
    'SELECT idUsuario, nombre, username, email, password_hash, rol, foto_perfil, activo
     FROM usuarios
     WHERE email = ?
     LIMIT 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    respuestaError('Email o contraseña incorrectos.');
}

if (!$usuario['activo']) {
    respuestaError('Tu cuenta está desactivada. Contacta con el administrador.');
}

if ($rol === 'admin' && $usuario['rol'] !== 'admin') {
    respuestaError('No tienes permisos de administrador.');
}

$pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE idUsuario = ?')
    ->execute([$usuario['idUsuario']]);

iniciarSesionSegura();
session_regenerate_id(true);
$_SESSION['idUsuario']   = $usuario['idUsuario'];
$_SESSION['nombre']      = $usuario['nombre'];
$_SESSION['username']    = $usuario['username'];
$_SESSION['rol']         = $usuario['rol'];
$_SESSION['foto_perfil'] = $usuario['foto_perfil'];

respuestaOk([
    'usuario' => [
        'idUsuario'   => $usuario['idUsuario'],
        'nombre'      => $usuario['nombre'],
        'username'    => $usuario['username'],
        'rol'         => $usuario['rol'],
        'foto_perfil' => $usuario['foto_perfil'],
    ],
    'redirigir' => $usuario['rol'] === 'admin' ? '../admin/dashboard.html' : '../html/index.html',
]);