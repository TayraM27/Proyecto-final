<?php
/*--------------------------------------------------------------------------------------------
admin/dashboard.php
Panel principal del administrador
Muestra resumen: mascotas, usuarios, solicitudes pendientes, donaciones */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

$pdo = conectar();

// Estadisticas generales
$stats = [];

$consultas = [
    'total_mascotas'      => "SELECT COUNT(*) FROM mascotas WHERE activa = 1",
    'mascotas_disponibles'=> "SELECT COUNT(*) FROM mascotas WHERE activa = 1 AND estado_adopcion = 'disponible'",
    'total_usuarios'      => "SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario' AND activo = 1",
    'solicitudes_pendientes'=> "SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'pendiente'",
    'apadrinamientos_activos'=> "SELECT COUNT(*) FROM apadrinamientos WHERE estado = 'activo'",
    'donaciones_completadas'=> "SELECT COUNT(*) FROM donaciones WHERE estado = 'completada'",
    'total_donado'        => "SELECT COALESCE(SUM(importe), 0) FROM donaciones WHERE estado = 'completada'",
    'publicaciones_foro'  => "SELECT COUNT(*) FROM publicaciones WHERE activa = 1",
];

foreach ($consultas as $clave => $sql) {
    $stats[$clave] = $pdo->query($sql)->fetchColumn();
}

// Ultimas solicitudes pendientes (5)
$ultimasSolicitudes = $pdo->query(
    "SELECT s.idSolicitud, s.nombre, s.email, s.fecha_envio,
            m.nombre AS mascota
     FROM solicitudes_adopcion s
     JOIN mascotas m ON s.idMascota = m.idMascota
     WHERE s.estado = 'pendiente'
     ORDER BY s.fecha_envio DESC
     LIMIT 5"
)->fetchAll();

// Ultimas mascotas añadidas (5)
$ultimasMascotas = $pdo->query(
    "SELECT m.idMascota, m.nombre, m.especie, m.urgencia, m.fecha_publicacion,
            p.nombre AS protectora
     FROM mascotas m
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE m.activa = 1
     ORDER BY m.fecha_publicacion DESC
     LIMIT 5"
)->fetchAll();

header('Content-Type: application/json; charset=utf-8');
respuestaOk([
    'stats'              => $stats,
    'ultimas_solicitudes'=> $ultimasSolicitudes,
    'ultimas_mascotas'   => $ultimasMascotas,
]);