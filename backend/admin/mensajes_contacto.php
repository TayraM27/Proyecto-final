<?php
/*CRUD de mensajes de contacto para el administrador */

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar mensajes (solo admin) */
if ($metodo === 'GET') {
    requerirAdmin();
    $stmt = $pdo->query(
        'SELECT idMensaje, nombre, email, asunto, mensaje, tipo, leido, fecha
         FROM mensajes_contacto
         ORDER BY fecha DESC'
    );
    respuestaOk(['mensajes' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
POST - recibir mensaje desde formulario público */
if ($metodo === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($data['nombre']) || empty($data['email']) || empty($data['mensaje'])) {
        respuestaError('Faltan campos obligatorios: nombre, email, mensaje.');
    }

    $tiposValidos = ['colaboracion', 'protectora', 'otro'];
    $tipo = in_array($data['tipo'] ?? '', $tiposValidos) ? $data['tipo'] : 'otro';

    $stmt = $pdo->prepare(
        'INSERT INTO mensajes_contacto (nombre, email, asunto, mensaje, tipo)
         VALUES (?,?,?,?,?)'
    );
    $ok = $stmt->execute([
        $data['nombre'],
        $data['email'],
        $data['asunto']  ?? null,
        $data['mensaje'],
        $tipo,
    ]);

    if ($ok) {
        respuestaOk(['mensaje' => 'Mensaje enviado correctamente.']);
    } else {
        respuestaError('Error al enviar el mensaje.');
    }
}

/*--------------------------------------------------------------------------------------------
PUT - marcar como leído (solo admin) */
if ($metodo === 'PUT') {
    requerirAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($data['idMensaje'])) {
        respuestaError('Falta idMensaje.', 422);
    }

    $leido = isset($data['leido']) ? (int)$data['leido'] : 1;
    $pdo->prepare('UPDATE mensajes_contacto SET leido = ? WHERE idMensaje = ?')
        ->execute([$leido, $data['idMensaje']]);

    respuestaOk(['mensaje' => 'Mensaje actualizado correctamente.']);
}

/*--------------------------------------------------------------------------------------------
DELETE - eliminar (solo admin) */
if ($metodo === 'DELETE') {
    requerirAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($data['idMensaje'])) {
        respuestaError('Falta idMensaje.', 422);
    }

    $pdo->prepare('DELETE FROM mensajes_contacto WHERE idMensaje = ?')
        ->execute([$data['idMensaje']]);

    respuestaOk(['mensaje' => 'Mensaje eliminado correctamente.']);
}

respuestaError('Método no permitido.', 405);