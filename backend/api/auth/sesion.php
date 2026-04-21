<?php
/*--------------------------------------------------------------------------------------------
Devuelve si hay sesión activa y datos básicos del usuario.
Timeout de inactividad: 30 minutos sin actividad = sesión destruida. */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

define('TIMEOUT_INACTIVIDAD', 1800);

iniciarSesionSegura();

if (!usuarioLogueado()) {
    session_write_close();
    respuestaOk(['logueado' => false]);
}

/*------- timeout de inactividad -------*/
$ahora = time();
if (isset($_SESSION['ultima_actividad'])) {
    if (($ahora - $_SESSION['ultima_actividad']) > TIMEOUT_INACTIVIDAD) {
        session_unset();
        session_destroy();
        respuestaOk(['logueado' => false]);
    }
}
$_SESSION['ultima_actividad'] = $ahora;

/*------- datos extendidos desde BD si faltan -------*/
if (!isset($_SESSION['email'])) {
    $pdo  = conectar();
    $stmt = $pdo->prepare('SELECT email, localidad, telefono FROM usuarios WHERE idUsuario = ? LIMIT 1');
    $stmt->execute([$_SESSION['idUsuario']]);
    $extra = $stmt->fetch();
    if ($extra) {
        $_SESSION['email']     = $extra['email'];
        $_SESSION['localidad'] = $extra['localidad'];
        $_SESSION['telefono']  = $extra['telefono'];
    }
}

/* Liberar el lock de sesión antes de responder para que otras
   peticiones PHP simultáneas no queden bloqueadas esperando */
session_write_close();

respuestaOk([
    'logueado' => true,
    'usuario'  => [
        'idUsuario'   => $_SESSION['idUsuario'],
        'nombre'      => $_SESSION['nombre'],
        'username'    => $_SESSION['username'],
        'rol'         => $_SESSION['rol'],
        'foto_perfil' => $_SESSION['foto_perfil'] ?? null,
        'email'       => $_SESSION['email']     ?? null,
        'localidad'   => $_SESSION['localidad'] ?? null,
        'telefono'    => $_SESSION['telefono']  ?? null,
    ],
]);