<?php
/*--------------------------------------------------------------------------------------------
GET    — lista mascotas con filtros y paginación
POST   — crea mascota (acepta multipart con fotos)
PUT    — actualiza mascota
DELETE — elimina (soft delete) mascota */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdmin();

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $q      = limpiar($_GET['q']       ?? '');
    $especie= $_GET['especie']         ?? 'todos';
    $estado = $_GET['estado']          ?? 'todos';
    $pagina = (int)($_GET['pagina']    ?? 1);
    $p      = paginacion($pagina, 15);

    $where  = ['m.activa = 1'];
    $params = [];

    if ($q) {
        $where[]  = '(m.nombre LIKE ? OR m.raza LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($especie !== 'todos') { $where[] = 'm.especie = ?'; $params[] = $especie; }
    if ($estado  !== 'todos') { $where[] = 'm.estado_adopcion = ?'; $params[] = $estado; }

    $cond = implode(' AND ', $where);

    $total = (int)$pdo->prepare("SELECT COUNT(*) FROM mascotas m WHERE $cond")->execute($params) ?
             $pdo->prepare("SELECT COUNT(*) FROM mascotas m WHERE $cond")->execute($params) : 0;
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM mascotas m WHERE $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT m.idMascota, m.idProtectora, m.nombre, m.especie, m.raza, m.sexo,
                   m.tamanyo, m.color, m.descripcion, m.estado_salud, m.urgencia,
                   m.estado_adopcion, m.edad_texto, m.badge_extra,
                   m.compatible_ninos, m.compatible_perros, m.compatible_gatos,
                   m.apto_piso, m.vacunado, m.esterilizado, m.microchip, m.desparasitado,
                   m.disponible_apadrinamiento, m.disponible_acogida, m.activa,
                   p.nombre AS protectora_nombre,
                   (SELECT mf.ruta FROM mascotas_fotos mf WHERE mf.idMascota=m.idMascota AND mf.es_principal=1 LIMIT 1) AS foto_principal
            FROM mascotas m
            JOIN protectoras p ON m.idProtectora = p.idProtectora
            WHERE $cond
            ORDER BY m.idMascota DESC
            LIMIT ? OFFSET ?";

    $params[] = $p['limite'];
    $params[] = $p['offset'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk([
        'mascotas'    => $stmt->fetchAll(),
        'total'       => $total,
        'pagina'      => $p['pagina'],
        'totalPaginas'=> (int)ceil($total / $p['limite']),
    ]);
}

/*--------------------------------------------------------------------------------------------
POST — crear mascota (JSON o multipart con fotos) */
if ($metodo === 'POST') {
    $esMultipart = !empty($_POST);
    $datos = $esMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    if (empty($datos['nombre']))       respuestaError('El nombre es obligatorio.');
    if (empty($datos['idProtectora'])) respuestaError('La protectora es obligatoria.');

    /* Columnas base siempre presentes */
    $colsList   = [];
    $valsList   = [];
    $execParams = [];

    $base = [
        'idProtectora'            => (int)$datos['idProtectora'],
        'nombre'                  => limpiar($datos['nombre']),
        'especie'                 => $datos['especie']          ?? 'perro',
        'raza'                    => limpiar($datos['raza']     ?? ''),
        'sexo'                    => $datos['sexo']             ?? 'macho',
        'tamanyo'                 => $datos['tamanyo']          ?? 'mediano',
        'color'                   => limpiar($datos['color']    ?? ''),
        'descripcion'             => limpiar($datos['descripcion']  ?? ''),
        'estado_salud'            => limpiar($datos['estado_salud'] ?? ''),
        'urgencia'                => $datos['urgencia']         ?? 'normal',
        'estado_adopcion'         => $datos['estado_adopcion']  ?? 'disponible',
        'compatible_ninos'        => (int)($datos['compatible_ninos']  ?? 0),
        'compatible_perros'       => (int)($datos['compatible_perros'] ?? 0),
        'compatible_gatos'        => (int)($datos['compatible_gatos']  ?? 0),
        'apto_piso'               => (int)($datos['apto_piso']         ?? 0),
        'vacunado'                => (int)($datos['vacunado']          ?? 0),
        'esterilizado'            => (int)($datos['esterilizado']      ?? 0),
        'microchip'               => (int)($datos['microchip']         ?? 0),
        'desparasitado'           => (int)($datos['desparasitado']     ?? 0),
        'disponible_apadrinamiento' => (int)($datos['disponible_apadrinamiento'] ?? 1),
    ];

    /* Columnas opcionales — verificar si existen */
    $opcionales = [
        'edad_texto'         => limpiar($datos['edad_texto']         ?? ''),
        'badge_extra'        => limpiar($datos['badge_extra']        ?? ''),
        'disponible_acogida' => (int)($datos['disponible_acogida']   ?? 1),
    ];
    foreach ($opcionales as $col => $val) {
        try {
            $pdo->query("SELECT `$col` FROM mascotas LIMIT 1");
            $base[$col] = $val;
        } catch (Exception $e) {}
    }

    foreach ($base as $col => $val) {
        $colsList[]   = "`$col`";
        $valsList[]   = '?';
        $execParams[] = $val;
    }

    /* activa siempre = 1 */
    $colsList[]   = '`activa`';
    $valsList[]   = '1';

    $sql = 'INSERT INTO mascotas (' . implode(', ', $colsList) . ') VALUES (' . implode(', ', $valsList) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($execParams);

    $idMascota = (int)$pdo->lastInsertId();

    /* Guardar fotos si se adjuntaron */
    if (!empty($_FILES['fotos'])) {
        $archivos = $_FILES['fotos'];
        $total    = count($archivos['name']);
        $destDir  = __DIR__ . '/../../img/mascotas/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        for ($i = 0; $i < $total; $i++) {
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) continue;
            $mime = mime_content_type($archivos['tmp_name'][$i]);
            if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'])) continue;
            $ext    = strtolower(pathinfo($archivos['name'][$i], PATHINFO_EXTENSION));
            $nombre = 'mascota_' . $idMascota . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($archivos['tmp_name'][$i], $destDir . $nombre)) {
                $esPrincipal = ($i === 0) ? 1 : 0;
                $pdo->prepare('INSERT INTO mascotas_fotos (idMascota, ruta, es_principal, orden) VALUES (?,?,?,?)')
                    ->execute([$idMascota, 'img/mascotas/' . $nombre, $esPrincipal, $i]);
            }
        }
    }

    respuestaOk(['mensaje' => 'Mascota creada correctamente.', 'idMascota' => $idMascota]);
}

