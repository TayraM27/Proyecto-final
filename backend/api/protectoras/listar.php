<?php
/*--------------------------------------------------------------------------------------------
GET — lista protectoras activas
Ruta: backend/api/protectoras/listar.php */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

session_write_close();

$pdo  = conectar();
$stmt = $pdo->query(
    'SELECT idProtectora, nombre, localidad, telefono, email, web,
            foto_logo, descripcion, verificada
     FROM protectoras
     WHERE activa = 1
     ORDER BY nombre ASC'
);

respuestaOk(['protectoras' => $stmt->fetchAll()]);