<?php
/*--------------------------------------------------------------------------------------------
GET    — lista mascotas (admin lectura global, protectora solo las suyas)
POST   — crea mascota (SOLO protectora, NO admin)
PUT    — actualiza mascota (SOLO protectora sobre las suyas, NO admin)
DELETE — soft delete (protectora sobre las suyas, admin con motivo y auditoría)
FOTOS:
  POST ?accion=fotos  — sube nuevas fotos (SOLO protectora)
  PUT  ?accion=foto   — actualiza foto (es_principal, eliminar) (SOLO protectora) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesionSegura();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'protectora'])) {
    respuestaError('Acceso restringido.', 403);
}

define('LIMITE_PRIORITARIAS', 2);

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();
$idUsuario = (int)($_SESSION['idUsuario'] ?? 0);

$accion = $_GET['accion'] ?? '';

if ($metodo === 'POST' && $accion === 'fotos') {
    $idMascota = (int)($_POST['idMascota'] ?? 0);
    if (!$idMascota) respuestaError('idMascota requerido.');

    $check = $pdo->prepare('SELECT idMascota FROM mascotas WHERE idMascota = ? AND idProtectora = ?');
    $check->execute([$idMascota, $idProtectoraUsuario]);
    if (!$check->fetch()) respuestaError('No tienes permiso para gestionar esta mascota.', 403);

    if (empty($_FILES['fotos'])) respuestaError('No se recibieron archivos.');

    $archivos = $_FILES['fotos'];
    $total    = count($archivos['name']);
    $destDir  = __DIR__ . '/../../img/mascotas/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM mascotas_fotos WHERE idMascota = ?');
    $stmtCount->execute([$idMascota]);
    $orden = (int)$stmtCount->fetchColumn();

    $stmtInsert = $pdo->prepare('INSERT INTO mascotas_fotos (idMascota, ruta, es_principal, orden) VALUES (?,?,?,?)');
    $subidas = 0;
    for ($i = 0; $i < $total; $i++) {
        if ($archivos['error'][$i] !== UPLOAD_ERR_OK) continue;
        $mime = mime_content_type($archivos['tmp_name'][$i]);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'])) continue;
        $ext    = strtolower(pathinfo($archivos['name'][$i], PATHINFO_EXTENSION));
        $nombre = 'mascota_' . $idMascota . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($archivos['tmp_name'][$i], $destDir . $nombre)) {
            $stmtInsert->execute([$idMascota, 'img/mascotas/' . $nombre, 0, $orden + $i]);
            $subidas++;
        }
    }
    respuestaOk(['mensaje' => $subidas . ' foto(s) subida(s).']);
}

if ($metodo === 'PUT' && $accion === 'foto') {
    if ($esAdmin) respuestaError('Acción no permitida.', 403);

    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $idFoto = (int)($datos['idFoto'] ?? 0);
    if (!$idFoto) respuestaError('idFoto requerido.');

    $check = $pdo->prepare('SELECT f.idFoto, f.idMascota, f.ruta, f.es_principal, m.idProtectora
                            FROM mascotas_fotos f JOIN mascotas m ON f.idMascota = m.idMascota WHERE f.idFoto = ?');
    $check->execute([$idFoto]);
    $foto = $check->fetch();
    if (!$foto) respuestaError('Foto no encontrada.', 404);
    if ($foto['idProtectora'] != $idProtectoraUsuario) respuestaError('No tienes permiso.', 403);

    if (!empty($datos['eliminar'])) {
        $rutaFisica = __DIR__ . '/../../' . $foto['ruta'];
        if (file_exists($rutaFisica)) @unlink($rutaFisica);
        $pdo->prepare('DELETE FROM mascotas_fotos WHERE idFoto = ?')->execute([$idFoto]);
        respuestaOk(['mensaje' => 'Foto eliminada.']);
    }

    if (isset($datos['es_principal'])) {
        $pdo->prepare('UPDATE mascotas_fotos SET es_principal = 0 WHERE idMascota = ?')->execute([$foto['idMascota']]);
        $pdo->prepare('UPDATE mascotas_fotos SET es_principal = 1 WHERE idFoto = ?')->execute([$idFoto]);
        respuestaOk(['mensaje' => 'Foto principal actualizada.']);
    }

    respuestaError('Acción no reconocida.');
}

session_write_close();

/*--------------------------------------------------------------------------------------------
GET — admin: lectura global con filtros | protectora: solo sus mascotas */
if ($metodo === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id) {
        $sql = "SELECT m.*, p.nombre AS protectora_nombre,
                       (SELECT mf.ruta FROM mascotas_fotos mf WHERE mf.idMascota=m.idMascota AND mf.es_principal=1 LIMIT 1) AS foto_principal
                FROM mascotas m
                JOIN protectoras p ON m.idProtectora = p.idProtectora
                WHERE m.idMascota = ?";
        $params = [$id];

        if (!$esAdmin && $idProtectoraUsuario) {
            $sql .= ' AND m.idProtectora = ?';
            $params[] = $idProtectoraUsuario;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $mascota = $stmt->fetch();
        if (!$mascota) respuestaError('Mascota no encontrada.', 404);
        $stmtF = $pdo->prepare('SELECT idFoto, ruta, es_principal, orden FROM mascotas_fotos WHERE idMascota = ? ORDER BY orden, idFoto');
        $stmtF->execute([$id]);
        $mascota['fotos'] = $stmtF->fetchAll();
        respuestaOk(['mascota' => $mascota]);
    }

    $q      = limpiar($_GET['q']       ?? '');
    $especie= $_GET['especie']         ?? 'todos';
    $estado = $_GET['estado']          ?? 'todos';
    $todos  = !empty($_GET['todos'])   ? 1 : 0;
    $pagina = (int)($_GET['pagina']    ?? 1);
    $p      = pagina($pagina, 15);

    $where  = $todos ? [] : ['m.activa = 1'];
    $params = [];

    if (!$esAdmin && $idProtectoraUsuario) {
        $where[] = 'm.idProtectora = ?';
        $params[] = $idProtectoraUsuario;
    }

    if ($q) {
        $where[]  = '(m.nombre LIKE ? OR m.raza LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($especie !== 'todos') { $where[] = 'm.especie = ?'; $params[] = $especie; }
    if ($estado  !== 'todos') { $where[] = 'm.estado_adopcion = ?'; $params[] = $estado; }

    $cond = $where ? implode(' AND ', $where) : '1';

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM mascotas m WHERE $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT m.idMascota, m.idProtectora, m.nombre, m.especie, m.raza, m.sexo,
                   m.tamanyo, m.color, m.descripcion, m.estado_salud, m.urgencia,
                   m.estado_adopcion, m.edad_texto, m.badge_extra,
                   m.compatible_ninos, m.compatible_perros, m.compatible_gatos,
                   m.apto_piso, m.vacunado, m.esterilizado, m.microchip, m.desparasitado,
                   m.disponible_apadrinamiento, m.disponible_acogida, m.prioritaria, m.fecha_prioritaria, m.activa, m.descripcion_slider,
                   m.fecha_entrada, m.fecha_nacimiento,
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
POST — crear mascota (SOLO PROTECTORA, admin NO puede crear) */
if ($metodo === 'POST') {
    if ($esAdmin) {
        respuestaError('Los administradores no pueden crear mascotas. Esta acción corresponde a la protectora.', 403);
    }

    if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

    $esMultipart = !empty($_POST);
    $datos = $esMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    if (empty($datos['nombre'])) respuestaError('El nombre es obligatorio.');

    $base = [
        'idProtectora'              => $idProtectoraUsuario,
        'nombre'                    => limpiar($datos['nombre']),
        'especie'                   => $datos['especie']              ?? 'perro',
        'raza'                      => limpiar($datos['raza']         ?? ''),
        'sexo'                      => $datos['sexo']                 ?? 'macho',
        'tamanyo'                   => $datos['tamanyo']              ?? 'mediano',
        'color'                     => limpiar($datos['color']        ?? ''),
        'descripcion'               => limpiar($datos['descripcion']  ?? ''),
        'estado_salud'              => limpiar($datos['estado_salud'] ?? ''),
        'urgencia'                  => $datos['urgencia']             ?? 'normal',
        'estado_adopcion'           => $datos['estado_adopcion']      ?? 'disponible',
        'compatible_ninos'          => (int)($datos['compatible_ninos']  ?? 0),
        'compatible_perros'         => (int)($datos['compatible_perros'] ?? 0),
        'compatible_gatos'          => (int)($datos['compatible_gatos']  ?? 0),
        'apto_piso'                 => (int)($datos['apto_piso']         ?? 0),
        'vacunado'                  => (int)($datos['vacunado']          ?? 0),
        'esterilizado'              => (int)($datos['esterilizado']      ?? 0),
        'microchip'                 => (int)($datos['microchip']         ?? 0),
        'desparasitado'             => (int)($datos['desparasitado']     ?? 0),
        'disponible_apadrinamiento' => (int)($datos['disponible_apadrinamiento'] ?? 1),
        'edad_texto'                => limpiar($datos['edad_texto']      ?? ''),
        'badge_extra'               => limpiar($datos['badge_extra']     ?? ''),
        'disponible_acogida'        => (int)($datos['disponible_acogida'] ?? 1),
        'prioritaria'               => (int)($datos['prioritaria']        ?? 0),
        'descripcion_slider'        => limpiar($datos['descripcion_slider'] ?? ''),
        'fecha_entrada'             => !empty($datos['fecha_entrada'])    ? $datos['fecha_entrada']    : null,
        'fecha_nacimiento'          => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
    ];

    $cols   = [];
    $vals   = [];
    $params = [];

    foreach ($base as $col => $val) {
        $cols[]   = "`$col`";
        $vals[]   = '?';
        $params[] = $val;
    }

    $cols[]   = '`activa`';
    $vals[]   = '1';

    $stmt = $pdo->prepare('INSERT INTO mascotas (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')');
    $stmt->execute($params);
    $idMascota = (int)$pdo->lastInsertId();

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
                $pdo->prepare('INSERT INTO mascotas_fotos (idMascota, ruta, es_principal, orden) VALUES (?,?,?,?)')
                    ->execute([$idMascota, 'img/mascotas/' . $nombre, ($i === 0 ? 1 : 0), $i]);
            }
        }
    }

    respuestaOk(['mensaje' => 'Mascota creada correctamente.', 'idMascota' => $idMascota]);
}

