<?php
/*--------------------------------------------------------------------------------------------
GET  ?idPublicacion=1 — lista comentarios
POST — crea comentario (requiere login)
PUT  — like/unlike (requiere login) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $idPublicacion = (int)($_GET['idPublicacion'] ?? 0);
    if (!$idPublicacion) respuestaError('ID de publicacion no valido.');

    iniciarSesionSegura();
    $idUsuarioActual = isset($_SESSION['idUsuario']) ? (int)$_SESSION['idUsuario'] : 0;
    session_write_close();

    $sql = "SELECT
                c.idComentario,
                c.contenido,
                c.num_likes,
                c.fecha,
                u.nombre      AS autor_nombre,
                u.username    AS autor_username,
                u.rol         AS autor_rol,
                u.foto_perfil AS autor_foto"
        . ($idUsuarioActual
            ? ", (SELECT COUNT(*) FROM likes_comentarios lc
                  WHERE lc.idComentario = c.idComentario AND lc.idUsuario = $idUsuarioActual) AS yo_like"
            : ", 0 AS yo_like")
        . " FROM comentarios c
            JOIN usuarios u ON c.idUsuario = u.idUsuario
            WHERE c.idPublicacion = ? AND c.activo = 1
            ORDER BY c.fecha ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idPublicacion]);

    $pdo->prepare('UPDATE publicaciones SET num_vistas = num_vistas + 1 WHERE idPublicacion = ?')
        ->execute([$idPublicacion]);

    respuestaOk(['comentarios' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
POST */
if ($metodo === 'POST') {
    requerirLogin();

    $datos         = json_decode(file_get_contents('php://input'), true) ?? [];
    $idPublicacion = (int)($datos['idPublicacion'] ?? 0);
    $contenido     = limpiar($datos['contenido']   ?? '');

    if (!$idPublicacion || !$contenido) respuestaError('Publicacion y contenido son obligatorios.');

    $stmt = $pdo->prepare('SELECT idPublicacion, idUsuario FROM publicaciones WHERE idPublicacion = ? AND activa = 1 LIMIT 1');
    $stmt->execute([$idPublicacion]);
    $pub = $stmt->fetch();
    if (!$pub) respuestaError('Publicacion no encontrada.', 404);

    $idUsuario = (int)$_SESSION['idUsuario'];

    $stmt = $pdo->prepare('INSERT INTO comentarios (idPublicacion, idUsuario, contenido) VALUES (?, ?, ?)');
    $stmt->execute([$idPublicacion, $idUsuario, $contenido]);

    respuestaOk(['mensaje' => 'Comentario publicado.', 'idComentario' => (int)$pdo->lastInsertId()]);
}

/*--------------------------------------------------------------------------------------------
PUT — like/unlike comentario */
if ($metodo === 'PUT') {
    requerirLogin();

    $datos        = json_decode(file_get_contents('php://input'), true) ?? [];
    $idComentario = (int)($datos['idComentario'] ?? 0);
    if (!$idComentario) respuestaError('ID de comentario no valido.');

    $idUsuario = (int)$_SESSION['idUsuario'];

    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM likes_comentarios WHERE idUsuario = ? AND idComentario = ?');
    $stmtCheck->execute([$idUsuario, $idComentario]);
    $yaLike = (bool)$stmtCheck->fetchColumn();

    if ($yaLike) {
        $pdo->prepare('DELETE FROM likes_comentarios WHERE idUsuario = ? AND idComentario = ?')
            ->execute([$idUsuario, $idComentario]);
        $pdo->prepare('UPDATE comentarios SET num_likes = GREATEST(0, num_likes - 1) WHERE idComentario = ?')
            ->execute([$idComentario]);
        $accion = 'unlike';
    } else {
        $pdo->prepare('INSERT IGNORE INTO likes_comentarios (idUsuario, idComentario) VALUES (?, ?)')
            ->execute([$idUsuario, $idComentario]);
        $pdo->prepare('UPDATE comentarios SET num_likes = num_likes + 1 WHERE idComentario = ?')
            ->execute([$idComentario]);
        $accion = 'like';
    }

    $nuevoTotal = (int)$pdo->query("SELECT num_likes FROM comentarios WHERE idComentario = $idComentario")->fetchColumn();
    respuestaOk(['accion' => $accion, 'num_likes' => $nuevoTotal]);
}

respuestaError('Metodo no permitido.', 405);