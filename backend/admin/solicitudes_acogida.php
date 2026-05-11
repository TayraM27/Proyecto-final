<?php
/*--------------------------------------------------------------------------------------------
GET — lista solicitudes de acogida
PUT — cambia estado de solicitud de acogida
Acceso: Admin ve todas, protectora ve solo las de sus mascotas */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdminOProtectora();

$pdo = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $estado = trim($_GET['estado'] ?? 'pendiente');

    $where = ['1=1'];
    $params = [];

    if ($estado !== 'todos') {
        $where[] = 'sa.estado = ?';
        $params[] = $estado;
    }

    if (!$esAdmin && $idProtectoraUsuario) {
        $where[] = 'm.idProtectora = ?';
        $params[] = $idProtectoraUsuario;
    }

    $cond = implode(' AND ', $where);

    $sql = "SELECT sa.idSolicitud, sa.nombre, sa.email, sa.telefono, sa.vivienda,
                   sa.experiencia, sa.tiempo, sa.mensaje, sa.estado, sa.fecha_envio,
                   m.idMascota, m.nombre AS mascota
            FROM solicitudes_acogida sa
            JOIN mascotas m ON sa.idMascota = m.idMascota
            WHERE $cond
            ORDER BY sa.fecha_envio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk(['solicitudes' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
PUT */
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($datos['idSolicitud'] ?? 0);
    $estado = trim($datos['estado'] ?? '');

    if (!$id || !$estado) respuestaError('idSolicitud y estado son obligatorios.');

    $estados_validos = ['pendiente', 'en_revision', 'aprobada', 'rechazada'];
    if (!in_array($estado, $estados_validos, true)) respuestaError('Estado no valido.');

    // Verificar que la solicitud pertenece a la protectora
    $stmt = $pdo->prepare(
        'SELECT sa.idSolicitud FROM solicitudes_acogida sa
         JOIN mascotas m ON sa.idMascota = m.idMascota
         WHERE sa.idSolicitud = ?'
    );
    $stmt->execute([$id]);
    $sol = $stmt->fetch();
    if (!$sol) respuestaError('Solicitud no encontrada.');

    if (!$esAdmin) {
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.', 403);
        $stmt = $pdo->prepare(
            'SELECT sa.idSolicitud FROM solicitudes_acogida sa
             JOIN mascotas m ON sa.idMascota = m.idMascota
             WHERE sa.idSolicitud = ? AND m.idProtectora = ?'
        );
        $stmt->execute([$id, $idProtectoraUsuario]);
        if (!$stmt->fetch()) respuestaError('No puedes gestionar esta solicitud.', 403);
    }

    $pdo->prepare(
        'UPDATE solicitudes_acogida SET estado = ?, fecha_gestion = NOW() WHERE idSolicitud = ?'
    )->execute([$estado, $id]);

    respuestaOk(['mensaje' => 'Estado actualizado.']);
}

respuestaError('Metodo no permitido.', 405);
