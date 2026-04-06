<?php
/*--------------------------------------------------------------------------------------------
devuelve si hay sesion activa y datos basicos del usuario
El frontend puede llamar a esto al cargar para mostrar/ocultar elementos */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();

if (!usuarioLogueado()) {
    respuestaOk(['logueado' => false]);
}

respuestaOk([
    'logueado' => true,
    'usuario'  => [
        'idUsuario'   => $_SESSION['idUsuario'],
        'nombre'      => $_SESSION['nombre'],
        'username'    => $_SESSION['username'],
        'rol'         => $_SESSION['rol'],
        'foto_perfil' => $_SESSION['foto_perfil'] ?? null,
    ],
]);