<?php
/*--------------------------------------------------------------------------------------------
CRUD — Eventos (admin solo lectura, protectora CRUD sobre sus eventos) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
requerirAdminOProtectora();

$pdo = conectar();
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();
$idUsuario = (int)($_SESSION['idUsuario'] ?? 0);
session_write_close();

$id = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? 'listar';

switch ($accion) {
    case 'listar':
        $buscar = $_GET['buscar'] ?? '';
        $activos = $_GET['activos'] ?? '1';

        $sql = "SELECT e.*, p.nombre AS protectora_nombre FROM eventos e
                LEFT JOIN protectoras p ON e.idProtectora = p.idProtectora
                WHERE 1=1";
        $params = [];

        if (!$esAdmin && $idProtectoraUsuario) {
            $sql .= " AND e.idProtectora = ?";
            $params[] = $idProtectoraUsuario;
        }

        if ($buscar) {
            $sql .= " AND (e.titulo LIKE :buscar OR e.lugar LIKE :buscar2 OR e.localidad LIKE :buscar3)";
            $params[':buscar'] = "%$buscar%";
            $params[':buscar2'] = "%$buscar%";
            $params[':buscar3'] = "%$buscar%";
        }

        if ($activos === '1') {
            $sql .= " AND e.activa=1";
        }

        $sql .= " ORDER BY e.fecha_evento ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respuestaOk(['eventos' => $stmt->fetchAll()]);
        break;

    case 'crear':
        if ($esAdmin) respuestaError('Los administradores no pueden crear eventos. Esta acción corresponde a la protectora.', 403);
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

        $data = jsonInput();

        $sql = "INSERT INTO eventos (idProtectora, titulo, descripcion, fecha_evento, hora, lugar, localidad, url_info, precio, activa)
                VALUES (:idProtectora, :titulo, :descripcion, :fecha_evento, :hora, :lugar, :localidad, :url_info, :precio, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idProtectora' => $idProtectoraUsuario,
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':fecha_evento' => $data['fecha_evento'],
            ':hora' => $data['hora'] ?? null,
            ':lugar' => $data['lugar'] ?? null,
            ':localidad' => $data['localidad'] ?? null,
            ':url_info' => $data['url_info'] ?? null,
            ':precio' => $data['precio'] ?? 'Gratis',
        ]);

        respuestaOk(['id' => $pdo->lastInsertId()]);
        break;

    case 'actualizar':
        if ($esAdmin) respuestaError('Los administradores no pueden editar eventos. Esta acción corresponde a la protectora.', 403);
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

        $data = jsonInput();

        $sql = "UPDATE eventos SET titulo=:titulo, descripcion=:descripcion, fecha_evento=:fecha_evento,
                hora=:hora, lugar=:lugar, localidad=:localidad, url_info=:url_info, precio=:precio, activa=:activa
                WHERE idEvento=:id AND idProtectora=:idProtectora";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':idProtectora' => $idProtectoraUsuario,
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':fecha_evento' => $data['fecha_evento'],
            ':hora' => $data['hora'] ?? null,
            ':lugar' => $data['lugar'] ?? null,
            ':localidad' => $data['localidad'] ?? null,
            ':url_info' => $data['url_info'] ?? null,
            ':precio' => $data['precio'] ?? 'Gratis',
            ':activa' => $data['activa'] ?? 1,
        ]);

        respuestaOk(['ok' => true]);
        break;

    case 'eliminar':
        if (!$esAdmin) {
            if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
            $stmt = $pdo->prepare("UPDATE eventos SET activa=0 WHERE idEvento = ? AND idProtectora = ?");
            $stmt->execute([$id, $idProtectoraUsuario]);
        } else {
            $motivo = $_POST['motivo'] ?? '';
            if (!$motivo) respuestaError('El administrador debe indicar un motivo para desactivar este evento.');
            $stmt = $pdo->prepare("UPDATE eventos SET activa=0 WHERE idEvento = ?");
            $stmt->execute([$id]);
            $pdo->prepare(
                'INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, reason)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$idUsuario, 'admin', 'soft_delete_evento', 'evento', $id, $motivo]);
        }
        respuestaOk(['ok' => true]);
        break;

    case 'activar':
        if ($esAdmin) respuestaError('Los administradores no pueden reactivar eventos.', 403);
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
        $stmt = $pdo->prepare("UPDATE eventos SET activa=1 WHERE idEvento = ? AND idProtectora = ?");
        $stmt->execute([$id, $idProtectoraUsuario]);
        respuestaOk(['ok' => true]);
        break;
}