/*--------------------------------------------------------------------------------------------
PUT — actualizar mascota (SOLO PROTECTORA sobre las suyas, admin NO puede editar) */
if ($metodo === 'PUT') {
    if ($esAdmin) {
        respuestaError('Los administradores no pueden editar mascotas. Esta acción corresponde a la protectora.', 403);
    }

    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idMascota'] ?? 0);
    if (!$id) respuestaError('idMascota requerido.');
    
    $idProtectoraCheck = $idProtectoraUsuario ? $idProtectoraUsuario : (int)($datos['idProtectora'] ?? 0);
    if (!$idProtectoraCheck) {
        respuestaError('No tienes una protectora asignada.');
    }
    
    $check = $pdo->prepare('SELECT idMascota, prioritaria, fecha_prioritaria FROM mascotas WHERE idMascota = ? AND idProtectora = ?');
    $check->execute([$id, $idProtectoraCheck]);
    $estadoActualRow = $check->fetch();
    if (!$estadoActualRow) respuestaError('No tienes permiso sobre esta mascota.', 403);

    $nuevaPrioritaria = (int)($datos['prioritaria'] ?? 0);
    $eraPrioritaria   = (int)$estadoActualRow['prioritaria'];

    if ($nuevaPrioritaria === 1 && $eraPrioritaria === 0) {
        $stmtC = $pdo->prepare('SELECT COUNT(*) FROM mascotas WHERE idProtectora = ? AND prioritaria = 1 AND activa = 1 AND idMascota != ?');
        $stmtC->execute([$idProtectoraCheck, $id]);
        if ((int)$stmtC->fetchColumn() >= LIMITE_PRIORITARIAS) {
            respuestaError('Has alcanzado el límite de mascotas urgentes. Desmarca otra para continuar.', 400);
        }
    }

    if ($nuevaPrioritaria === 1 && $eraPrioritaria === 0) {
        $fechaPrioritaria = date('Y-m-d H:i:s');
    } elseif ($nuevaPrioritaria === 0) {
        $fechaPrioritaria = null;
    } else {
        $fechaPrioritaria = $estadoActualRow['fecha_prioritaria'];
    }

    $pdo->prepare('UPDATE mascotas SET
     nombre=?, especie=?, raza=?, sexo=?, tamanyo=?, color=?,
     descripcion=?, estado_salud=?, badge_extra=?, urgencia=?, estado_adopcion=?, edad_texto=?,
     compatible_ninos=?, compatible_perros=?, compatible_gatos=?,
     apto_piso=?, vacunado=?, esterilizado=?, microchip=?, desparasitado=?,
     disponible_apadrinamiento=?, disponible_acogida=?, prioritaria=?, fecha_prioritaria=?, descripcion_slider=?,
     fecha_entrada=?, fecha_nacimiento=?
     WHERE idMascota=? AND idProtectora=?'
    )->execute([
        limpiar($datos['nombre']      ?? ''),
        $datos['especie']             ?? 'perro',
        limpiar($datos['raza']        ?? ''),
        $datos['sexo']                ?? 'macho',
        $datos['tamanyo']             ?? 'mediano',
        limpiar($datos['color']       ?? ''),
        limpiar($datos['descripcion'] ?? ''),
        limpiar($datos['estado_salud']?? ''),
        limpiar($datos['badge_extra']  ?? ''),
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
        $nuevaPrioritaria,
        $fechaPrioritaria,
        limpiar($datos['descripcion_slider'] ?? ''),
        !empty($datos['fecha_entrada'])    ? $datos['fecha_entrada']    : null,
        !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
        $id,
        $idProtectoraUsuario,
    ]);

    respuestaOk(['mensaje' => 'Mascota actualizada correctamente.']);
}

