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
     WHERE idUsuario = ? AND idMascota = ?
     LIMIT 1'
);
$stmt->execute([$idUsuario, $idMascota]);
$apadrinamiento = $stmt->fetch();

if (!$apadrinamiento) {
    respuestaError('No estas apadrinando a esta mascota.', 403);
}

$idApadrinamiento = $apadrinamiento['idApadrinamiento'];

$stmt = $pdo->prepare(
    'SELECT s.idSeguimiento, s.contenido, s.tipo_archivo, s.ruta_archivo, s.fecha,
            m.nombre AS mascota_nombre
     FROM seguimientos s
     JOIN apadrinamientos a ON s.idApadrinamiento = a.idApadrinamiento
     JOIN mascotas m ON a.idMascota = m.idMascota
     WHERE s.idApadrinamiento = ?
     ORDER BY s.fecha DESC'
);
$stmt->execute([$idApadrinamiento]);

$seguimientos = $stmt->fetchAll();
$mascotaNombre = $seguimientos[0]['mascota_nombre'] ?? '';

respuestaOk(['seguimientos' => $seguimientos, 'mascota_nombre' => $mascotaNombre]);