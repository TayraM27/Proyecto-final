<?php
/*--------------------------------------------------------------------------------------------
GET — lista protectoras activas
Parámetros:
  pagina      — número de página (default 1)
  limite      — resultados por página (default 9)
  especie     — perro | gato | todos
  teaming     — 1 para filtrar las que tienen teaming
  q           — búsqueda por nombre o localidad */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

session_write_close();

$pdo     = conectar();
$pagina  = max(1, (int)($_GET['pagina']  ?? 1));
$limite  = min(100, max(1, (int)($_GET['limite']  ?? 9)));
$offset   = ($pagina - 1) * $limite;
$especie = $_GET['especie'] ?? 'todos';
$teaming = (int)($_GET['teaming'] ?? 0);
$q       = trim($_GET['q']       ?? '');

$where  = ['activa = 1'];
$params = [];

if ($especie !== 'todos') {
    $where[]  = 'especie_atencion = ?';
    $params[] = $especie;
}
if ($teaming) {
    $where[] = 'teaming IS NOT NULL AND teaming != ""';
}
if ($q !== '') {
    $where[]  = '(nombre LIKE ? OR localidad LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$cond = implode(' AND ', $where);

/* Total */
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM protectoras WHERE $cond");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

/* Datos */
$sql = "SELECT idProtectora, nombre, localidad, telefono, email,
                web, tipo_pagina, red_social_url, especie_atencion,
                iban, bizum, teaming, badges,
                foto_logo, descripcion, descripcion_dona,
                url_formulario_acogida, verificada
         FROM protectoras
         WHERE $cond
         ORDER BY nombre ASC
         LIMIT ? OFFSET ?";
$params[] = $limite;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

respuestaOk([
    'protectoras'   => $stmt->fetchAll(),
    'total'        => $total,
    'pagina'       => $pagina,
    'totalPaginas' => (int)ceil($total / $limite),
]);