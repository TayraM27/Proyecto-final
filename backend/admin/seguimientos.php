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
            'SELECT MAX(s.idSeguimiento) AS idSeguimiento,
                    MAX(s.idApadrinamiento) AS idApadrinamiento,
                    s.contenido,
                    MAX(s.tipo_archivo) AS tipo_archivo,
                    MAX(s.ruta_archivo) AS ruta_archivo,
                    MAX(s.fecha) AS fecha,
                    MAX(s.idActualizacion) AS idActualizacion,
                    m.nombre AS mascota_nombre
             FROM seguimientos s
             JOIN apadrinamientos a ON s.idApadrinamiento = a.idApadrinamiento
             JOIN mascotas m ON a.idMascota = m.idMascota
             ' . $cond . '
             GROUP BY COALESCE(s.idActualizacion, s.idSeguimiento), s.contenido, m.nombre
             ORDER BY fecha DESC'
        );
        $stmt->execute($params);
        $seguimientos = $stmt->fetchAll();

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
            'SELECT a.idApadrinamiento, a.idUsuario, m.idProtectora
             FROM apadrinamientos a
             JOIN mascotas m ON a.idMascota = m.idMascota
             WHERE a.idMascota = ? AND a.estado = "activo"'
        );
        $stmt->execute([$idMascota]);
        $apads = $stmt->fetchAll();

        if (!$apads) {
            respuestaError('No hay un apadrinamiento activo para esta mascota.', 404);
        }

        if (!$esAdmin && (int)$apads[0]['idProtectora'] !== $idProtectora) {
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

        $fotos = ($tipoArchivo === 'foto' && $rutaArchivo) ? json_encode([$rutaArchivo]) : null;
        $videoUrl = ($tipoArchivo === 'video' && $rutaArchivo) ? $rutaArchivo : null;

        $stmt = $pdo->prepare('INSERT INTO actualizaciones (idMascota, idProtectora, mensaje, fotos, video_url) VALUES (?,?,?,?,?)');
        $stmt->execute([$idMascota, $idProtectora, $contenido, $fotos, $videoUrl]);
        $idActualizacion = $pdo->lastInsertId();

        $stmtM = $pdo->prepare('SELECT nombre FROM mascotas WHERE idMascota = ?');
        $stmtM->execute([$idMascota]);
        $mascota = $stmtM->fetch();
        $mascotaNombre = $mascota ? $mascota['nombre'] : 'tu apadrinado';

        $insertSeg = $pdo->prepare('INSERT INTO seguimientos (idApadrinamiento, contenido, tipo_archivo, ruta_archivo, idActualizacion) VALUES (?,?,?,?,?)');
        $insertPadrino = $pdo->prepare('INSERT INTO actualizacion_padrinos (idActualizacion, idUsuario, leido) VALUES (?,?,0)');

        foreach ($apads as $apad) {
            $insertSeg->execute([
                (int)$apad['idApadrinamiento'],
                $contenido,
                $tipoArchivo,
                $rutaArchivo,
                $idActualizacion,
            ]);
            if ($apad['idUsuario']) {
                $insertPadrino->execute([$idActualizacion, $apad['idUsuario']]);
                crearNotificacion($apad['idUsuario'], 'actualizacion_protectora', 'Nueva actualización de ' . $mascotaNombre, 'perfil.html?tab=actualizaciones');
            }
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
