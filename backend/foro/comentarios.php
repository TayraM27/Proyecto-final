<?php
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

function obtenerSesion() {
    iniciarSesionSegura();
    $r = [
        'idUsuario'    => $_SESSION['idUsuario']    ?? null,
        'idProtectora' => $_SESSION['idProtectora'] ?? null,
        'rol'          => $_SESSION['rol']           ?? '',
        'esAdmin'      => ($_SESSION['rol']          ?? '') === 'admin',
    ];
    session_write_close();
    return $r;
}

/*--------------------------------------------------------------------------------------------
GET - cargar comentarios */
if ($metodo === 'GET') {
    $idPublicacion = (int)($_GET['idPublicacion'] ?? 0);
    if (!$idPublicacion) respuestaError('ID de publicacion no valido.');

    $ses          = obtenerSesion();
    $idUsuario    = $ses['idUsuario']    ? (int)$ses['idUsuario']    : 0;
    $idProtectora = $ses['idProtectora'] ? (int)$ses['idProtectora'] : 0;
    $esAdmin      = $ses['esAdmin'];

    $pdo->prepare('UPDATE publicaciones SET num_vistas = num_vistas + 1 WHERE idPublicacion = ?')
        ->execute([$idPublicacion]);

    $sql = "SELECT
                c.idComentario,
                c.idPublicacion,
                c.idUsuario,
                c.idProtectora,
                c.parent_id,
                c.contenido,
                c.num_likes,
                c.deleted,
                c.deleted_at,
                c.fecha,
                u.nombre      AS autor_nombre,
                u.username    AS autor_username,
                u.rol         AS autor_rol,
                u.foto_perfil AS autor_foto,
                p.nombre      AS prot_nombre,
                p.email       AS prot_email
            FROM comentarios c
            LEFT JOIN usuarios    u ON c.idUsuario    = u.idUsuario
            LEFT JOIN protectoras p ON c.idProtectora = p.idProtectora
            WHERE c.idPublicacion = ? AND c.parent_id IS NULL
            ORDER BY c.fecha ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idPublicacion]);
    $comentarios = $stmt->fetchAll();

    foreach ($comentarios as &$com) {
        if ($com['deleted'] && !$esAdmin) {
            $com['contenido']      = null;
            $com['autor_nombre']   = '';
            $com['autor_username'] = '';
            $com['autor_foto']     = '';
        }

        $com['tiene_like'] = false;
        if ($idUsuario) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idComentario = ? AND idUsuario = ?');
            $chk->execute([$com['idComentario'], $idUsuario]);
            $com['tiene_like'] = (bool)$chk->fetchColumn();
        } elseif ($idProtectora) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idComentario = ? AND idProtectora = ?');
            $chk->execute([$com['idComentario'], $idProtectora]);
            $com['tiene_like'] = (bool)$chk->fetchColumn();
        }

        $sqlResp = "SELECT
                        r.idComentario,
                        r.idPublicacion,
                        r.idUsuario,
                        r.idProtectora,
                        r.parent_id,
                        r.contenido,
                        r.num_likes,
                        r.deleted,
                        r.deleted_at,
                        r.fecha,
                        u.nombre      AS autor_nombre,
                        u.username    AS autor_username,
                        u.rol         AS autor_rol,
                        u.foto_perfil AS autor_foto,
                        p.nombre      AS prot_nombre,
                        p.email       AS prot_email
                    FROM comentarios r
                    LEFT JOIN usuarios    u ON r.idUsuario    = u.idUsuario
                    LEFT JOIN protectoras p ON r.idProtectora = p.idProtectora
                    WHERE r.parent_id = ?
                    ORDER BY r.fecha ASC";

        $stmtResp = $pdo->prepare($sqlResp);
        $stmtResp->execute([$com['idComentario']]);
        $respuestas = $stmtResp->fetchAll();

        foreach ($respuestas as &$resp) {
            if ($resp['deleted'] && !$esAdmin) {
                $resp['contenido']      = null;
                $resp['autor_nombre']   = '';
                $resp['autor_username'] = '';
                $resp['autor_foto']     = '';
            }

            $resp['tiene_like'] = false;
            if ($idUsuario) {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idComentario = ? AND idUsuario = ?');
                $chk->execute([$resp['idComentario'], $idUsuario]);
                $resp['tiene_like'] = (bool)$chk->fetchColumn();
            } elseif ($idProtectora) {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idComentario = ? AND idProtectora = ?');
                $chk->execute([$resp['idComentario'], $idProtectora]);
                $resp['tiene_like'] = (bool)$chk->fetchColumn();
            }
        }
        unset($resp);
        $com['respuestas'] = $respuestas;
    }
    unset($com);

    respuestaOk(['comentarios' => $comentarios]);
}

