<?php
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar publicaciones */
if ($metodo === 'GET') {

    /* conteos por categoria para el sidebar */
    if (isset($_GET['conteo'])) {
        $cats   = ['adopcion', 'cuidados', 'acogida', 'salud', 'informacion'];
        $conteos = [];
        foreach ($cats as $cat) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM publicaciones WHERE activa = 1 AND categoria = ?');
            $stmt->execute([$cat]);
            $conteos[$cat] = (int)$stmt->fetchColumn();
        }
        respuestaOk(['conteos' => $conteos]);
    }

    $categoria = $_GET['categoria'] ?? 'todas';
    $busqueda  = limpiar($_GET['q'] ?? '');
    $pagina    = (int)($_GET['pagina'] ?? 1);
    $p         = pagina($pagina, 15);

    $where  = ['pub.activa = 1'];
    $params = [];

    $cats = ['adopcion', 'cuidados', 'acogida', 'salud', 'informacion'];
    if ($categoria !== 'todas' && in_array($categoria, $cats)) {
        $where[]  = 'pub.categoria = ?';
        $params[] = $categoria;
    }
    if ($busqueda) {
        $where[]  = '(pub.titulo LIKE ? OR pub.contenido LIKE ?)';
        $params[] = '%' . $busqueda . '%';
        $params[] = '%' . $busqueda . '%';
    }

    $condicion = implode(' AND ', $where);

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM publicaciones pub WHERE $condicion");
    $stmtTotal->execute($params);
    $total = (int)$stmtTotal->fetch()['total'];

    iniciarSesionSegura();
    $idUsuarioActual = isset($_SESSION['idUsuario']) ? (int)$_SESSION['idUsuario'] : 0;
    session_write_close();

    $yoLike = $idUsuarioActual
        ? "(SELECT COUNT(*) FROM likes_publicaciones lp WHERE lp.idPublicacion = pub.idPublicacion AND lp.idUsuario = $idUsuarioActual) AS yo_like"
        : "0 AS yo_like";

    $sql = "SELECT
                pub.idPublicacion,
                pub.idUsuario,
                pub.idProtectora,
                pub.titulo,
                pub.contenido,
                pub.categoria,
                pub.imagen,
                pub.video,
                pub.tipo_media,
                pub.num_likes,
                pub.num_vistas,
                pub.fijada,
                pub.activa,
                pub.fecha,
                COALESCE(u.nombre,    prot.nombre,  'Anónimo') AS autor_nombre,
                COALESCE(u.username,  prot.nombre,  '')        AS autor_username,
                COALESCE(u.rol,       'protectora')             AS autor_rol,
                COALESCE(u.foto_perfil, prot.foto_logo)         AS autor_foto,
                (SELECT COUNT(*)
                 FROM comentarios c
                 WHERE c.idPublicacion = pub.idPublicacion
                   AND c.deleted = 0) AS num_comentarios,
                $yoLike
            FROM publicaciones pub
            LEFT JOIN usuarios    u    ON pub.idUsuario    = u.idUsuario
            LEFT JOIN protectoras prot ON pub.idProtectora = prot.idProtectora
            WHERE $condicion
            ORDER BY pub.fijada DESC, pub.fecha DESC
            LIMIT ? OFFSET ?";

    $params[] = $p['limite'];
    $params[] = $p['offset'];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk([
        'publicaciones' => $stmt->fetchAll(),
        'total'         => $total,
        'pagina'        => $p['pagina'],
        'totalPaginas'  => (int)ceil($total / $p['limite']),
    ]);
}

