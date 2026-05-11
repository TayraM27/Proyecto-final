<?php
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesion.', 401);
}

$idUsuario = (int)$_SESSION['idUsuario'];
session_write_close();
$pdo = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $stmt = $pdo->prepare('SELECT tipo_test, respuestas_json, puntuacion, fecha FROM test_resultados WHERE idUsuario = ? ORDER BY tipo_test, fecha DESC');
    $stmt->execute([$idUsuario]);
    $rows = $stmt->fetchAll();

    $conocimiento = null;
    $compatibilidad = null;

    foreach ($rows as $r) {
        if ($r['tipo_test'] === 'conocimiento') {
            $datos = json_decode($r['respuestas_json'], true) ?: [];
            $conocimiento = [
                'fecha' => $r['fecha'],
                'puntos' => $datos['puntos'] ?? $r['puntuacion'],
                'total' => $datos['total'] ?? 10,
                'pct' => $datos['pct'] ?? ($r['puntuacion'] ? round($r['puntuacion'] / 10 * 100) : 0),
            ];
        } elseif ($r['tipo_test'] === 'compatibilidad') {
            $datos = json_decode($r['respuestas_json'], true) ?: [];
            $compatibilidad = [
                'fecha' => $r['fecha'],
                'especieFiltro' => $datos['especieFiltro'] ?? 'todos',
            ];
        }
    }

    respuestaOk(['conocimiento' => $conocimiento, 'compatibilidad' => $compatibilidad]);
}

if ($metodo === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $tipo = $input['tipo'] ?? '';
    $datos = $input['datos'] ?? [];
    $accion = $input['accion'] ?? 'guardar';

    if (!in_array($tipo, ['conocimiento', 'compatibilidad'])) {
        respuestaError('Tipo de test no valido.', 400);
    }

    if ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM test_resultados WHERE idUsuario = ? AND tipo_test = ?')->execute([$idUsuario, $tipo]);
        respuestaOk(['mensaje' => 'Resultado eliminado.']);
    }

    $puntuacion = null;
    if ($tipo === 'conocimiento') {
        $puntuacion = intval($datos['puntos'] ?? 0);
    }

    $stmt = $pdo->prepare('INSERT INTO test_resultados (idUsuario, tipo_test, respuestas_json, puntuacion) VALUES (?, ?, ?, ?)');
    $stmt->execute([$idUsuario, $tipo, json_encode($datos), $puntuacion]);

    respuestaOk(['mensaje' => 'Resultado guardado.']);
}

respuestaError('Metodo no permitido.', 405);
