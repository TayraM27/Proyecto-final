<?php
/*--------------------------------------------------------------------------------------------
GET — lista solicitudes con filtro de estado y paginación
PUT — cambia estado de una solicitud */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdminOProtectora();

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $estado = $_GET['estado'] ?? 'todos';
    $pagina = (int)($_GET['pagina'] ?? 1);
    $p      = pagina($pagina, 15);

    $condParts = [];
    $params    = [];

    if (!$esAdmin && $idProtectoraUsuario) {
        $condParts[] = 'c.idProtectora = ?';
        $params[]    = $idProtectoraUsuario;
    }

    if ($estado !== 'todos') {
        $condParts[] = 'c.estado = ?';
        $params[]    = $estado;
    }

    $cond = $condParts ? 'WHERE ' . implode(' AND ', $condParts) : '';

    $unionSql = "SELECT
                    s.idSolicitud,
                    s.nombre,
                    s.email,
                    s.telefono,
                    s.mensaje,
                    s.estado,
                    s.fecha_envio,
                    m.nombre          AS mascota,
                    m.especie         AS mascota_especie,
                    p.nombre          AS protectora,
                    m.idProtectora,
                    'adopcion'        AS tipo,
                    NULL              AS cuota,
                    NULL              AS metodo_pago
                FROM solicitudes_adopcion s
                JOIN mascotas    m ON s.idMascota    = m.idMascota
                JOIN protectoras p ON m.idProtectora = p.idProtectora

                UNION ALL

                SELECT
                    a.idApadrinamiento AS idSolicitud,
                    a.nombre_pagador   AS nombre,
                    a.email_pagador    AS email,
                    a.telefono,
                    a.mensaje,
                    a.estado,
                    a.fecha_inicio     AS fecha_envio,
                    m.nombre           AS mascota,
                    m.especie          AS mascota_especie,
                    p.nombre           AS protectora,
                    m.idProtectora,
                    'apadrinamiento'   AS tipo,
                    a.cuota,
                    a.metodo_pago
                FROM apadrinamientos a
                JOIN mascotas    m ON a.idMascota    = m.idMascota
                JOIN protectoras p ON m.idProtectora = p.idProtectora";

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM ($unionSql) c $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT * FROM ($unionSql) c $cond ORDER BY c.fecha_envio DESC LIMIT ? OFFSET ?";

    $params[] = $p['limite'];
    $params[] = $p['offset'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk([
        'solicitudes'  => $stmt->fetchAll(),
        'total'        => $total,
        'pagina'       => $p['pagina'],
        'totalPaginas' => (int)ceil($total / $p['limite']),
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT — cambiar estado (SOLO PROTECTORA sobre solicitudes de sus mascotas, admin NO puede) */
if ($metodo === 'PUT') {
    if ($esAdmin) {
        respuestaError('Los administradores no pueden gestionar solicitudes. Esta acción corresponde a la protectora.', 403);
    }

    if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

    $datos       = json_decode(file_get_contents('php://input'), true) ?? [];
    $id          = (int)($datos['idSolicitud'] ?? 0);
    $nuevoEstado = limpiar($datos['estado'] ?? '');

    if (!$id) respuestaError('idSolicitud requerido.');

    $estadosValidos = ['pendiente','en_revision','aprobada','rechazada'];
    if (!in_array($nuevoEstado, $estadosValidos)) respuestaError('Estado no válido.');

    // Verificar que la solicitud pertenece a una mascota de esta protectora
    $stmt = $pdo->prepare(
        'SELECT s.idSolicitud, s.idMascota FROM solicitudes_adopcion s
         JOIN mascotas m ON s.idMascota = m.idMascota
         WHERE s.idSolicitud = ? AND m.idProtectora = ?'
    );
    $stmt->execute([$id, $idProtectoraUsuario]);
    if (!$stmt->fetch()) respuestaError('No puedes gestionar esta solicitud.', 403);

    $pdo->prepare('UPDATE solicitudes_adopcion SET estado = ? WHERE idSolicitud = ?')
        ->execute([$nuevoEstado, $id]);

    /* Si se aprueba, marcar mascota en proceso */
    if ($nuevoEstado === 'aprobada') {
        $stmtMasc = $pdo->prepare('SELECT idMascota FROM solicitudes_adopcion WHERE idSolicitud = ?');
        $stmtMasc->execute([$id]);
        $sol = $stmtMasc->fetch();
        if ($sol) {
            $pdo->prepare("UPDATE mascotas SET estado_adopcion = 'en_proceso' WHERE idMascota = ?")
                ->execute([$sol['idMascota']]);
        }
    }

    respuestaOk(['mensaje' => 'Estado actualizado correctamente.']);
}

respuestaError('Método no permitido.', 405);