<?php
/*--------------------------------------------------------------------------------------------
admin/mascotas.php
CRUD completo de mascotas para el administrador
GET    — lista todas las mascotas (incluyendo inactivas)
POST   — crea nueva mascota
PUT    — actualiza mascota existente
DELETE — desactiva mascota (borrado logico) */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar mascotas */
if ($metodo === 'GET') {
    $busqueda = limpiar($_GET['q']     ?? '');
    $especie  = $_GET['especie']       ?? 'todos';
    $estado   = $_GET['estado']        ?? 'todos';
    $pagina   = (int)($_GET['pagina']  ?? 1);
    $p        = paginacion($pagina, 20);

    $where  = ['1 = 1'];
    $params = [];

    if ($busqueda) {
        $where[]  = '(m.nombre LIKE ? OR m.raza LIKE ?)';
        $like     = '%' . $busqueda . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if (in_array($especie, ['perro', 'gato'])) {
        $where[]  = 'm.especie = ?';
        $params[] = $especie;
    }

    if (in_array($estado, ['disponible', 'en_proceso', 'adoptado'])) {
        $where[]  = 'm.estado_adopcion = ?';
        $params[] = $estado;
    }

    $condicion = implode(' AND ', $where);

    $total = $pdo->prepare("SELECT COUNT(*) FROM mascotas m WHERE $condicion");
    $total->execute($params);
    $total = (int)$total->fetchColumn();

    $params[] = $p['limite'];
    $params[] = $p['offset'];

    $stmt = $pdo->prepare(
        "SELECT m.idMascota, m.nombre, m.especie, m.raza, m.sexo, m.edad_texto,
                m.tamanyo, m.urgencia, m.estado_adopcion, m.activa,
                m.disponible_apadrinamiento, m.fecha_publicacion,
                p.nombre AS protectora_nombre,
                f.ruta   AS foto_principal
         FROM mascotas m
         JOIN protectoras p ON m.idProtectora = p.idProtectora
         LEFT JOIN mascotas_fotos f ON f.idMascota = m.idMascota AND f.es_principal = 1
         WHERE $condicion
         ORDER BY m.fecha_publicacion DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute($params);

    respuestaOk([
        'mascotas'    => $stmt->fetchAll(),
        'total'       => $total,
        'totalPaginas'=> (int)ceil($total / $p['limite']),
        'pagina'      => $p['pagina'],
    ]);
}

/*--------------------------------------------------------------------------------------------
POST - crear mascota */
if ($metodo === 'POST') {
    // Puede venir como multipart (con fotos) o JSON
    $datos = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    $campos = [
        'idProtectora', 'nombre', 'especie', 'raza', 'sexo',
        'edad_texto', 'tamanyo', 'color', 'descripcion', 'estado_salud',
        'urgencia', 'tiempo_en_adopcion',
    ];

    $obligatorios = ['idProtectora', 'nombre', 'especie', 'sexo', 'tamanyo'];
    foreach ($obligatorios as $campo) {
        if (empty($datos[$campo])) {
            respuestaError("El campo '$campo' es obligatorio.");
        }
    }

    $espe = $datos['especie'] ?? '';
    if (!in_array($espe, ['perro', 'gato'])) {
        respuestaError('Especie no valida.');
    }

    $tama = $datos['tamanyo'] ?? '';
    if (!in_array($tama, ['pequeño', 'mediano', 'grande'])) {
        respuestaError('Tamanyo no valido.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO mascotas
            (idProtectora, nombre, especie, raza, sexo, edad_texto, tamanyo,
             color, descripcion, estado_salud, urgencia, tiempo_en_adopcion,
             compatible_ninos, compatible_perros, compatible_gatos, apto_piso,
             vacunado, esterilizado, microchip, desparasitado, disponible_apadrinamiento,
             fecha_entrada)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    $stmt->execute([
        (int)$datos['idProtectora'],
        limpiar($datos['nombre']),
        $espe,
        limpiar($datos['raza']             ?? ''),
        $datos['sexo'],
        limpiar($datos['edad_texto']       ?? ''),
        $tama,
        limpiar($datos['color']            ?? ''),
        limpiar($datos['descripcion']      ?? ''),
        limpiar($datos['estado_salud']     ?? 'Bueno'),
        in_array($datos['urgencia'] ?? '', ['normal','urgente','nuevo']) ? $datos['urgencia'] : 'normal',
        limpiar($datos['tiempo_en_adopcion'] ?? ''),
        (int)($datos['compatible_ninos']  ?? 0),
        (int)($datos['compatible_perros'] ?? 0),
        (int)($datos['compatible_gatos']  ?? 0),
        (int)($datos['apto_piso']         ?? 0),
        (int)($datos['vacunado']          ?? 0),
        (int)($datos['esterilizado']      ?? 0),
        (int)($datos['microchip']         ?? 0),
        (int)($datos['desparasitado']     ?? 0),
        (int)($datos['disponible_apadrinamiento'] ?? 1),
        !empty($datos['fecha_entrada']) ? $datos['fecha_entrada'] : null,
    ]);

    $idNueva = (int)$pdo->lastInsertId();

    // Subida de fotos si las hay
    if (!empty($_FILES['fotos'])) {
        subirFotosMascota($pdo, $idNueva, $_FILES['fotos']);
    }

    respuestaOk(['mensaje' => 'Mascota creada correctamente.', 'idMascota' => $idNueva]);
}

/*--------------------------------------------------------------------------------------------
PUT - actualizar mascota */
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idMascota'] ?? 0);

    if (!$id) {
        respuestaError('ID de mascota requerido.');
    }

    // Comprobar que existe
    $stmt = $pdo->prepare('SELECT idMascota FROM mascotas WHERE idMascota = ? LIMIT 1');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        respuestaError('Mascota no encontrada.', 404);
    }

    $stmt = $pdo->prepare(
        'UPDATE mascotas SET
            idProtectora = ?, nombre = ?, especie = ?, raza = ?, sexo = ?,
            edad_texto = ?, tamanyo = ?, color = ?, descripcion = ?,
            estado_salud = ?, urgencia = ?, estado_adopcion = ?, tiempo_en_adopcion = ?,
            compatible_ninos = ?, compatible_perros = ?, compatible_gatos = ?, apto_piso = ?,
            vacunado = ?, esterilizado = ?, microchip = ?, desparasitado = ?,
            disponible_apadrinamiento = ?, activa = ?
         WHERE idMascota = ?'
    );

    $stmt->execute([
        (int)($datos['idProtectora']   ?? 1),
        limpiar($datos['nombre']       ?? ''),
        $datos['especie']              ?? 'perro',
        limpiar($datos['raza']         ?? ''),
        $datos['sexo']                 ?? 'macho',
        limpiar($datos['edad_texto']   ?? ''),
        $datos['tamanyo']              ?? 'mediano',
        limpiar($datos['color']        ?? ''),
        limpiar($datos['descripcion']  ?? ''),
        limpiar($datos['estado_salud'] ?? 'Bueno'),
        $datos['urgencia']             ?? 'normal',
        $datos['estado_adopcion']      ?? 'disponible',
        limpiar($datos['tiempo_en_adopcion'] ?? ''),
        (int)($datos['compatible_ninos']  ?? 0),
        (int)($datos['compatible_perros'] ?? 0),
        (int)($datos['compatible_gatos']  ?? 0),
        (int)($datos['apto_piso']         ?? 0),
        (int)($datos['vacunado']          ?? 0),
        (int)($datos['esterilizado']      ?? 0),
        (int)($datos['microchip']         ?? 0),
        (int)($datos['desparasitado']     ?? 0),
        (int)($datos['disponible_apadrinamiento'] ?? 1),
        (int)($datos['activa']            ?? 1),
        $id,
    ]);

    respuestaOk(['mensaje' => 'Mascota actualizada correctamente.']);
}

/*--------------------------------------------------------------------------------------------
DELETE - borrado logico */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idMascota'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        respuestaError('ID de mascota requerido.');
    }

    // No se puede eliminar si tiene apadrinamientos activos
    $stmt = $pdo->prepare(
        'SELECT idApadrinamiento FROM apadrinamientos
         WHERE idMascota = ? AND estado = "activo" LIMIT 1'
    );
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        respuestaError('No se puede eliminar: la mascota tiene apadrinamientos activos.');
    }

    $pdo->prepare('UPDATE mascotas SET activa = 0 WHERE idMascota = ?')->execute([$id]);

    respuestaOk(['mensaje' => 'Mascota eliminada correctamente.']);
}

