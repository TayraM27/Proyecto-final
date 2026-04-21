<?php
/*--------------------------------------------------------------------------------------------
GET    — lista protectoras con búsqueda
POST   — crea protectora
PUT    — actualiza protectora
DELETE — elimina protectora (soft delete, solo si no tiene animales activos) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdmin();

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $q = limpiar($_GET['q'] ?? '');

    $where  = [];
    $params = [];

    if ($q) {
        $where[]  = '(p.nombre LIKE ? OR p.localidad LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    $cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT p.idProtectora, p.nombre, p.localidad, p.telefono, p.email,
                   p.web, p.foto_logo, p.descripcion, p.direccion,
                   p.latitud, p.longitud, p.verificada, p.activa, p.fecha_registro,
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
POST */
if ($metodo === 'POST') {
    $datos  = json_decode(file_get_contents('php://input'), true) ?? [];
    $nombre = limpiar($datos['nombre'] ?? '');

    if (!$nombre) respuestaError('El nombre es obligatorio.');
    if (!empty($datos['email']) && !validarEmail($datos['email'])) respuestaError('Email no válido.');

    $stmt = $pdo->prepare(
        'INSERT INTO protectoras
            (nombre, descripcion, direccion, localidad, telefono, email, web, foto_logo, latitud, longitud)
         VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $nombre,
        limpiar($datos['descripcion'] ?? ''),
        limpiar($datos['direccion']   ?? ''),
        limpiar($datos['localidad']   ?? ''),
        limpiar($datos['telefono']    ?? ''),
        $datos['email']    ?? null,
        $datos['web']      ?? null,
        $datos['foto_logo']?? null,
        !empty($datos['latitud'])  ? (float)$datos['latitud']  : null,
        !empty($datos['longitud']) ? (float)$datos['longitud'] : null,
    ]);

    respuestaOk(['mensaje' => 'Protectora creada.', 'idProtectora' => (int)$pdo->lastInsertId()]);
}

/*--------------------------------------------------------------------------------------------
PUT */
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idProtectora'] ?? 0);
    if (!$id) respuestaError('idProtectora requerido.');
    if (!empty($datos['email']) && !validarEmail($datos['email'])) respuestaError('Email no válido.');

    $pdo->prepare(
        'UPDATE protectoras SET
            nombre=?, descripcion=?, direccion=?, localidad=?,
            telefono=?, email=?, web=?, foto_logo=?,
            latitud=?, longitud=?, verificada=?, activa=?
         WHERE idProtectora=?'
    )->execute([
        limpiar($datos['nombre']      ?? ''),
        limpiar($datos['descripcion'] ?? ''),
        limpiar($datos['direccion']   ?? ''),
        limpiar($datos['localidad']   ?? ''),
        limpiar($datos['telefono']    ?? ''),
        $datos['email']    ?? null,
        $datos['web']      ?? null,
        $datos['foto_logo']?? null,
        !empty($datos['latitud'])  ? (float)$datos['latitud']  : null,
        !empty($datos['longitud']) ? (float)$datos['longitud'] : null,
        (int)($datos['verificada'] ?? 0),
        (int)($datos['activa']     ?? 1),
        $id,
    ]);

    respuestaOk(['mensaje' => 'Protectora actualizada.']);
}

/*--------------------------------------------------------------------------------------------
DELETE */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idProtectora'] ?? 0);
    if (!$id) respuestaError('idProtectora requerido.');

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mascotas WHERE idProtectora = ? AND activa = 1');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        respuestaError('No se puede eliminar: la protectora tiene animales activos.');
    }

    $pdo->prepare('UPDATE protectoras SET activa = 0 WHERE idProtectora = ?')->execute([$id]);
    respuestaOk(['mensaje' => 'Protectora eliminada.']);
}

respuestaError('Método no permitido.', 405);