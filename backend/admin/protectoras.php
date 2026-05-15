<?php
ob_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);
/*--------------------------------------------------------------------------------------------
GET    — lista protectoras
POST   — crea protectora
PUT    — actualiza protectora
DELETE — elimina protectora */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdminOProtectora();

$pdo = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id) {
        $sql = "SELECT p.idProtectora, p.nombre, p.descripcion, p.descripcion_dona,
                       p.direccion, p.localidad, p.telefono, p.email,
                       p.web, p.tipo_pagina, p.red_social_url, p.especie_atencion,
                       p.iban, p.bizum, p.teaming, p.badges,
                       p.foto_logo, p.latitud, p.longitud,
                       p.verificada, p.activa, p.fecha_registro,
                       COUNT(m.idMascota) AS num_animales
                FROM protectoras p
                LEFT JOIN mascotas m ON m.idProtectora = p.idProtectora AND m.activa = 1
                WHERE p.idProtectora = ?
                GROUP BY p.idProtectora";
        $params = [$id];
        if (!$esAdmin && $idProtectoraUsuario) {
            $sql .= ' AND p.idProtectora = ?';
            $params[] = $idProtectoraUsuario;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $protectora = $stmt->fetch();
        if (!$protectora) respuestaError('Protectora no encontrada.', 404);
        respuestaOk(['protectora' => $protectora]);
    }

    $q      = limpiar($_GET['q']      ?? '');
    $nombre = limpiar($_GET['nombre'] ?? '');
    $todos  = !empty($_GET['todos'])  ? 1 : 0;

    $where  = $todos ? [] : ['p.activa = 1'];
    $params = [];

    if ($q) {
        $where[]  = 'p.localidad LIKE ?';
        $params[] = "%$q%";
    }
    if ($nombre) {
        $where[]  = 'p.nombre LIKE ?';
        $params[] = "%$nombre%";
    }

    $cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT p.idProtectora, p.nombre, p.descripcion, p.descripcion_dona,
                   p.direccion, p.localidad, p.telefono, p.email,
                   p.web, p.tipo_pagina, p.red_social_url, p.especie_atencion,
                   p.iban, p.bizum, p.teaming, p.badges,
                   p.foto_logo, p.latitud, p.longitud,
                   p.verificada, p.activa, p.fecha_registro,
                   COUNT(m.idMascota) AS num_animales
            FROM protectoras p
            LEFT JOIN mascotas m ON m.idProtectora = p.idProtectora AND m.activa = 1
            $cond
            GROUP BY p.idProtectora
            ORDER BY p.nombre";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk(['protectoras' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
POST — crea protectora (SOLO REGISTRO PUBLICO, admin NO puede crear) */
if ($metodo === 'POST') {
    respuestaError('Las protectoras se registran por su cuenta. Los administradores no pueden crear protectoras.', 403);
}

/*--------------------------------------------------------------------------------------------
PUT — actualizar protectora (protectora edita sus datos, admin solo moderacion) */
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idProtectora'] ?? 0);
    if (!$id) respuestaError('idProtectora requerido.');

    if ($esAdmin) {
        /* Soporta accion directa (suspender/reactivar) desde el panel */
        $accion = $datos['accion'] ?? '';
        if ($accion === 'suspender') {
            $pdo->prepare('UPDATE protectoras SET activa = 0 WHERE idProtectora = ?')->execute([$id]);
            $pdo->prepare('INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, reason) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([(int)($_SESSION['idUsuario'] ?? 0), 'admin', 'suspender_protectora', 'protectora', $id, 'Suspendida por admin']);
            respuestaOk(['mensaje' => 'Protectora suspendida.']);
        }
        if ($accion === 'reactivar') {
            $pdo->prepare('UPDATE protectoras SET activa = 1 WHERE idProtectora = ?')->execute([$id]);
            $pdo->prepare('INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, reason) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([(int)($_SESSION['idUsuario'] ?? 0), 'admin', 'reactivar_protectora', 'protectora', $id, 'Reactivada por admin']);
            respuestaOk(['mensaje' => 'Protectora reactivada.']);
        }
        respuestaError('Acción no permitida. El admin no puede editar la información de la protectora.', 403);
    } else {
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
        if ($id !== $idProtectoraUsuario) respuestaError('No puedes editar otra protectora.', 403);
        if (!empty($datos['email']) && !validarEmail($datos['email'])) respuestaError('Email no válido.');
        if (!empty($datos['telefono']) && !preg_match('/^\d{9}$/', limpiar($datos['telefono']))) respuestaError('El teléfono debe tener exactamente 9 dígitos.');

        $pdo->prepare(
            'UPDATE protectoras SET
                nombre=?, descripcion=?, descripcion_dona=?, direccion=?, localidad=?,
                telefono=?, email=?, web=?, tipo_pagina=?,
                red_social_url=?, especie_atencion=?,
                iban=?, bizum=?, teaming=?, badges=?,
                foto_logo=?, url_formulario_acogida=?, latitud=?, longitud=?
             WHERE idProtectora=?'
        )->execute([
            limpiar($datos['nombre']           ?? ''),
            limpiar($datos['descripcion']      ?? ''),
            limpiar($datos['descripcion_dona'] ?? ''),
            limpiar($datos['direccion']        ?? ''),
            limpiar($datos['localidad']        ?? ''),
            limpiar($datos['telefono']         ?? ''),
            !empty($datos['email'])         ? $datos['email']          : null,
            !empty($datos['web'])           ? $datos['web']            : null,
            $datos['tipo_pagina']           ?? 'sin_pagina',
            !empty($datos['red_social_url'])? $datos['red_social_url'] : null,
            $datos['especie_atencion']      ?? 'ambos',
            !empty($datos['iban'])          ? limpiar($datos['iban'])  : null,
            !empty($datos['bizum'])         ? limpiar($datos['bizum']) : null,
            !empty($datos['teaming'])       ? $datos['teaming']        : null,
            $datos['badges']               ?? '',
            !empty($datos['foto_logo'])     ? $datos['foto_logo']      : null,
            !empty($datos['url_formulario_acogida']) ? $datos['url_formulario_acogida'] : null,
            !empty($datos['latitud'])       ? (float)$datos['latitud'] : null,
            !empty($datos['longitud'])      ? (float)$datos['longitud']: null,
            $id,
        ]);

        respuestaOk(['mensaje' => 'Protectora actualizada.']);
    }
}

/*--------------------------------------------------------------------------------------------
DELETE — soft delete (solo admin con motivo y auditoria, protectora NO puede eliminarse) */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idProtectora'] ?? 0);
    $motivo = limpiar($datos['motivo'] ?? '');

    if (!$esAdmin) respuestaError('Las protectoras no pueden eliminarse. Contacta con administración.', 403);
    if (!$id) respuestaError('idProtectora requerido.');
    if (!$motivo) respuestaError('El administrador debe indicar un motivo para desactivar esta protectora.');

    $stmt = $pdo->prepare('SELECT idProtectora, nombre FROM protectoras WHERE idProtectora = ?');
    $stmt->execute([$id]);
    $prot = $stmt->fetch();
    if (!$prot) respuestaError('Protectora no encontrada.', 404);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mascotas WHERE idProtectora = ? AND activa = 1');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        respuestaError('No se puede desactivar: la protectora tiene animales activos. Primero elimina o desactiva todos los animales.');
    }

    $pdo->prepare('UPDATE protectoras SET activa = 0 WHERE idProtectora = ?')->execute([$id]);

    $pdo->prepare(
        'INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, reason)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        (int)($_SESSION['idUsuario'] ?? 0),
        'admin',
        'soft_delete_protectora',
        'protectora',
        $id,
        $motivo,
    ]);

    respuestaOk(['mensaje' => 'Protectora desactivada por moderación.']);
}

respuestaError('Método no permitido.', 405);