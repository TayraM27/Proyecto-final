<?php
/*--------------------------------------------------------------------------------------------
CRUD seguimientos de apadrinamiento */

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar seguimientos de un apadrinamiento */
    case 'GET':
        $idApadrinamiento = (int)($_GET['idApadrinamiento'] ?? 0);
        if (!$idApadrinamiento) {
            respuestaError('Falta idApadrinamiento.', 422);
        }
        $stmt = $pdo->prepare(
            'SELECT idSeguimiento, idApadrinamiento, contenido, tipo_archivo, ruta_archivo, fecha
             FROM seguimientos
             WHERE idApadrinamiento = ?
             ORDER BY fecha ASC'
        );
        $stmt->execute([$idApadrinamiento]);
        respuestaOk(['seguimientos' => $stmt->fetchAll()]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - añadir seguimiento */
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idApadrinamiento'], $data['contenido'])) {
            respuestaError('Faltan campos obligatorios: idApadrinamiento, contenido.', 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO seguimientos (idApadrinamiento, contenido, tipo_archivo, ruta_archivo)
             VALUES (?,?,?,?)'
        );
        $ok = $stmt->execute([
            (int)$data['idApadrinamiento'],
            $data['contenido'],
            $data['tipo_archivo']  ?? 'texto',
            $data['ruta_archivo']  ?? null,
        ]);
        if ($ok) {
            respuestaOk(['mensaje' => 'Seguimiento añadido correctamente.']);
        } else {
            respuestaError('Error al guardar el seguimiento.');
        }
        break;

    /*--------------------------------------------------------------------------------------------
    DELETE - eliminar seguimiento */
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idSeguimiento'])) {
            respuestaError('Falta idSeguimiento.', 422);
        }
        $pdo->prepare('DELETE FROM seguimientos WHERE idSeguimiento = ?')
            ->execute([(int)$data['idSeguimiento']]);
        respuestaOk(['mensaje' => 'Seguimiento eliminado.']);
        break;

    default:
        respuestaError('Método no permitido.', 405);
}