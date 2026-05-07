<?php
/*--------------------------------------------------------------------------------------------
Listado de protectoras. Accesible sin login para la web pública.
Si el usuario es protectora o admin, recibe datos adicionales. */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();

$pdo       = conectar();
$esAdmin   = esAdmin();
$esProt    = esProtectora();
$idProtUser = getIdProtectoraUsuario();

$q      = limpiar($_GET['q']      ?? '');
$nombre = limpiar($_GET['nombre'] ?? '');
$todos  = !empty($_GET['todos'])  ? 1 : 0;

$where  = $todos ? [] : ['p.activa = 1'];
$params = [];

if ($q) {
    $where[]  = 'p.localidad LIKE ?';
    $params[] = "%$q%";
}
if ($nombre) {
    $where[]  = 'p.nombre LIKE ?';
    $params[] = "%$nombre%";
}

$cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.idProtectora, p.nombre, p.descripcion, p.descripcion_dona,
               p.direccion, p.localidad, p.telefono, p.email,
               p.web, p.tipo_pagina, p.red_social_url, p.especie_atencion,
               p.iban, p.bizum, p.teaming, p.badges,
               p.foto_logo, p.latitud, p.longitud,
               p.verificada, p.activa, p.fecha_registro,
               COUNT(m.idMascota) AS num_animales
        FROM protectoras p
        LEFT JOIN mascotas m ON m.idProtectora = p.idProtectora AND m.activa = 1
        $cond
        GROUP BY p.idProtectora
        ORDER BY p.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$protectoras = $stmt->fetchAll();

if ($esAdmin || $esProt) {
    respuestaOk(['protectoras' => $protectoras]);
}

respuestaOk(['protectoras' => array_map(function($p) {
    unset($p['iban'], $p['bizum'], $p['teaming'], $p['email'], $p['verificada']);
    return $p;
}, $protectoras)]);