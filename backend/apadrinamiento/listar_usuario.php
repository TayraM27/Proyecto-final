<?php
/*--------------------------------------------------------------------------------------------
GET — lista apadrinamientos del usuario logueado
Devuelve: idApadrinamiento, idMascota, cantidad_mensual, metodo_pago,
         estado, fecha_solicitud, mensaje_protectora,
         mascota: { nombre, foto_principal, protectora_nombre } */
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesion.', 401);
}

$pdo = conectar();
$idUsuario = (int)$_SESSION['idUsuario'];

$stmt = $pdo->prepare(
    'SELECT 
        a.idApadrinamiento, a.idMascota, a.cantidad_mensual, a.metodo_pago,
        a.estado, a.fecha_solicitud, a.mensaje_protectora,
        m.nombre AS mascota_nombre,
        (SELECT ruta FROM mascotas_fotos WHERE idMascota = a.idMascota AND es_principal = 1 LIMIT 1) AS foto_principal,
        p.nombre AS protectora_nombre
     FROM apadrinamientos a
     JOIN mascotas m ON a.idMascota = m.idMascota
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE a.idUsuario = ? AND a.deleted = 0
     ORDER BY a.fecha_solicitud DESC'
);
$stmt->execute([$idUsuario]);
$apadrinamientos = $stmt->fetchAll();

respuestaOk(['apadrinamientos' => $apadrinamientos]);
