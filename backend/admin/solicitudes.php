<?php
/*--------------------------------------------------------------------------------------------
GET — lista solicitudes con filtro de estado y paginación
PUT — cambia estado de una solicitud */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdmin();
session_write_close();

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $estado = $_GET['estado'] ?? 'todos';
    $pagina = (int)($_GET['pagina'] ?? 1);
    $p      = paginacion($pagina, 15);

    $where  = [];
    $params = [];

    $estadosValidos = ['pendiente','en_revision','aprobada','rechazada'];
    if ($estado !== 'todos' && in_array($estado, $estadosValidos)) {
        $where[]  = 's.estado = ?';
        $params[] = $estado;
    }

    $cond = $where ? implode(' AND ', $where) : '1';

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM solicitudes_adopcion s WHERE $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT
                s.idSolicitud,
                s.nombre,
                s.email,
                s.telefono,
                s.mensaje,
                s.estado,
                s.fecha_envio,
                m.nombre   AS mascota,
                m.especie  AS mascota_especie,
                p.nombre   AS protectora
            FROM solicitudes_adopcion s
            JOIN mascotas    m ON s.idMascota    = m.idMascota
            JOIN protectoras p ON m.idProtectora = p.idProtectora
            WHERE $cond
            ORDER BY s.fecha_envio DESC
            LIMIT ? OFFSET ?";

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
PUT — cambiar estado */
if ($metodo === 'PUT') {
    $datos       = json_decode(file_get_contents('php://input'), true) ?? [];
    $id          = (int)($datos['idSolicitud'] ?? 0);
    $nuevoEstado = $datos['estado'] ?? '';

    if (!$id) respuestaError('idSolicitud requerido.');

    $estadosValidos = ['pendiente','en_revision','aprobada','rechazada'];
    if (!in_array($nuevoEstado, $estadosValidos)) respuestaError('Estado no válido.');

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