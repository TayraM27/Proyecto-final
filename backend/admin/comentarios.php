<?php
/*--------------------------------------------------------------------------------------------
Panel de administración: moderación de comentarios
GET  — lista todos los comentarios (incluidos eliminados)
PUT  ?accion=restore — restaurar comentario eliminado
DELETE                — eliminar comentario (admin) */

require_once __DIR__ . '/../includes/funciones.php';
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

/*--------------------------------------------------------------------------------------------
GET — listar todos los comentarios */
if ($metodo === 'GET') {
    $idPublicacion = (int)($_GET['idPublicacion'] ?? 0);
    $pagina = (int)($_GET['pagina'] ?? 1);
    $limite = 20;
    $offset = ($pagina - 1) * $limite;

    $where = '';
    $params = [];
    if ($idPublicacion) {
        $where = 'WHERE c.idPublicacion = ?';
        $params[] = $idPublicacion;
    }

    $sqlTotal = "SELECT COUNT(*) as total FROM comentarios c $where";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $total = (int)$stmtTotal->fetch()['total'];

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
                p.email       AS prot_email,
                pub.titulo    AS pub_titulo
            FROM comentarios c
            LEFT JOIN usuarios u ON c.idUsuario = u.idUsuario
            LEFT JOIN protectoras p ON c.idProtectora = p.idProtectora
            LEFT JOIN publicaciones pub ON c.idPublicacion = pub.idPublicacion
            $where
            ORDER BY c.fecha DESC
            LIMIT $limite OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $comentarios = $stmt->fetchAll();

    respuestaOk([
        'comentarios' => $comentarios,
        'total' => $total,
        'pagina' => $pagina,
        'totalPaginas' => ceil($total / $limite)
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT ?accion=restore — restaurar comentario */
if ($metodo === 'PUT' && $accion === 'restore') {
    $datos = jsonInput();
    $idComentario = (int)($datos['idComentario'] ?? 0);
    if (!$idComentario) respuestaError('ID de comentario no válido.');

    $stmt = $pdo->prepare('SELECT deleted FROM comentarios WHERE idComentario = ?');
    $stmt->execute([$idComentario]);
    $com = $stmt->fetch();
    if (!$com) respuestaError('Comentario no encontrado.');
    if (!$com['deleted']) respuestaError('El comentario no esta eliminado.');

    $pdo->prepare('UPDATE comentarios SET deleted = 0, deleted_at = NULL WHERE idComentario = ?')
        ->execute([$idComentario]);

    respuestaOk(['mensaje' => 'Comentario restaurado.']);
}

/*--------------------------------------------------------------------------------------------
DELETE — eliminar comentario (admin) */
if ($metodo === 'DELETE') {
    $datos = jsonInput();
    $idComentario = (int)($datos['idComentario'] ?? 0);
    if (!$idComentario) respuestaError('ID de comentario no válido.');

    $stmt = $pdo->prepare('SELECT idPublicacion, parent_id FROM comentarios WHERE idComentario = ?');
    $stmt->execute([$idComentario]);
    $com = $stmt->fetch();
    if (!$com) respuestaError('Comentario no encontrado.');

    $pdo->prepare('DELETE FROM comentarios WHERE idComentario = ?')->execute([$idComentario]);

    if (!$com['parent_id']) {
        $pdo->prepare('UPDATE publicaciones SET num_comentarios = GREATEST(0, num_comentarios - 1) WHERE idPublicacion = ?')
            ->execute([$com['idPublicacion']]);
    }

    respuestaOk(['mensaje' => 'Comentario eliminado permanentemente.']);
}

respuestaError('Método no permitido.', 405);