/*--------------------------------------------------------------------------------------------
POST - crear comentario o respuesta */
if ($metodo === 'POST') {
    $ses = obtenerSesion();
    if (!$ses['idUsuario'] && !$ses['idProtectora']) respuestaError('Debes iniciar sesion.', 401);

    $datos         = jsonInput();
    $idPublicacion = (int)($datos['idPublicacion'] ?? 0);
    $parent_id     = !empty($datos['idPadre']) ? (int)$datos['idPadre'] : null;
    $contenido     = trim($datos['contenido'] ?? '');

    if (!$idPublicacion || !$contenido) respuestaError('Publicacion y contenido son obligatorios.');
    if (mb_strlen($contenido) > 1000)   respuestaError('Maximo 1000 caracteres.');

    $stmt = $pdo->prepare(
        'SELECT idPublicacion, idUsuario, idProtectora FROM publicaciones WHERE idPublicacion = ? AND activa = 1 LIMIT 1'
    );
    $stmt->execute([$idPublicacion]);
    $pub = $stmt->fetch();
    if (!$pub) respuestaError('Publicacion no encontrada.', 404);

    /* Validar padre y aplanar a 1 nivel de profundidad */
    if ($parent_id) {
        $chkParent = $pdo->prepare('SELECT idComentario, deleted, parent_id FROM comentarios WHERE idComentario = ? AND idPublicacion = ?');
        $chkParent->execute([$parent_id, $idPublicacion]);
        $parent = $chkParent->fetch();
        if (!$parent)           respuestaError('Comentario padre no encontrado.', 404);
        if ($parent['deleted']) respuestaError('No puedes responder a un comentario eliminado.', 400);
        /* Si el padre es ya una respuesta, usar su padre (max 1 nivel) */
        if ($parent['parent_id']) {
            $parent_id = (int)$parent['parent_id'];
        }
    }

    $idUsuario    = $ses['idUsuario'];
    $idProtectora = $ses['idProtectora'];

    $stmt = $pdo->prepare(
        'INSERT INTO comentarios (idPublicacion, idUsuario, idProtectora, parent_id, contenido)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idPublicacion, $idUsuario, $idProtectora, $parent_id, $contenido]);
    $idComentario = (int)$pdo->lastInsertId();

    /* Notificaciones solo en comentarios raiz */
    if (!$parent_id) {
        if ($idUsuario) {
            $stmtUser = $pdo->prepare('SELECT username FROM usuarios WHERE idUsuario = ?');
            $stmtUser->execute([$idUsuario]);
        } else {
            $stmtUser = $pdo->prepare('SELECT nombre AS username FROM protectoras WHERE idProtectora = ?');
            $stmtUser->execute([$idProtectora]);
        }
        $userCom  = $stmtUser->fetch();
        $msgNotif = $userCom ? ($userCom['username'] . ' ha comentado tu publicacion') : 'Nuevo comentario en tu publicacion';

        if ($pub['idUsuario'] && $pub['idUsuario'] != $idUsuario) {
            $pdo->prepare('INSERT INTO notificaciones (idUsuario, tipo, mensaje, idPublicacion) VALUES (?, ?, ?, ?)')
                ->execute([$pub['idUsuario'], 'comentario', $msgNotif, $idPublicacion]);
        }
        if ($pub['idProtectora'] && $pub['idProtectora'] != $idProtectora) {
            $pdo->prepare('INSERT INTO notificaciones (idProtectora, tipo, mensaje, idPublicacion) VALUES (?, ?, ?, ?)')
                ->execute([$pub['idProtectora'], 'comentario', $msgNotif, $idPublicacion]);
        }
    }

    respuestaOk(['mensaje' => 'Comentario publicado.', 'idComentario' => $idComentario]);
}

/*--------------------------------------------------------------------------------------------
PUT - like / unlike */
if ($metodo === 'PUT') {
    $ses = obtenerSesion();
    if (!$ses['idUsuario'] && !$ses['idProtectora']) respuestaError('Debes iniciar sesion.', 401);

    $datos        = jsonInput();
    $idComentario = (int)($datos['idComentario'] ?? 0);
    if (!$idComentario) respuestaError('ID de comentario no valido.');

    $stmtChk = $pdo->prepare('SELECT idUsuario, idProtectora, idPublicacion, deleted FROM comentarios WHERE idComentario = ?');
    $stmtChk->execute([$idComentario]);
    $com = $stmtChk->fetch();
    if (!$com)           respuestaError('Comentario no encontrado.', 404);
    if ($com['deleted']) respuestaError('No puedes dar like a un comentario eliminado.', 400);

    $idUsuarioL = $ses['idUsuario'];
    $idProtectoraL = $ses['idProtectora'];

    if ($idUsuarioL) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idComentario = ? AND idUsuario = ?');
        $chk->execute([$idComentario, $idUsuarioL]);
        $yaLike = (bool)$chk->fetchColumn();

        if ($yaLike) {
            $pdo->prepare('DELETE FROM likes_comentarios WHERE idComentario = ? AND idUsuario = ?')->execute([$idComentario, $idUsuarioL]);
            $pdo->prepare('UPDATE comentarios SET num_likes = GREATEST(0, num_likes - 1) WHERE idComentario = ?')->execute([$idComentario]);
            $accion = 'unlike';
        } else {
            $pdo->prepare('INSERT IGNORE INTO likes_comentarios (idUsuario, idComentario) VALUES (?, ?)')->execute([$idUsuarioL, $idComentario]);
            $pdo->prepare('UPDATE comentarios SET num_likes = num_likes + 1 WHERE idComentario = ?')->execute([$idComentario]);
            $accion = 'like';

            $s = $pdo->prepare('SELECT username FROM usuarios WHERE idUsuario = ?');
            $s->execute([$idUsuarioL]);
            $liker = $s->fetch();
            if ($liker) {
                if ($com['idUsuario'] && $com['idUsuario'] != $idUsuarioL) {
                    $pdo->prepare('INSERT INTO notificaciones (idUsuario, tipo, mensaje, idPublicacion) VALUES (?,?,?,?)')
                        ->execute([$com['idUsuario'], 'like_comentario', $liker['username'] . ' le ha dado like a tu comentario', $com['idPublicacion']]);
                }
                if ($com['idProtectora']) {
                    $pdo->prepare('INSERT INTO notificaciones (idProtectora, tipo, mensaje, idPublicacion) VALUES (?,?,?,?)')
                        ->execute([$com['idProtectora'], 'like_comentario', $liker['username'] . ' le ha dado like a tu comentario', $com['idPublicacion']]);
                }
            }
        }
    } else {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idComentario = ? AND idProtectora = ?');
        $chk->execute([$idComentario, $idProtectoraL]);
        $yaLike = (bool)$chk->fetchColumn();

        if ($yaLike) {
            $pdo->prepare('DELETE FROM likes_comentarios WHERE idComentario = ? AND idProtectora = ?')->execute([$idComentario, $idProtectoraL]);
            $pdo->prepare('UPDATE comentarios SET num_likes = GREATEST(0, num_likes - 1) WHERE idComentario = ?')->execute([$idComentario]);
            $accion = 'unlike';
        } else {
            $pdo->prepare('INSERT IGNORE INTO likes_comentarios (idProtectora, idComentario) VALUES (?, ?)')->execute([$idProtectoraL, $idComentario]);
            $pdo->prepare('UPDATE comentarios SET num_likes = num_likes + 1 WHERE idComentario = ?')->execute([$idComentario]);
            $accion = 'like';

            $s = $pdo->prepare('SELECT nombre FROM protectoras WHERE idProtectora = ?');
            $s->execute([$idProtectoraL]);
            $liker = $s->fetch();
            if ($liker) {
                if ($com['idUsuario'] && $com['idUsuario'] != $idUsuarioL) {
                    $pdo->prepare('INSERT INTO notificaciones (idUsuario, tipo, mensaje, idPublicacion) VALUES (?,?,?,?)')
                        ->execute([$com['idUsuario'], 'like_comentario', $liker['nombre'] . ' le ha dado like a tu comentario', $com['idPublicacion']]);
                }
                if ($com['idProtectora'] && $com['idProtectora'] != $idProtectoraL) {
                    $pdo->prepare('INSERT INTO notificaciones (idProtectora, tipo, mensaje, idPublicacion) VALUES (?,?,?,?)')
                        ->execute([$com['idProtectora'], 'like_comentario', $liker['nombre'] . ' le ha dado like a tu comentario', $com['idPublicacion']]);
                }
            }
        }
    }

    $stmtLikes = $pdo->prepare('SELECT num_likes FROM comentarios WHERE idComentario = ?');
    $stmtLikes->execute([$idComentario]);
    respuestaOk(['accion' => $accion, 'num_likes' => (int)$stmtLikes->fetchColumn()]);
}

