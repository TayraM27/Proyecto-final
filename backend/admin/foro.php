<?php
/*--------------------------------------------------------------------------------------------
admin/foro.php
Moderacion del foro para el administrador
GET    — lista publicaciones y comentarios pendientes de revision
DELETE — elimina publicacion o comentario (borrado logico)
PUT    — fijar/desfijar publicacion */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar publicaciones para moderar */
if ($metodo === 'GET') {
    $tipo   = $_GET['tipo']   ?? 'publicaciones';
    $pagina = (int)($_GET['pagina'] ?? 1);
    $p      = paginacion($pagina, 20);

    if ($tipo === 'comentarios') {
        $stmt = $pdo->prepare(
            'SELECT c.idComentario, c.contenido, c.num_likes, c.activo, c.fecha,
                    u.nombre AS autor_nombre, u.username AS autor_username,
                    pub.titulo AS publicacion_titulo, pub.idPublicacion
             FROM comentarios c
             JOIN usuarios u ON c.idUsuario = u.idUsuario
             JOIN publicaciones pub ON c.idPublicacion = pub.idPublicacion
             ORDER BY c.fecha DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$p['limite'], $p['offset']]);
        respuestaOk(['comentarios' => $stmt->fetchAll()]);
    }

    // Por defecto: publicaciones
    $stmt = $pdo->prepare(
        'SELECT pub.idPublicacion, pub.titulo, pub.categoria, pub.num_likes,
                pub.num_vistas, pub.fijada, pub.activa, pub.fecha,
                u.nombre AS autor_nombre, u.username AS autor_username,
                u.rol    AS autor_rol,
                (SELECT COUNT(*) FROM comentarios c WHERE c.idPublicacion = pub.idPublicacion) AS num_comentarios
         FROM publicaciones pub
         JOIN usuarios u ON pub.idUsuario = u.idUsuario
         ORDER BY pub.fijada DESC, pub.fecha DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$p['limite'], $p['offset']]);
    respuestaOk(['publicaciones' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
PUT - fijar/desfijar publicacion o reactivar contenido */
if ($metodo === 'PUT') {
    $datos  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $datos['accion'] ?? '';

    if ($accion === 'fijar' || $accion === 'desfijar') {
        $idPublicacion = (int)($datos['idPublicacion'] ?? 0);
        if (!$idPublicacion) {
            respuestaError('ID de publicacion requerido.');
        }
        $fijada = $accion === 'fijar' ? 1 : 0;
        $pdo->prepare('UPDATE publicaciones SET fijada = ? WHERE idPublicacion = ?')
            ->execute([$fijada, $idPublicacion]);
        respuestaOk(['mensaje' => 'Publicacion ' . ($fijada ? 'fijada' : 'desfijada') . '.']);
    }

    if ($accion === 'reactivar_publicacion') {
        $id = (int)($datos['idPublicacion'] ?? 0);
        if (!$id) {
            respuestaError('ID requerido.');
        }
        $pdo->prepare('UPDATE publicaciones SET activa = 1 WHERE idPublicacion = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Publicacion reactivada.']);
    }

    if ($accion === 'reactivar_comentario') {
        $id = (int)($datos['idComentario'] ?? 0);
        if (!$id) {
            respuestaError('ID requerido.');
        }
        $pdo->prepare('UPDATE comentarios SET activo = 1 WHERE idComentario = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Comentario reactivado.']);
    }

    respuestaError('Accion no reconocida.');
}

/*--------------------------------------------------------------------------------------------
DELETE - borrado logico de publicacion o comentario */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $tipo  = $datos['tipo'] ?? '';

    if ($tipo === 'publicacion') {
        $id = (int)($datos['idPublicacion'] ?? 0);
        if (!$id) {
            respuestaError('ID de publicacion requerido.');
        }
        $pdo->prepare('UPDATE publicaciones SET activa = 0 WHERE idPublicacion = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Publicacion eliminada.']);
    }

    if ($tipo === 'comentario') {
        $id = (int)($datos['idComentario'] ?? 0);
        if (!$id) {
            respuestaError('ID de comentario requerido.');
        }
        $pdo->prepare('UPDATE comentarios SET activo = 0 WHERE idComentario = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Comentario eliminado.']);
    }

    respuestaError('Tipo no valido. Usa "publicacion" o "comentario".');
}

respuestaError('Metodo no permitido.', 405);