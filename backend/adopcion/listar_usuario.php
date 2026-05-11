<?php
/*--------------------------------------------------------------------------------------------
GET — lista solicitudes de adopción del usuario logueado
Devuelve: idSolicitud, idMascota, nombre_mascota, foto_principal,
         estado, fecha_envio, fecha_gestion, mensaje_protectora */
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesión.', 401);
}

$pdo = conectar();
$idUsuario = (int)$_SESSION['idUsuario'];
session_write_close();

$stmt = $pdo->prepare(
    'SELECT 
        s.idSolicitud, s.idMascota, s.estado, s.fecha_envio, s.fecha_gestion, s.mensaje_protectora,
        m.nombre AS nombre_mascota,
        (SELECT ruta FROM mascotas_fotos WHERE idMascota = s.idMascota AND es_principal = 1 LIMIT 1) AS foto_principal
     FROM solicitudes_adopcion s
     JOIN mascotas m ON s.idMascota = m.idMascota
     WHERE s.idUsuario = ? AND s.deleted = 0
     ORDER BY s.fecha_envio DESC'
);
$stmt->execute([$idUsuario]);
$solicitudes = $stmt->fetchAll();

respuestaOk(['solicitudes' => $solicitudes]);
