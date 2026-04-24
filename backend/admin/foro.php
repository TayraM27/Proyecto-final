<?php
/*--------------------------------------------------------------------------------------------
admin/foro.php
Moderacion del foro para el administrador
GET    — lista publicaciones y comentarios
DELETE — elimina publicacion o comentario (borrado logico)
PUT    — fijar/desfijar/reactivar publicacion o comentario */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $tipo        = $_GET['tipo']        ?? 'publicaciones';
    $pagina      = max(1, (int)($_GET['pagina'] ?? 1));
    $soloActivas = isset($_GET['soloActivas']) ? (int)$_GET['soloActivas'] : 1;
    $p           = paginacion($pagina, 20);

    if ($tipo === 'comentarios') {
        $where = $soloActivas ? 'WHERE c.activo = 1' : '';
        $stmt = $pdo->prepare(
            "SELECT c.idComentario, c.contenido, c.num_likes, c.activo, c.fecha,
                    u.nombre AS autor_nombre, u.username AS autor_username,
                    pub.titulo AS publicacion_titulo, pub.idPublicacion
             FROM comentarios c
             JOIN usuarios u   ON c.idUsuario     = u.idUsuario
             JOIN publicaciones pub ON c.idPublicacion = pub.idPublicacion
             $where
             ORDER BY c.fecha DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$p['limite'], $p['offset']]);
        respuestaOk(['comentarios' => $stmt->fetchAll()]);
    }

    /* publicaciones */
    $where = $soloActivas ? 'WHERE pub.activa = 1' : '';
    $stmt = $pdo->prepare(
        "SELECT pub.idPublicacion, pub.titulo, pub.categoria, pub.num_likes,
                pub.num_vistas, pub.fijada, pub.activa, pub.fecha,
                u.nombre AS autor_nombre, u.username AS autor_username,
                u.rol    AS autor_rol,
                (SELECT COUNT(*) FROM comentarios c WHERE c.idPublicacion = pub.idPublicacion AND c.activo = 1) AS num_comentarios
         FROM publicaciones pub
         JOIN usuarios u ON pub.idUsuario = u.idUsuario
         $where
         ORDER BY pub.fijada DESC, pub.fecha DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute([$p['limite'], $p['offset']]);
    respuestaOk(['publicaciones' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
PUT */
if ($metodo === 'PUT') {
    $datos  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $datos['accion'] ?? '';

    if ($accion === 'fijar' || $accion === 'desfijar') {
        $id = (int)($datos['idPublicacion'] ?? 0);
        if (!$id) respuestaError('ID requerido.');
        $fijada = $accion === 'fijar' ? 1 : 0;
        $pdo->prepare('UPDATE publicaciones SET fijada = ? WHERE idPublicacion = ?')->execute([$fijada, $id]);
        respuestaOk(['mensaje' => 'Publicación ' . ($fijada ? 'fijada' : 'desfijada') . '.']);
    }

    if ($accion === 'reactivar_publicacion') {
        $id = (int)($datos['idPublicacion'] ?? 0);
        if (!$id) respuestaError('ID requerido.');
        $pdo->prepare('UPDATE publicaciones SET activa = 1 WHERE idPublicacion = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Publicación reactivada.']);
    }

    if ($accion === 'reactivar_comentario') {
        $id = (int)($datos['idComentario'] ?? 0);
        if (!$id) respuestaError('ID requerido.');
        $pdo->prepare('UPDATE comentarios SET activo = 1 WHERE idComentario = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Comentario reactivado.']);
    }

    respuestaError('Acción no reconocida.');
}

/*--------------------------------------------------------------------------------------------
DELETE */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $tipo  = $datos['tipo'] ?? '';

    if ($tipo === 'publicacion') {
        $id = (int)($datos['idPublicacion'] ?? 0);
        if (!$id) respuestaError('ID requerido.');
        $pdo->prepare('UPDATE publicaciones SET activa = 0 WHERE idPublicacion = ?')->execute([$id]);
        $pdo->prepare('UPDATE comentarios SET activo = 0 WHERE idPublicacion = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Publicación eliminada.']);
    }

    if ($tipo === 'comentario') {
        $id = (int)($datos['idComentario'] ?? 0);
        if (!$id) respuestaError('ID requerido.');
        $pdo->prepare('UPDATE comentarios SET activo = 0 WHERE idComentario = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Comentario eliminado.']);
    }

    respuestaError('Tipo no reconocido.');
}

respuestaError('Método no permitido.', 405);