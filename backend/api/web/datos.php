<?php
/*--------------------------------------------------------------------------------------------
GET — datos dinámicos para la web pública */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

$eventos = $pdo->query(
    "SELECT * FROM eventos WHERE activa=1 ORDER BY fecha_evento ASC LIMIT 10"
)->fetchAll();

$entidades = $pdo->query(
    "SELECT * FROM entidades_colaboradoras WHERE activa=1 ORDER BY nombre ASC"
)->fetchAll();

respuestaOk([
    'eventos' => $eventos,
    'entidades' => $entidades
]);