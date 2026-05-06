<?php
/*--------------------------------------------------------------------------------------------
CRUD — Eventos
Admin: CRUD sobre eventos globales (idProtectora IS NULL), solo lectura en eventos de protectora
Protectora: CRUD sobre sus propios eventos */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdminOProtectora();

$pdo                 = conectar();
$esAdmin             = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();
$idUsuario           = (int)($_SESSION['idUsuario'] ?? 0);
session_write_close();

$id     = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? 'listar';

switch ($accion) {

    /*--------------------------------------------------------------------------------------------
    listar */
    case 'listar':
        $buscar  = $_GET['buscar'] ?? '';
        $activos = $_GET['activos'] ?? '1';

        $sql    = "SELECT e.*, p.nombre AS protectora_nombre
                   FROM eventos e
                   LEFT JOIN protectoras p ON e.idProtectora = p.idProtectora
                   WHERE 1=1";
        $params = [];

        /* Protectora ve solo sus eventos */
        if (!$esAdmin && $idProtectoraUsuario) {
            $sql .= " AND e.idProtectora = ?";
            $params[] = $idProtectoraUsuario;
        }

        if ($buscar) {
            $sql .= " AND (e.titulo LIKE ? OR e.lugar LIKE ? OR e.localidad LIKE ?)";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
        }

        if ($activos === '1') {
            $sql .= " AND e.activa = 1";
        }

        $sql .= " ORDER BY e.fecha_evento ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respuestaOk(['eventos' => $stmt->fetchAll()]);
        break;

    /*--------------------------------------------------------------------------------------------
    crear */
    case 'crear':
        $data = jsonInput();

        if ($esAdmin) {
            /* Admin crea eventos globales (idProtectora = NULL) */
            $stmt = $pdo->prepare(
                "INSERT INTO eventos (idProtectora, titulo, descripcion, fecha_evento, hora, lugar, localidad, url_info, precio, activa)
                 VALUES (NULL, :titulo, :descripcion, :fecha_evento, :hora, :lugar, :localidad, :url_info, :precio, 1)"
            );
            $stmt->execute([
                ':titulo'       => $data['titulo'],
                ':descripcion'  => $data['descripcion']  ?? null,
                ':fecha_evento' => $data['fecha_evento'],
                ':hora'         => $data['hora']         ?? null,
                ':lugar'        => $data['lugar']        ?? null,
                ':localidad'    => $data['localidad']    ?? null,
                ':url_info'     => $data['url_info']     ?? null,
                ':precio'       => $data['precio']       ?? 'Gratis',
            ]);
        } else {
            if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
            $stmt = $pdo->prepare(
                "INSERT INTO eventos (idProtectora, titulo, descripcion, fecha_evento, hora, lugar, localidad, url_info, precio, activa)
                 VALUES (:idProtectora, :titulo, :descripcion, :fecha_evento, :hora, :lugar, :localidad, :url_info, :precio, 1)"
            );
            $stmt->execute([
                ':idProtectora' => $idProtectoraUsuario,
                ':titulo'       => $data['titulo'],
                ':descripcion'  => $data['descripcion']  ?? null,
                ':fecha_evento' => $data['fecha_evento'],
                ':hora'         => $data['hora']         ?? null,
                ':lugar'        => $data['lugar']        ?? null,
                ':localidad'    => $data['localidad']    ?? null,
                ':url_info'     => $data['url_info']     ?? null,
                ':precio'       => $data['precio']       ?? 'Gratis',
            ]);
        }

        respuestaOk(['id' => (int)$pdo->lastInsertId(), 'mensaje' => 'Evento creado.']);
        break;

    /*--------------------------------------------------------------------------------------------
    actualizar */
    case 'actualizar':
        $data = jsonInput();

        if ($esAdmin) {
            /* Admin solo puede editar eventos globales */
            $stmtCheck = $pdo->prepare('SELECT idEvento, idProtectora FROM eventos WHERE idEvento = ?');
            $stmtCheck->execute([$id]);
            $evento = $stmtCheck->fetch();
            if (!$evento) respuestaError('Evento no encontrado.', 404);
            if ($evento['idProtectora']) respuestaError('No puedes editar eventos de protectoras.', 403);

            $stmt = $pdo->prepare(
                "UPDATE eventos SET titulo=:titulo, descripcion=:descripcion, fecha_evento=:fecha_evento,
                 hora=:hora, lugar=:lugar, localidad=:localidad, url_info=:url_info, precio=:precio, activa=:activa
                 WHERE idEvento=:id AND idProtectora IS NULL"
            );
            $stmt->execute([
                ':id'           => $id,
                ':titulo'       => $data['titulo'],
                ':descripcion'  => $data['descripcion']  ?? null,
                ':fecha_evento' => $data['fecha_evento'],
                ':hora'         => $data['hora']         ?? null,
                ':lugar'        => $data['lugar']        ?? null,
                ':localidad'    => $data['localidad']    ?? null,
                ':url_info'     => $data['url_info']     ?? null,
                ':precio'       => $data['precio']       ?? 'Gratis',
                ':activa'       => isset($data['activa']) ? (int)$data['activa'] : 1,
            ]);
        } else {
            if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
            $stmt = $pdo->prepare(
                "UPDATE eventos SET titulo=:titulo, descripcion=:descripcion, fecha_evento=:fecha_evento,
                 hora=:hora, lugar=:lugar, localidad=:localidad, url_info=:url_info, precio=:precio, activa=:activa
                 WHERE idEvento=:id AND idProtectora=:idProtectora"
            );
            $stmt->execute([
                ':id'           => $id,
                ':idProtectora' => $idProtectoraUsuario,
                ':titulo'       => $data['titulo'],
                ':descripcion'  => $data['descripcion']  ?? null,
                ':fecha_evento' => $data['fecha_evento'],
                ':hora'         => $data['hora']         ?? null,
                ':lugar'        => $data['lugar']        ?? null,
                ':localidad'    => $data['localidad']    ?? null,
                ':url_info'     => $data['url_info']     ?? null,
                ':precio'       => $data['precio']       ?? 'Gratis',
                ':activa'       => isset($data['activa']) ? (int)$data['activa'] : 1,
            ]);
        }

        respuestaOk(['mensaje' => 'Evento actualizado.']);
        break;

    /*--------------------------------------------------------------------------------------------
    eliminar (desactivar) */
    case 'eliminar':
        if ($esAdmin) {
            /* Admin solo puede desactivar eventos globales */
            $stmtCheck = $pdo->prepare('SELECT idProtectora FROM eventos WHERE idEvento = ?');
            $stmtCheck->execute([$id]);
            $evento = $stmtCheck->fetch();
            if (!$evento) respuestaError('Evento no encontrado.', 404);
            if ($evento['idProtectora']) respuestaError('No puedes eliminar eventos de protectoras.', 403);
            $pdo->prepare("UPDATE eventos SET activa = 0 WHERE idEvento = ? AND idProtectora IS NULL")->execute([$id]);
        } else {
            if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
            $pdo->prepare("UPDATE eventos SET activa = 0 WHERE idEvento = ? AND idProtectora = ?")->execute([$id, $idProtectoraUsuario]);
        }
        respuestaOk(['mensaje' => 'Evento desactivado.']);
        break;

    /*--------------------------------------------------------------------------------------------
    activar */
    case 'activar':
        if ($esAdmin) {
            $stmtCheck = $pdo->prepare('SELECT idProtectora FROM eventos WHERE idEvento = ?');
            $stmtCheck->execute([$id]);
            $evento = $stmtCheck->fetch();
            if (!$evento) respuestaError('Evento no encontrado.', 404);
            if ($evento['idProtectora']) respuestaError('No puedes reactivar eventos de protectoras.', 403);
            $pdo->prepare("UPDATE eventos SET activa = 1 WHERE idEvento = ? AND idProtectora IS NULL")->execute([$id]);
        } else {
            if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
            $pdo->prepare("UPDATE eventos SET activa = 1 WHERE idEvento = ? AND idProtectora = ?")->execute([$id, $idProtectoraUsuario]);
        }
        respuestaOk(['mensaje' => 'Evento activado.']);
        break;

    default:
        respuestaError('Acción no reconocida.', 400);
}