/*--------------------------------------------------------------------------------------------
DELETE - soft delete */
if ($metodo === 'DELETE') {
    $ses = obtenerSesion();
    if (!$ses['idUsuario'] && !$ses['idProtectora']) respuestaError('Debes iniciar sesion.', 401);

    $datos        = jsonInput();
    $idComentario = (int)($datos['idComentario'] ?? 0);
    if (!$idComentario) respuestaError('ID de comentario no valido.');

    $stmt = $pdo->prepare('SELECT idUsuario, idProtectora, idPublicacion, parent_id, deleted FROM comentarios WHERE idComentario = ?');
    $stmt->execute([$idComentario]);
    $com = $stmt->fetch();
    if (!$com)           respuestaError('Comentario no encontrado.');
    if ($com['deleted']) respuestaError('Comentario ya eliminado.');

    $esAutor = ($ses['idUsuario']    && $com['idUsuario']    == $ses['idUsuario'])
            || ($ses['idProtectora'] && $com['idProtectora'] == $ses['idProtectora']);

    if (!$esAutor && !$ses['esAdmin']) respuestaError('No tienes permiso para eliminar este comentario.', 403);

    $pdo->prepare('UPDATE comentarios SET deleted = 1, deleted_at = NOW() WHERE idComentario = ?')
        ->execute([$idComentario]);

    respuestaOk(['mensaje' => 'Comentario eliminado.']);
}

respuestaError('Metodo no permitido.', 405);