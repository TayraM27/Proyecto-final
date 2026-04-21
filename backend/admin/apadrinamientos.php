<?php
/*CRUD de apadrinamientos para el administrador*/

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar apadrinamientos */
    case 'GET':
        $stmt = $pdo->query(
            'SELECT a.idApadrinamiento, a.cuota, a.fecha_inicio, a.fecha_fin, a.estado,
                    a.nombre_pagador, a.email_pagador,
                    m.nombre AS mascota, m.especie,
                    u.nombre AS padrino, u.email AS padrino_email
             FROM apadrinamientos a
             JOIN mascotas m ON a.idMascota = m.idMascota
             JOIN usuarios u ON a.idUsuario = u.idUsuario
             ORDER BY a.idApadrinamiento DESC'
        );
        respuestaOk(['apadrinamientos' => $stmt->fetchAll()]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - crear apadrinamiento manual */
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idMascota'], $data['idUsuario'], $data['cuota'])) {
            respuestaError('Faltan campos obligatorios: idMascota, idUsuario, cuota.', 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO apadrinamientos
                (idMascota, idUsuario, cuota, fecha_inicio, estado, nombre_pagador, email_pagador)
             VALUES (?,?,?,?,?,?,?)'
        );
        $ok = $stmt->execute([
            $data['idMascota'],
            $data['idUsuario'],
            $data['cuota'],
            $data['fecha_inicio']    ?? date('Y-m-d'),
            $data['estado']          ?? 'activo',
            $data['nombre_pagador']  ?? null,
            $data['email_pagador']   ?? null,
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Apadrinamiento creado correctamente.']);
        } else {
            respuestaError('Error al crear el apadrinamiento.');
        }
        break;

    /*--------------------------------------------------------------------------------------------
    PUT - actualizar estado */
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idApadrinamiento'])) {
            respuestaError('Falta idApadrinamiento.', 422);
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
    DELETE - eliminar */
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idApadrinamiento'])) {
            respuestaError('Falta idApadrinamiento.', 422);
        }
        $pdo->prepare('DELETE FROM apadrinamientos WHERE idApadrinamiento = ?')
            ->execute([$data['idApadrinamiento']]);
        respuestaOk(['mensaje' => 'Apadrinamiento eliminado correctamente.']);
        break;

    default:
        respuestaError('Método no permitido.', 405);
}