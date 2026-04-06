<?php
/*--------------------------------------------------------------------------------------------
admin/protectoras.php
CRUD de protectoras para el administrador
GET    — lista protectoras
POST   — crea protectora
PUT    — actualiza protectora
DELETE — desactiva protectora */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $stmt = $pdo->query(
        'SELECT p.idProtectora, p.nombre, p.localidad, p.telefono, p.email,
                p.web, p.foto_logo, p.verificada, p.activa, p.fecha_registro,
                COUNT(m.idMascota) AS num_animales
         FROM protectoras p
         LEFT JOIN mascotas m ON m.idProtectora = p.idProtectora AND m.activa = 1
         GROUP BY p.idProtectora
         ORDER BY p.nombre'
    );
    respuestaOk(['protectoras' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
POST - crear */
if ($metodo === 'POST') {
    $datos = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    $nombre = limpiar($datos['nombre'] ?? '');
    if (!$nombre) {
        respuestaError('El nombre de la protectora es obligatorio.');
    }

    if (!empty($datos['email']) && !validarEmail($datos['email'])) {
        respuestaError('Email no valido.');
    }

    // Foto logo opcional
    $rutaLogo = null;
    if (!empty($_FILES['foto_logo']) && $_FILES['foto_logo']['error'] === UPLOAD_ERR_OK) {
        $rutaLogo = subirLogoProtectora($_FILES['foto_logo']);
    }

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
        $rutaLogo,
        !empty($datos['latitud'])  ? (float)$datos['latitud']  : null,
        !empty($datos['longitud']) ? (float)$datos['longitud'] : null,
    ]);

    respuestaOk(['mensaje' => 'Protectora creada.', 'idProtectora' => $pdo->lastInsertId()]);
}

/*--------------------------------------------------------------------------------------------
PUT - actualizar */
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idProtectora'] ?? 0);

    if (!$id) {
        respuestaError('ID de protectora requerido.');
    }

    $stmt = $pdo->prepare('SELECT idProtectora FROM protectoras WHERE idProtectora = ? LIMIT 1');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        respuestaError('Protectora no encontrada.', 404);
    }

    if (!empty($datos['email']) && !validarEmail($datos['email'])) {
        respuestaError('Email no valido.');
    }

    $stmt = $pdo->prepare(
        'UPDATE protectoras SET
            nombre = ?, descripcion = ?, direccion = ?, localidad = ?,
            telefono = ?, email = ?, web = ?, latitud = ?, longitud = ?,
            verificada = ?, activa = ?
         WHERE idProtectora = ?'
    );
    $stmt->execute([
        limpiar($datos['nombre']      ?? ''),
        limpiar($datos['descripcion'] ?? ''),
        limpiar($datos['direccion']   ?? ''),
        limpiar($datos['localidad']   ?? ''),
        limpiar($datos['telefono']    ?? ''),
        $datos['email']     ?? null,
        $datos['web']       ?? null,
        !empty($datos['latitud'])  ? (float)$datos['latitud']  : null,
        !empty($datos['longitud']) ? (float)$datos['longitud'] : null,
        (int)($datos['verificada'] ?? 0),
        (int)($datos['activa']     ?? 1),
        $id,
    ]);

    respuestaOk(['mensaje' => 'Protectora actualizada.']);
}

/*--------------------------------------------------------------------------------------------
DELETE - borrado logico */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idProtectora'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        respuestaError('ID de protectora requerido.');
    }

    // No se puede eliminar si tiene mascotas activas
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM mascotas WHERE idProtectora = ? AND activa = 1'
    );
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        respuestaError('No se puede eliminar: la protectora tiene animales activos.');
    }

    $pdo->prepare('UPDATE protectoras SET activa = 0 WHERE idProtectora = ?')->execute([$id]);
    respuestaOk(['mensaje' => 'Protectora eliminada.']);
}

respuestaError('Metodo no permitido.', 405);

/*--------------------------------------------------------------------------------------------
funcion auxiliar subida de logo */
function subirLogoProtectora(array $archivo): ?string {
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension  = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $permitidos) || $archivo['size'] > 2 * 1024 * 1024) {
        return null;
    }

    $directorio = __DIR__ . '/../uploads/protectoras/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombre = uniqid('prot_', true) . '.' . $extension;
    move_uploaded_file($archivo['tmp_name'], $directorio . $nombre);
    return 'uploads/protectoras/' . $nombre;
}