/*--------------------------------------------------------------------------------------------
DELETE — soft delete (protectora sobre las suyas, admin con motivo y auditoría) */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idMascota'] ?? 0);
    $motivo = limpiar($datos['motivo'] ?? '');

    if (!$id) respuestaError('idMascota requerido.');

    if ($esAdmin) {
        if (!$motivo) respuestaError('El administrador debe indicar un motivo para desactivar esta mascota.');

        $stmt = $pdo->prepare('SELECT idMascota, idProtectora FROM mascotas WHERE idMascota = ?');
        $stmt->execute([$id]);
        $m = $stmt->fetch();
        if (!$m) respuestaError('Mascota no encontrada.', 404);

        $pdo->prepare('UPDATE mascotas SET activa = 0, deleted_at = NOW() WHERE idMascota = ?')
            ->execute([$id]);

        $pdo->prepare(
            'INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, reason, detalles)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $idUsuario,
            'admin',
            'soft_delete_mascota',
            'mascota',
            $id,
            $motivo,
            json_encode(['idProtectora' => $m->idProtectora], JSON_UNESCAPED_UNICODE)
        ]);

        respuestaOk(['mensaje' => 'Mascota desactivada por moderación.']);
    } else {
        if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');
        $pdo->prepare('UPDATE mascotas SET activa = 0, deleted_at = NOW() WHERE idMascota = ? AND idProtectora = ?')
            ->execute([$id, $idProtectoraUsuario]);
        respuestaOk(['mensaje' => 'Mascota eliminada.']);
    }
}

