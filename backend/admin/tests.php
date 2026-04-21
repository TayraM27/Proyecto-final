<?php
/*--------------------------------------------------------------------------------------------
admin/tests.php
CRUD de preguntas y opciones del test para el administrador
GET    — lista preguntas con sus opciones
POST   — crea pregunta con opciones
PUT    — actualiza pregunta y opciones
DELETE — elimina pregunta (y sus opciones por CASCADE) */

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar preguntas con sus opciones */
    case 'GET':
        $preguntas = $pdo->query(
            'SELECT * FROM test_preguntas ORDER BY tipo_test, orden, idPregunta ASC'
        )->fetchAll();

        foreach ($preguntas as &$pregunta) {
            $stmt = $pdo->prepare(
                'SELECT * FROM test_opciones WHERE idPregunta = ? ORDER BY idOpcion ASC'
            );
            $stmt->execute([$pregunta['idPregunta']]);
            $pregunta['opciones'] = $stmt->fetchAll();
        }

        respuestaOk(['preguntas' => $preguntas]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - crear pregunta con sus opciones */
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['texto'], $data['tipo_test'], $data['opciones'])
            || !is_array($data['opciones'])) {
            respuestaError('Faltan campos: texto, tipo_test, opciones.', 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO test_preguntas (tipo_test, texto, orden) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['tipo_test'],
            $data['texto'],
            (int)($data['orden'] ?? 0),
        ]);
        $idPregunta = $pdo->lastInsertId();

        foreach ($data['opciones'] as $op) {
            $pdo->prepare(
                'INSERT INTO test_opciones (idPregunta, texto, valor, es_correcta)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $idPregunta,
                $op['texto'],
                $op['valor']       ?? null,
                (int)($op['es_correcta'] ?? 0),
            ]);
        }

        respuestaOk(['mensaje' => 'Pregunta creada correctamente.', 'idPregunta' => $idPregunta]);
        break;

    /*--------------------------------------------------------------------------------------------
    PUT - actualizar pregunta y opciones */
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idPregunta'], $data['texto'], $data['opciones'])) {
            respuestaError('Faltan campos: idPregunta, texto, opciones.', 422);
        }

        $pdo->prepare(
            'UPDATE test_preguntas SET texto = ?, orden = ? WHERE idPregunta = ?'
        )->execute([
            $data['texto'],
            (int)($data['orden'] ?? 0),
            $data['idPregunta'],
        ]);

        $pdo->prepare('DELETE FROM test_opciones WHERE idPregunta = ?')
            ->execute([$data['idPregunta']]);

        foreach ($data['opciones'] as $op) {
            $pdo->prepare(
                'INSERT INTO test_opciones (idPregunta, texto, valor, es_correcta)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $data['idPregunta'],
                $op['texto'],
                $op['valor']       ?? null,
                (int)($op['es_correcta'] ?? 0),
            ]);
        }

        respuestaOk(['mensaje' => 'Pregunta actualizada correctamente.']);
        break;

    /*--------------------------------------------------------------------------------------------
    DELETE - eliminar pregunta (opciones se borran por CASCADE) */
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idPregunta'])) {
            respuestaError('Falta idPregunta.', 422);
        }

        $ok = $pdo->prepare('DELETE FROM test_preguntas WHERE idPregunta = ?')
                  ->execute([$data['idPregunta']]);

        if ($ok) {
            respuestaOk(['mensaje' => 'Pregunta eliminada correctamente.']);
        } else {
            respuestaError('Error al eliminar la pregunta.');
        }
        break;

    default:
        respuestaError('Método no permitido.', 405);
}