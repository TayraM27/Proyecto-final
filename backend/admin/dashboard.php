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
$stats['total_usuarios']        = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='usuario'")->fetchColumn();
$stats['solicitudes_pendientes']= (int)$pdo->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado='pendiente'")->fetchColumn();
$stats['apadrinamientos_activos']=(int)$pdo->query("SELECT COUNT(*) FROM apadrinamientos WHERE estado='activo'")->fetchColumn();
$stats['donaciones_completadas']= (int)$pdo->query("SELECT COUNT(*) FROM donaciones WHERE estado='completada'")->fetchColumn();
$stats['publicaciones_foro']    = (int)$pdo->query("SELECT COUNT(*) FROM publicaciones WHERE activa=1")->fetchColumn();

$ultSol = $pdo->query(
    "SELECT u.nombre, u.email, m.nombre AS mascota, s.fecha_envio
     FROM solicitudes_adopcion s
     JOIN usuarios u ON s.idUsuario = u.idUsuario
     JOIN mascotas m ON s.idMascota = m.idMascota
     WHERE s.estado='pendiente'
     ORDER BY s.fecha_envio DESC LIMIT 5"
)->fetchAll();

$ultMasc = $pdo->query(
    "SELECT m.nombre, m.especie, m.urgencia, p.nombre AS protectora_nombre
     FROM mascotas m
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE m.activa=1
     ORDER BY m.idMascota DESC LIMIT 5"
)->fetchAll();

respuestaOk([
    'stats'             => $stats,
    'ultimas_solicitudes' => $ultSol,
    'ultimas_mascotas'    => $ultMasc,
]);