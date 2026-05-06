<?php
/*CRUD de apadrinamientos para el administrador*/

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
requerirAdminOProtectora();

$pdo = conectar();
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar apadrinamientos */
    case 'GET':
        $where = [];
        $params = [];
        if (!$esAdmin && $idProtectoraUsuario) {
            $where[] = 'm.idProtectora = ?';
            $params[] = $idProtectoraUsuario;
        }
        $cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $pdo->prepare(
            'SELECT a.idApadrinamiento, a.cuota, a.fecha_inicio, a.fecha_fin, a.estado,
                    a.nombre_pagador, a.email_pagador,
                    m.nombre AS mascota, m.especie, m.idProtectora,
                    u.nombre AS padrino, u.email AS padrino_email
             FROM apadrinamientos a
             JOIN mascotas m ON a.idMascota = m.idMascota
             JOIN usuarios u ON a.idUsuario = u.idUsuario
             ' . $cond . '
             ORDER BY a.idApadrinamiento DESC'
        );
        $stmt->execute($params);
        respuestaOk(['apadrinamientos' => $stmt->fetchAll()]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - crear apadrinamiento (SOLO FLUJO PUBLICO, admin NO puede crear) */
    case 'POST':
        respuestaError('Los apadrinamientos se crean desde el flujo público. Los administradores no pueden crear apadrinamientos.', 403);
        break;

    /*--------------------------------------------------------------------------------------------
    PUT - actualizar estado (SOLO PROTECTORA sobre sus apadrinamientos, admin NO puede) */
    case 'PUT':
        if ($esAdmin) {
            respuestaError('Los administradores no pueden gestionar apadrinamientos. Esta acción corresponde a la protectora.', 403);
        }
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idApadrinamiento'])) {
            respuestaError('Falta idApadrinamiento.', 422);
        }

        $check = $pdo->prepare('SELECT m.idProtectora FROM apadrinamientos a JOIN mascotas m ON a.idMascota = m.idMascota WHERE a.idApadrinamiento = ?');
        $check->execute([$data['idApadrinamiento']]);
        $res = $check->fetch();
        if (!$res || (int)$res['idProtectora'] !== $idProtectoraUsuario) {
            respuestaError('No puedes gestionar este apadrinamiento.', 403);
        }

        $campos = [];
        $params = [];

        if (isset($data['estado'])) {
            $campos[] = 'estado = ?';
            $params[] = $data['estado'];
        }
        if (isset($data['fecha_fin'])) {
            $campos[] = 'fecha_fin = ?';
            $params[] = $data['fecha_fin'];
        }
        if (isset($data['cuota'])) {
            $campos[] = 'cuota = ?';
            $params[] = $data['cuota'];
        }

        if (empty($campos)) {
            respuestaError('No hay campos para actualizar.');
        }

        $params[] = $data['idApadrinamiento'];
        $pdo->prepare('UPDATE apadrinamientos SET ' . implode(', ', $campos) . ' WHERE idApadrinamiento = ?')
            ->execute($params);

        respuestaOk(['mensaje' => 'Apadrinamiento actualizado correctamente.']);
        break;

    /*--------------------------------------------------------------------------------------------
    DELETE - eliminar (SOLO PROTECTORA sobre sus apadrinamientos, admin NO puede) */
    case 'DELETE':
        if ($esAdmin) {
            respuestaError('Los administradores no pueden eliminar apadrinamientos. Esta acción corresponde a la protectora.', 403);
        }
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idApadrinamiento'])) {
            respuestaError('Falta idApadrinamiento.', 422);
        }

        $check = $pdo->prepare('SELECT m.idProtectora FROM apadrinamientos a JOIN mascotas m ON a.idMascota = m.idMascota WHERE a.idApadrinamiento = ?');
        $check->execute([$data['idApadrinamiento']]);
        $res = $check->fetch();
        if (!$res || (int)$res['idProtectora'] !== $idProtectoraUsuario) {
            respuestaError('No puedes eliminar este apadrinamiento.', 403);
        }

        $pdo->prepare('DELETE FROM apadrinamientos WHERE idApadrinamiento = ?')
            ->execute([$data['idApadrinamiento']]);
        respuestaOk(['mensaje' => 'Apadrinamiento eliminado correctamente.']);
        break;

    default:
        respuestaError('Método no permitido.', 405);
}