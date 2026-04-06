<?php
/*--------------------------------------------------------------------------------------------
admin/solicitudes.php
Gestion de solicitudes de adopcion para el administrador
GET — lista solicitudes con filtros
PUT — cambia estado de la solicitud */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar */
if ($metodo === 'GET') {
    $estado = $_GET['estado'] ?? 'pendiente';
    $pagina = (int)($_GET['pagina'] ?? 1);
    $p      = paginacion($pagina, 20);

    $estadosValidos = ['pendiente', 'en_revision', 'aprobada', 'rechazada', 'todos'];
    if (!in_array($estado, $estadosValidos)) {
        $estado = 'pendiente';
    }

    $where  = ['1 = 1'];
    $params = [];

    if ($estado !== 'todos') {
        $where[]  = 's.estado = ?';
        $params[] = $estado;
    }

    $condicion = implode(' AND ', $where);

    $total = $pdo->prepare("SELECT COUNT(*) FROM solicitudes_adopcion s WHERE $condicion");
    $total->execute($params);
    $total = (int)$total->fetchColumn();

    $params[] = $p['limite'];
    $params[] = $p['offset'];

    $stmt = $pdo->prepare(
        "SELECT s.idSolicitud, s.nombre, s.email, s.telefono, s.mensaje,
                s.estado, s.fecha_envio, s.fecha_gestion,
                m.idMascota, m.nombre AS mascota_nombre, m.especie,
                p.nombre AS protectora_nombre
         FROM solicitudes_adopcion s
         JOIN mascotas m ON s.idMascota = m.idMascota
         JOIN protectoras p ON m.idProtectora = p.idProtectora
         WHERE $condicion
         ORDER BY s.fecha_envio DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute($params);

    respuestaOk([
        'solicitudes' => $stmt->fetchAll(),
        'total'       => $total,
        'totalPaginas'=> (int)ceil($total / $p['limite']),
        'pagina'      => $p['pagina'],
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT - cambiar estado */
if ($metodo === 'PUT') {
    $datos      = json_decode(file_get_contents('php://input'), true) ?? [];
    $idSolicitud= (int)($datos['idSolicitud'] ?? 0);
    $nuevoEstado= $datos['estado']            ?? '';

    $estadosValidos = ['pendiente', 'en_revision', 'aprobada', 'rechazada'];
    if (!$idSolicitud || !in_array($nuevoEstado, $estadosValidos)) {
        respuestaError('Datos no validos.');
    }

    $pdo->prepare(
        'UPDATE solicitudes_adopcion
         SET estado = ?, fecha_gestion = NOW()
         WHERE idSolicitud = ?'
    )->execute([$nuevoEstado, $idSolicitud]);

    // Si se aprueba la solicitud, marcar la mascota como en_proceso
    if ($nuevoEstado === 'aprobada') {
        $stmt = $pdo->prepare('SELECT idMascota FROM solicitudes_adopcion WHERE idSolicitud = ?');
        $stmt->execute([$idSolicitud]);
        $sol = $stmt->fetch();
        if ($sol) {
            $pdo->prepare(
                "UPDATE mascotas SET estado_adopcion = 'en_proceso' WHERE idMascota = ?"
            )->execute([$sol['idMascota']]);
        }
    }

    respuestaOk(['mensaje' => 'Estado de la solicitud actualizado a: ' . $nuevoEstado]);
}

respuestaError('Metodo no permitido.', 405);