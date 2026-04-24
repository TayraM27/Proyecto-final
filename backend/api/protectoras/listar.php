<?php
/*--------------------------------------------------------------------------------------------
GET — lista protectoras activas*/

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

session_write_close();

$pdo  = conectar();
$stmt = $pdo->query(
    'SELECT idProtectora, nombre, localidad, telefono, email,
            web, tipo_pagina, iban, bizum, teaming,
            foto_logo, descripcion, verificada
     FROM protectoras
     WHERE activa = 1
     ORDER BY nombre ASC'
);

respuestaOk(['protectoras' => $stmt->fetchAll()]);