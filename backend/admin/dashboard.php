<?php
/*--------------------------------------------------------------------------------------------
GET — estadísticas para el dashboard de admin */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdmin();
session_write_close();

$pdo = conectar();

$stats = [];
$stats['mascotas_disponibles']  = (int)$pdo->query("SELECT COUNT(*) FROM mascotas WHERE activa=1 AND estado_adopcion='disponible'")->fetchColumn();
$stats['total_protectoras']     = (int)$pdo->query("SELECT COUNT(*) FROM protectoras WHERE activa=1")->fetchColumn();
$stats['total_usuarios']        = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='usuario'")->fetchColumn();
$stats['apadrinamientos_activos']=(int)$pdo->query("SELECT COUNT(*) FROM apadrinamientos WHERE estado='activo'")->fetchColumn();
$stats['donaciones_completadas']= (int)$pdo->query("SELECT COUNT(*) FROM donaciones WHERE estado='completada'")->fetchColumn();
$stats['publicaciones_foro']    = (int)$pdo->query("SELECT COUNT(*) FROM publicaciones WHERE activa=1")->fetchColumn();


$ultMasc = $pdo->query(
    "SELECT m.nombre, m.especie, m.urgencia, p.nombre AS protectora_nombre
     FROM mascotas m
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE m.activa=1
     ORDER BY m.idMascota DESC LIMIT 5"
)->fetchAll();

$ultApad = $pdo->query(
    "SELECT m.nombre AS mascota, u.nombre AS padrino, a.cuota
     FROM apadrinamientos a
     JOIN mascotas m ON a.idMascota = m.idMascota
     JOIN usuarios u ON a.idUsuario = u.idUsuario
     WHERE a.estado = 'activo'
     ORDER BY a.idApadrinamiento DESC LIMIT 5"
)->fetchAll();

respuestaOk([
    'stats'                   => $stats,
    'ultimas_mascotas'        => $ultMasc,
    'ultimas_apadrinamientos' => $ultApad,
]);