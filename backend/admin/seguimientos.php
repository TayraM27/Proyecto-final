<?php
require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdminOProtectora();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();
$esAdmin = esAdmin();
$idProtectora = getIdProtectoraUsuario();

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $where = [];
        $params = [];

        if (!$esAdmin && $idProtectora) {
            $where[] = 'm.idProtectora = ?';
            $params[] = $idProtectora;
        }

        $cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare(
            'SELECT s.idSeguimiento, s.idApadrinamiento, s.contenido,
                    s.tipo_archivo, s.ruta_archivo, s.fecha,
                    s.idActualizacion,
                    m.nombre AS mascota_nombre
             FROM seguimientos s
             JOIN apadrinamientos a ON s.idApadrinamiento = a.idApadrinamiento
             JOIN mascotas m ON a.idMascota = m.idMascota
             ' . $cond . '
             ORDER BY s.fecha DESC'
        );
        $stmt->execute($params);
        $seguimientos = $stmt->fetchAll();

        $idsAct = array_filter(array_column($seguimientos, 'idActualizacion'));
        $respuestasPadrino = [];
        if ($idsAct) {
            $placeholders = implode(',', array_fill(0, count($idsAct), '?'));
            $stmtR = $pdo->prepare(
                "SELECT ap.idActualizacion, ap.respuesta, ap.fecha, u.nombre AS padrino_nombre
                 FROM actualizacion_padrinos ap
                 JOIN usuarios u ON ap.idUsuario = u.idUsuario
                 WHERE ap.idActualizacion IN ($placeholders) AND ap.respuesta IS NOT NULL AND ap.respuesta != ''"
            );
            $stmtR->execute($idsAct);
            $rows = $stmtR->fetchAll();
            foreach ($rows as $r) {
                $respuestasPadrino[$r['idActualizacion']][] = $r;
            }
        }

        foreach ($seguimientos as &$s) {
            $s['padrino_respuestas'] = $respuestasPadrino[$s['idActualizacion']] ?? [];
        }
        unset($s);

        respuestaOk(['seguimientos' => $seguimientos]);
        break;

    case 'POST':
        if (!$esAdmin && !$idProtectora) {
            respuestaError('No tienes una protectora asignada.', 403);
        }

        $idMascota = (int)($_POST['idMascota'] ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');

        if (!$idMascota || !$contenido) {
            respuestaError('Faltan campos obligatorios: idMascota, contenido.', 422);
        }

        $stmt = $pdo->prepare(
            'SELECT a.idApadrinamiento, m.idProtectora
             FROM apadrinamientos a
             JOIN mascotas m ON a.idMascota = m.idMascota
             WHERE a.idMascota = ? AND a.estado = "activo"
             LIMIT 1'
        );
        $stmt->execute([$idMascota]);
        $apad = $stmt->fetch();

        if (!$apad) {
            respuestaError('No hay un apadrinamiento activo para esta mascota.', 404);
        }

        if (!$esAdmin && (int)$apad['idProtectora'] !== $idProtectora) {
            respuestaError('No puedes gestionar esta mascota.', 403);
        }

        $tipoArchivo = 'texto';
        $rutaArchivo = null;

        if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../../uploads/seguimientos/';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $nombre = uniqid('seg_') . '.' . $ext;
            move_uploaded_file($_FILES['archivo']['tmp_name'], $dir . $nombre);
            $imgExts = ['jpg','jpeg','png','gif','webp','bmp'];
            $vidExts = ['mp4','webm','mov','avi'];
            $tipoArchivo = in_array($ext, $imgExts) ? 'foto' : (in_array($ext, $vidExts) ? 'video' : 'texto');
            $rutaArchivo = 'uploads/seguimientos/' . $nombre;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO seguimientos (idApadrinamiento, contenido, tipo_archivo, ruta_archivo)
             VALUES (?,?,?,?)'
        );
        $stmt->execute([
            (int)$apad['idApadrinamiento'],
            $contenido,
            $tipoArchivo,
            $rutaArchivo,
        ]);
        $idSeguimiento = $pdo->lastInsertId();

        $fotos = ($tipoArchivo === 'foto' && $rutaArchivo) ? json_encode([$rutaArchivo]) : null;
        $videoUrl = ($tipoArchivo === 'video' && $rutaArchivo) ? $rutaArchivo : null;

        $stmt = $pdo->prepare('INSERT INTO actualizaciones (idMascota, idProtectora, mensaje, fotos, video_url) VALUES (?,?,?,?,?)');
        $stmt->execute([$idMascota, $idProtectora, $contenido, $fotos, $videoUrl]);
        $idActualizacion = $pdo->lastInsertId();
        $pdo->prepare('UPDATE seguimientos SET idActualizacion = ? WHERE idSeguimiento = ?')->execute([$idActualizacion, $idSeguimiento]);

        $stmtM = $pdo->prepare('SELECT nombre FROM mascotas WHERE idMascota = ?');
        $stmtM->execute([$idMascota]);
        $mascota = $stmtM->fetch();
        $mascotaNombre = $mascota ? $mascota['nombre'] : 'tu apadrinado';

        $stmt = $pdo->prepare('SELECT idUsuario FROM apadrinamientos WHERE idMascota = ? AND estado = "activo"');
        $stmt->execute([$idMascota]);
        $padrinos = $stmt->fetchAll();
        $insertPadrino = $pdo->prepare('INSERT INTO actualizacion_padrinos (idActualizacion, idUsuario, leido) VALUES (?,?,0)');
        foreach ($padrinos as $padrino) {
            $insertPadrino->execute([$idActualizacion, $padrino['idUsuario']]);
            crearNotificacion($padrino['idUsuario'], 'actualizacion_protectora', 'Nueva actualización de ' . $mascotaNombre, 'perfil.html?tab=actualizaciones');
        }

        respuestaOk(['mensaje' => 'Seguimiento añadido correctamente.']);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idSeguimiento'])) {
            respuestaError('Falta idSeguimiento.', 422);
        }

        $stmt = $pdo->prepare(
            'SELECT s.idSeguimiento, m.idProtectora
             FROM seguimientos s
             JOIN apadrinamientos a ON s.idApadrinamiento = a.idApadrinamiento
             JOIN mascotas m ON a.idMascota = m.idMascota
             WHERE s.idSeguimiento = ?'
        );
        $stmt->execute([(int)$data['idSeguimiento']]);
        $seg = $stmt->fetch();

        if (!$seg) respuestaError('Seguimiento no encontrado.', 404);
        if (!$esAdmin && (int)$seg['idProtectora'] !== $idProtectora) {
            respuestaError('No puedes eliminar este seguimiento.', 403);
        }

        $pdo->prepare('DELETE FROM seguimientos WHERE idSeguimiento = ?')
            ->execute([(int)$data['idSeguimiento']]);
        respuestaOk(['mensaje' => 'Seguimiento eliminado.']);
        break;

    default:
        respuestaError('Método no permitido.', 405);
}
