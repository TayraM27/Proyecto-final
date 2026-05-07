<?php
/*--------------------------------------------------------------------------------------------
backend/api/protectoras/listar.php
Endpoint público — devuelve protectoras activas paginadas para protectoras.html */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo       = conectar();
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 9;
$offset    = ($pagina - 1) * $porPagina;

$stmtTotal = $pdo->query('SELECT COUNT(*) FROM protectoras WHERE activa = 1');
$total     = (int)$stmtTotal->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT idProtectora, nombre, descripcion, descripcion_dona, localidad, telefono,
            email, web, tipo_pagina, iban, bizum, teaming, red_social_url,
            foto_logo, verificada, especie_atencion, badges
     FROM protectoras
     WHERE activa = 1
     ORDER BY nombre ASC
     LIMIT ? OFFSET ?'
);
$stmt->execute([$porPagina, $offset]);
$protectoras = $stmt->fetchAll();

respuestaOk([
    'protectoras'  => $protectoras,
    'total'        => $total,
    'pagina'       => $pagina,
    'totalPaginas' => (int)ceil($total / $porPagina),
]);