respuestaError('Método no permitido.', 405);

/*--------------------------------------------------------------------------------------------
   PUT ?accion=toggle_prioritaria — marcar/desmarcar mascota prioritaria
   SOLO PROTECTORA (la propietaria), máximo LIMITE_PRIORITARIAS (2) */
if ($metodo === 'PUT' && $accion === 'toggle_prioritaria') {
    if ($esAdmin) {
        respuestaError('Los administradores no pueden marcar urgencias. Solo pueden desmarcar en caso de abuso.', 403);
    }

    $id = (int)($datos['idMascota'] ?? 0);
    if (!$id) respuestaError('idMascota requerido.');

    $check = $pdo->prepare('SELECT idMascota, idProtectora, prioritaria FROM mascotas WHERE idMascota = ? AND idProtectora = ?');
    $check->execute([$id, $idProtectoraUsuario]);
    $mascota = $check->fetch();
    if (!$mascota) respuestaError('No tienes permiso sobre esta mascota.', 403);

    if ($mascota['prioritaria']) {
        $pdo->prepare('UPDATE mascotas SET prioritaria = 0, fecha_prioritaria = NULL WHERE idMascota = ?')
            ->execute([$id]);
        respuestaOk(['mensaje' => 'Mascota desmarcada como prioritaria.', 'prioritaria' => 0]);
    } else {
        $stmtC = $pdo->prepare('SELECT COUNT(*) FROM mascotas WHERE idProtectora = ? AND prioritaria = 1 AND activa = 1');
        $stmtC->execute([$idProtectoraUsuario]);
        $cnt = (int)$stmtC->fetchColumn();
        if ($cnt >= LIMITE_PRIORITARIAS) {
            respuestaError('Has alcanzado el límite de mascotas urgentes. Desmarca otra para continuar.', 400);
        }
        $pdo->prepare('UPDATE mascotas SET prioritaria = 1, fecha_prioritaria = NOW() WHERE idMascota = ?')
            ->execute([$id]);
        respuestaOk(['mensaje' => 'Mascota marcada como prioritaria.', 'prioritaria' => 1]);
    }
}

/*--------------------------------------------------------------------------------------------
   PUT ?accion=desmarcar_prioritaria_admin — solo admin puede desmarcar abusos
   El admin no puede marcar, solo desmarcar. */
if ($metodo === 'PUT' && $accion === 'desmarcar_prioritaria_admin') {
    if (!$esAdmin) {
        respuestaError('Acción reservada al administrador.', 403);
    }

    $id = (int)($datos['idMascota'] ?? 0);
    if (!$id) respuestaError('idMascota requerido.');

    $pdo->prepare('UPDATE mascotas SET prioritaria = 0, fecha_prioritaria = NULL WHERE idMascota = ?')
        ->execute([$id]);
    respuestaOk(['mensaje' => 'Urgencia desmarcada por moderación.', 'prioritaria' => 0]);
}