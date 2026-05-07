<?php

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdmin();
session_write_close();

$pdo = conectar();

function safeCount($pdo, $sql, $default = 0) {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        return $default;
    }
}

$stats = [];
$stats['mascotas_disponibles']   = safeCount($pdo, "SELECT COUNT(*) FROM mascotas WHERE activa=1 AND estado_adopcion='disponible'");
$stats['total_protectoras']      = safeCount($pdo, "SELECT COUNT(*) FROM protectoras WHERE activa=1");
$stats['total_usuarios']         = safeCount($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol='usuario'");
$stats['apadrinamientos_activos']= safeCount($pdo, "SELECT COUNT(*) FROM apadrinamientos WHERE estado='aceptada' AND deleted=0");
$stats['donaciones_completadas'] = safeCount($pdo, "SELECT COUNT(*) FROM donaciones WHERE estado='completada'");
$stats['publicaciones_foro']     = safeCount($pdo, "SELECT COUNT(*) FROM publicaciones WHERE activa=1");

try {
    $ultMasc = $pdo->query(
        "SELECT m.nombre, m.especie, m.urgencia, p.nombre AS protectora_nombre
         FROM mascotas m
         JOIN protectoras p ON m.idProtectora = p.idProtectora
         WHERE m.activa=1
         ORDER BY m.idMascota DESC LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    $ultMasc = [];
}

try {
    $ultApad = $pdo->query(
        "SELECT m.nombre AS mascota, u.nombre AS padrino, a.cuota
         FROM apadrinamientos a
         JOIN mascotas m ON a.idMascota = m.idMascota
         JOIN usuarios u ON a.idUsuario = u.idUsuario
         WHERE a.estado = 'aceptada' AND a.deleted = 0
         ORDER BY a.idApadrinamiento DESC LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    $ultApad = [];
}

respuestaOk([
    'stats'                   => $stats,
    'ultimas_mascotas'        => $ultMasc,
    'ultimas_apadrinamientos' => $ultApad,
]);