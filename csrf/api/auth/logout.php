<?php
/*--------------------------------------------------------------------------------------------
Destruye la sesión del usuario */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
$_SESSION = [];
session_destroy();

respuestaOk(['mensaje' => 'Sesión cerrada.', 'redirigir' => 'index.html']);