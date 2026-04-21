<?php
/*CRUD de donaciones para el administrador*/

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar donaciones */
    case 'GET':
        $stmt = $pdo->query(
            'SELECT d.idDonacion, d.nombre_donante, d.email_donante, d.importe,
                    d.mensaje, d.estado, d.fecha,
                    u.nombre AS usuario_nombre, u.email AS usuario_email,
                    p.nombre AS protectora_nombre
             FROM donaciones d
             LEFT JOIN usuarios u ON d.idUsuario = u.idUsuario
             LEFT JOIN protectoras p ON d.idProtectora = p.idProtectora
             ORDER BY d.fecha DESC'
        );
        respuestaOk(['donaciones' => $stmt->fetchAll()]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - registrar donación manual */
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['importe'])) {
            respuestaError('Falta el campo importe.', 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO donaciones
                (idUsuario, idProtectora, nombre_donante, email_donante, importe, mensaje, estado)
             VALUES (?,?,?,?,?,?,?)'
        );
        $ok = $stmt->execute([
            $data['idUsuario']      ?? null,
            $data['idProtectora']   ?? null,
            $data['nombre_donante'] ?? null,
            $data['email_donante']  ?? null,
            $data['importe'],
            $data['mensaje']        ?? null,
            $data['estado']         ?? 'completada',
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Donación registrada correctamente.']);
        } else {
            respuestaError('Error al registrar la donación.');
        }
        break;

    /*--------------------------------------------------------------------------------------------
    PUT - cambiar estado */
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idDonacion'], $data['estado'])) {
            respuestaError('Faltan idDonacion y estado.', 422);
        }
        $estadosValidos = ['pendiente', 'completada', 'fallida'];
        if (!in_array($data['estado'], $estadosValidos)) {
            respuestaError('Estado no válido.');
        }
        $pdo->prepare('UPDATE donaciones SET estado = ? WHERE idDonacion = ?')
            ->execute([$data['estado'], $data['idDonacion']]);
        respuestaOk(['mensaje' => 'Estado actualizado correctamente.']);
        break;

    /*--------------------------------------------------------------------------------------------
    DELETE - eliminar */
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idDonacion'])) {
            respuestaError('Falta idDonacion.', 422);
        }
        $pdo->prepare('DELETE FROM donaciones WHERE idDonacion = ?')
            ->execute([$data['idDonacion']]);
        respuestaOk(['mensaje' => 'Donación eliminada correctamente.']);
        break;

    default:
        respuestaError('Método no permitido.', 405);
}