respuestaError('Metodo no permitido.', 405);

/*--------------------------------------------------------------------------------------------
funcion auxiliar: subir fotos de mascota */
function subirFotosMascota(PDO $pdo, int $idMascota, array $archivos): void {
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $directorio = __DIR__ . '/../uploads/mascotas/';

    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    // Normalizar estructura de $_FILES para multiples archivos
    $fotos = [];
    if (is_array($archivos['name'])) {
        foreach ($archivos['name'] as $i => $nombre) {
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                $fotos[] = [
                    'name'     => $nombre,
                    'tmp_name' => $archivos['tmp_name'][$i],
                    'size'     => $archivos['size'][$i],
                ];
            }
        }
    } else {
        if ($archivos['error'] === UPLOAD_ERR_OK) {
            $fotos[] = $archivos;
        }
    }

    $esPrimera = true;
    $orden     = 0;

    // Verificar si ya tiene foto principal
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mascotas_fotos WHERE idMascota = ? AND es_principal = 1');
    $stmt->execute([$idMascota]);
    if ((int)$stmt->fetchColumn() > 0) {
        $esPrimera = false;
    }

    foreach ($fotos as $foto) {
        $extension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $permitidos)) {
            continue;
        }
        if ($foto['size'] > 5 * 1024 * 1024) {
            continue;
        }

        $nombreArchivo = uniqid('mascota_', true) . '.' . $extension;
        move_uploaded_file($foto['tmp_name'], $directorio . $nombreArchivo);

        $pdo->prepare(
            'INSERT INTO mascotas_fotos (idMascota, ruta, es_principal, orden)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $idMascota,
            'uploads/mascotas/' . $nombreArchivo,
            $esPrimera ? 1 : 0,
            $orden,
        ]);

        $esPrimera = false;
        $orden++;
    }
}