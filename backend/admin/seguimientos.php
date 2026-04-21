<?php
// Panel admin: CRUD seguimientos
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';

iniciarSesionSegura();
requerirAdmin();
$pdo = conectar();

header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Listar seguimientos con info de mascota y usuario
        $stmt = $pdo->query('SELECT s.*, m.nombre AS mascota, u.nombre AS responsable FROM seguimientos s JOIN mascotas m ON s.idMascota = m.idMascota JOIN usuarios u ON s.idUsuario = u.idUsuario ORDER BY s.idSeguimiento DESC');
        $seguimientos = $stmt->fetchAll();
        respuestaOk(['seguimientos' => $seguimientos]);
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idMascota'], $data['idUsuario'], $data['fecha'], $data['descripcion'])) {
            respuestaError('Faltan datos obligatorios', 422);
        }
        $stmt = $pdo->prepare('INSERT INTO seguimientos (idMascota, idUsuario, fecha, descripcion, foto) VALUES (?, ?, ?, ?, ?)');
        $ok = $stmt->execute([
            $data['idMascota'],
            $data['idUsuario'],
            $data['fecha'],
            $data['descripcion'],
            $data['foto'] ?? null,
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Seguimiento creado correctamente']);
        } else {
            respuestaError('Error al crear seguimiento');
        }
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idSeguimiento'])) {
            respuestaError('Falta idSeguimiento', 422);
        }
        $stmt = $pdo->prepare('UPDATE seguimientos SET idMascota=?, idUsuario=?, fecha=?, descripcion=?, foto=? WHERE idSeguimiento=?');
        $ok = $stmt->execute([
            $data['idMascota'],
            $data['idUsuario'],
            $data['fecha'],
            $data['descripcion'],
            $data['foto'] ?? null,
            $data['idSeguimiento']
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Seguimiento actualizado correctamente']);
        } else {
            respuestaError('Error al actualizar seguimiento');
        }
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idSeguimiento'])) {
            respuestaError('Falta idSeguimiento', 422);
        }
        $stmt = $pdo->prepare('DELETE FROM seguimientos WHERE idSeguimiento=?');
        $ok = $stmt->execute([$data['idSeguimiento']]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Seguimiento eliminado correctamente']);
        } else {
            respuestaError('Error al eliminar seguimiento');
        }
        break;
    default:
        respuestaError('Método no permitido', 405);
}
