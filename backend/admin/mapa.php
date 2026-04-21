<?php
/*--------------------------------------------------------------------------------------------
admin/mapa.php
CRUD de puntos del mapa para el administrador
GET    — lista puntos
POST   — crea punto
PUT    — actualiza punto
DELETE — elimina punto */

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar puntos */
    case 'GET':
        $stmt   = $pdo->query('SELECT * FROM puntos_mapa ORDER BY idPunto DESC');
        $puntos = $stmt->fetchAll();
        respuestaOk(['puntos' => $puntos]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - crear punto */
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['nombre'], $data['categoria'], $data['latitud'], $data['longitud'])) {
            respuestaError('Faltan campos obligatorios: nombre, categoria, latitud, longitud.', 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO puntos_mapa (nombre, categoria, latitud, longitud, direccion, descripcion)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ok = $stmt->execute([
            $data['nombre'],
            $data['categoria'],
            $data['latitud'],
            $data['longitud'],
            $data['direccion']   ?? null,
            $data['descripcion'] ?? null,
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Punto creado correctamente.', 'idPunto' => $pdo->lastInsertId()]);
        } else {
            respuestaError('Error al crear el punto.');
        }
        break;

    /*--------------------------------------------------------------------------------------------
    PUT - actualizar punto */
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idPunto'])) {
            respuestaError('Falta idPunto.', 422);
        }
        $stmt = $pdo->prepare(
            'UPDATE puntos_mapa
             SET nombre=?, categoria=?, latitud=?, longitud=?, direccion=?, descripcion=?
             WHERE idPunto=?'
        );
        $ok = $stmt->execute([
            $data['nombre'],
            $data['categoria'],
            $data['latitud'],
            $data['longitud'],
            $data['direccion']   ?? null,
            $data['descripcion'] ?? null,
            $data['idPunto'],
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Punto actualizado correctamente.']);
        } else {
            respuestaError('Error al actualizar el punto.');
        }
        break;

    /*--------------------------------------------------------------------------------------------
    DELETE - eliminar punto */
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idPunto'])) {
            respuestaError('Falta idPunto.', 422);
        }
        $ok = $pdo->prepare('DELETE FROM puntos_mapa WHERE idPunto = ?')
                  ->execute([$data['idPunto']]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Punto eliminado correctamente.']);
        } else {
            respuestaError('Error al eliminar el punto.');
        }
        break;

    default:
        respuestaError('Método no permitido.', 405);
}