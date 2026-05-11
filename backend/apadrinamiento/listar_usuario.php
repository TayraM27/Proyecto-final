<?php
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
        a.idApadrinamiento, a.idMascota, a.cuota AS cantidad_mensual, a.metodo_pago,
        a.estado, a.fecha_inicio AS fecha_solicitud,
        m.nombre AS mascota_nombre,
        (SELECT ruta FROM mascotas_fotos WHERE idMascota = a.idMascota AND es_principal = 1 LIMIT 1) AS foto_principal,
        p.nombre AS protectora_nombre,
        p.activa AS protectora_activa
     FROM apadrinamientos a
     JOIN mascotas m ON a.idMascota = m.idMascota
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE a.idUsuario = ?
     ORDER BY a.fecha_inicio DESC'
);
$stmt->execute([$idUsuario]);
$apadrinamientos = $stmt->fetchAll();

respuestaOk(['apadrinamientos' => $apadrinamientos]);