/*--------------------------------------------------------------------------------------------
POST - crear publicacion */
if ($metodo === 'POST') {
    requerirLogin();

    $esMultipart = isset($_POST['titulo']) || isset($_FILES['media']);
    if ($esMultipart) {
        $titulo    = limpiar($_POST['titulo']    ?? '');
        $contenido = limpiar($_POST['contenido'] ?? '');
        $categoria = $_POST['categoria']         ?? 'informacion';
    } else {
        $datos     = json_decode(file_get_contents('php://input'), true) ?? [];
        $titulo    = limpiar($datos['titulo']    ?? '');
        $contenido = limpiar($datos['contenido'] ?? '');
        $categoria = $datos['categoria']         ?? 'informacion';
    }

    if (!$contenido) respuestaError('El contenido es obligatorio.');
    if (!$titulo)    $titulo = mb_substr($contenido, 0, 80) . (mb_strlen($contenido) > 80 ? '…' : '');

    $cats = ['adopcion', 'cuidados', 'acogida', 'salud', 'informacion'];
    if (!in_array($categoria, $cats)) $categoria = 'informacion';

    iniciarSesionSegura();
    $idUsuario    = isset($_SESSION['idUsuario'])    ? (int)$_SESSION['idUsuario']    : null;
    $idProtectora = isset($_SESSION['idProtectora']) ? (int)$_SESSION['idProtectora'] : null;
    session_write_close();

    $rutaImagen = null;
    $rutaVideo  = null;
    $tipoMedia  = 'none';

    if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $archivo     = $_FILES['media'];
        $mime        = mime_content_type($archivo['tmp_name']);
        $tamanoMax   = 50 * 1024 * 1024;
        $tiposImagen = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $tiposVideo  = ['video/mp4', 'video/webm'];

        if (!in_array($mime, array_merge($tiposImagen, $tiposVideo)))
            respuestaError('Solo jpg, png, webp, gif, mp4 o webm.');
        if ($archivo['size'] > $tamanoMax) respuestaError('El archivo supera 50 MB.');

        $ext    = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombre = uniqid('foro_', true) . '.' . $ext;

        if (in_array($mime, $tiposImagen)) {
            $destDir    = __DIR__ . '/../../img/foro/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $rutaImagen = 'img/foro/' . $nombre;
            $tipoMedia  = 'imagen';
            if (!move_uploaded_file($archivo['tmp_name'], $destDir . $nombre))
                respuestaError('Error al guardar la imagen.');
        } else {
            $destDir   = __DIR__ . '/../../img/foro/videos/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $rutaVideo = 'img/foro/videos/' . $nombre;
            $tipoMedia = 'video';
            if (!move_uploaded_file($archivo['tmp_name'], $destDir . $nombre))
                respuestaError('Error al guardar el video.');
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO publicaciones (idUsuario, idProtectora, titulo, contenido, categoria, imagen, video, tipo_media)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idUsuario, $idProtectora, $titulo, $contenido, $categoria, $rutaImagen, $rutaVideo, $tipoMedia]);

    respuestaOk(['mensaje' => 'Publicación creada.', 'idPublicacion' => (int)$pdo->lastInsertId()]);
}

/*--------------------------------------------------------------------------------------------
PUT - like / unlike */
if ($metodo === 'PUT') {
    requerirLogin();

    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $idPub = (int)($datos['idPublicacion'] ?? 0);
    if (!$idPub) respuestaError('idPublicacion requerido.');

    iniciarSesionSegura();
    $idUsuario = (int)$_SESSION['idUsuario'];
    session_write_close();

    $stmt = $pdo->prepare('SELECT idPublicacion FROM publicaciones WHERE idPublicacion = ? AND activa = 1');
    $stmt->execute([$idPub]);
    if (!$stmt->fetch()) respuestaError('Publicación no encontrada.');

    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM likes_publicaciones WHERE idUsuario = ? AND idPublicacion = ?');
    $stmtCheck->execute([$idUsuario, $idPub]);
    $yaLike = (bool)$stmtCheck->fetchColumn();

    if ($yaLike) {
        $pdo->prepare('DELETE FROM likes_publicaciones WHERE idUsuario = ? AND idPublicacion = ?')
             ->execute([$idUsuario, $idPub]);
        $pdo->prepare('UPDATE publicaciones SET num_likes = GREATEST(0, num_likes - 1) WHERE idPublicacion = ?')
             ->execute([$idPub]);
        $accion = 'unlike';
    } else {
        $pdo->prepare('INSERT IGNORE INTO likes_publicaciones (idUsuario, idPublicacion) VALUES (?, ?)')
             ->execute([$idUsuario, $idPub]);
        $pdo->prepare('UPDATE publicaciones SET num_likes = num_likes + 1 WHERE idPublicacion = ?')
             ->execute([$idPub]);
        $accion = 'like';
    }

    $stmt = $pdo->prepare('SELECT num_likes FROM publicaciones WHERE idPublicacion = ?');
    $stmt->execute([$idPub]);
    respuestaOk(['accion' => $accion, 'num_likes' => (int)$stmt->fetchColumn()]);
}

/*--------------------------------------------------------------------------------------------
DELETE - eliminar publicacion */
if ($metodo === 'DELETE') {
    requerirLogin();

    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $idPub = (int)($datos['idPublicacion'] ?? 0);
    if (!$idPub) respuestaError('idPublicacion requerido.');

    iniciarSesionSegura();
    $idUsuario = (int)$_SESSION['idUsuario'];
    $rol       = $_SESSION['rol'] ?? '';
    session_write_close();

    $stmt = $pdo->prepare('SELECT idUsuario FROM publicaciones WHERE idPublicacion = ? AND activa = 1');
    $stmt->execute([$idPub]);
    $pub = $stmt->fetch();

    if (!$pub) respuestaError('Publicación no encontrada.');
    if ($pub['idUsuario'] !== $idUsuario && $rol !== 'admin')
        respuestaError('No tienes permiso para eliminar esta publicación.', 403);

    $pdo->prepare('UPDATE publicaciones SET activa = 0 WHERE idPublicacion = ?')->execute([$idPub]);
    respuestaOk(['mensaje' => 'Publicación eliminada.']);
}

respuestaError('Método no permitido.', 405);