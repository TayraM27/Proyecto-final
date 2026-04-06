<?php
/*--------------------------------------------------------------------------------------------
destruye la sesion del usuario */

require_once __DIR__ . '/../../includes/funciones.php';

iniciarSesionSegura();
$_SESSION = [];
session_destroy();

header('Content-Type: application/json; charset=utf-8');
respuestaOk(['mensaje' => 'Sesion cerrada.', 'redirigir' => 'index.html']);