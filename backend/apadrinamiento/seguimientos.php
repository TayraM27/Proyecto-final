<?php
/*--------------------------------------------------------------------------------------------
GET ?idMascota=1 — devuelve los seguimientos de una mascota apadrinada por el usuario
Solo puede ver sus propios apadrinamientos */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirLogin();

$idMascota = (int)($_GET['idMascota'] ?? 0);
$idUsuario = (int)$_SESSION['idUsuario'];

if (!$idMascota) {
    respuestaError('ID de mascota no valido.');
}

$pdo = conectar();

// Verificar que el usuario apadrina esta mascota
$stmt = $pdo->prepare(
    'SELECT idApadrinamiento FROM apadrinamientos
     WHERE idUsuario = ? AND idMascota = ? AND estado = "activo"
     LIMIT 1'
);
$stmt->execute([$idUsuario, $idMascota]);
$apadrinamiento = $stmt->fetch();

if (!$apadrinamiento) {
    respuestaError('No estas apadrinando a esta mascota.', 403);
}

$idApadrinamiento = $apadrinamiento['idApadrinamiento'];

$stmt = $pdo->prepare(
    'SELECT idSeguimiento, contenido, tipo_archivo, ruta_archivo, fecha
     FROM seguimientos
     WHERE idApadrinamiento = ?
     ORDER BY fecha DESC'
);
$stmt->execute([$idApadrinamiento]);

respuestaOk(['seguimientos' => $stmt->fetchAll()]);