/*--------------------------------------------------------------------------------------------
PUT — actualizar mascota */
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idMascota'] ?? 0);
    if (!$id) respuestaError('idMascota requerido.');

    $pdo->prepare(
        'UPDATE mascotas SET
         idProtectora=?, nombre=?, especie=?, raza=?, sexo=?, tamanyo=?, color=?,
         descripcion=?, estado_salud=?, badge_extra=?, urgencia=?, estado_adopcion=?, edad_texto=?,
         compatible_ninos=?, compatible_perros=?, compatible_gatos=?,
         apto_piso=?, vacunado=?, esterilizado=?, microchip=?, desparasitado=?,
         disponible_apadrinamiento=?, disponible_acogida=?
         WHERE idMascota=?'
    )->execute([
        (int)($datos['idProtectora'] ?? 0),
        limpiar($datos['nombre']      ?? ''),
        $datos['especie']             ?? 'perro',
        limpiar($datos['raza']        ?? ''),
        $datos['sexo']                ?? 'macho',
        $datos['tamanyo']             ?? 'mediano',
        limpiar($datos['color']       ?? ''),
        limpiar($datos['descripcion'] ?? ''),
        limpiar($datos['estado_salud']?? ''),
        limpiar($datos['badge_extra']   ?? ''),
        $datos['urgencia']            ?? 'normal',
        $datos['estado_adopcion']     ?? 'disponible',
        limpiar($datos['edad_texto']  ?? ''),
        (int)($datos['compatible_ninos']  ?? 0),
        (int)($datos['compatible_perros'] ?? 0),
        (int)($datos['compatible_gatos']  ?? 0),
        (int)($datos['apto_piso']         ?? 0),
        (int)($datos['vacunado']          ?? 0),
        (int)($datos['esterilizado']      ?? 0),
        (int)($datos['microchip']         ?? 0),
        (int)($datos['desparasitado']     ?? 0),
        (int)($datos['disponible_apadrinamiento'] ?? 1),
        (int)($datos['disponible_acogida']         ?? 1),
        $id,
    ]);

    respuestaOk(['mensaje' => 'Mascota actualizada correctamente.']);
}

/*--------------------------------------------------------------------------------------------
DELETE — soft delete */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idMascota'] ?? 0);
    if (!$id) respuestaError('idMascota requerido.');
    $pdo->prepare('UPDATE mascotas SET activa = 0 WHERE idMascota = ?')->execute([$id]);
    respuestaOk(['mensaje' => 'Mascota eliminada.']);
}

respuestaError('Método no permitido.', 405);