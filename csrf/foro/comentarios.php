<?php
/*--------------------------------------------------------------------------------------------
GET  ?idPublicacion=1 — lista comentarios de una publicacion
POST — crea comentario (requiere login)
PUT  — like a un comentario (requiere login) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar comentarios */
if ($metodo === 'GET') {
    $idPublicacion = (int)($_GET['idPublicacion'] ?? 0);

    if (!$idPublicacion) {
        respuestaError('ID de publicacion no valido.');
    }

    $stmt = $pdo->prepare(
        'SELECT
            c.idComentario,
            c.contenido,
            c.num_likes,
            c.fecha,
            u.nombre      AS autor_nombre,
            u.username    AS autor_username,
            u.rol         AS autor_rol,
            u.foto_perfil AS autor_foto
         FROM comentarios c
         JOIN usuarios u ON c.idUsuario = u.idUsuario
         WHERE c.idPublicacion = ? AND c.activo = 1
         ORDER BY c.fecha ASC'
    );
    $stmt->execute([$idPublicacion]);

    // Incrementar vistas de la publicacion
    $pdo->prepare('UPDATE publicaciones SET num_vistas = num_vistas + 1 WHERE idPublicacion = ?')
        ->execute([$idPublicacion]);

    respuestaOk(['comentarios' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
POST - crear comentario */
if ($metodo === 'POST') {
    requerirLogin();

    $datos         = json_decode(file_get_contents('php://input'), true) ?? [];
    $idPublicacion = (int)($datos['idPublicacion'] ?? 0);
    $contenido     = limpiar($datos['contenido']   ?? '');

    if (!$idPublicacion || !$contenido) {
        respuestaError('Publicacion y contenido son obligatorios.');
    }

    // Verificar que la publicacion existe y esta activa
    $stmt = $pdo->prepare('SELECT idPublicacion FROM publicaciones WHERE idPublicacion = ? AND activa = 1 LIMIT 1');
    $stmt->execute([$idPublicacion]);
    if (!$stmt->fetch()) {
        respuestaError('Publicacion no encontrada.', 404);
    }

    $idUsuario = (int)$_SESSION['idUsuario'];

    $stmt = $pdo->prepare(
        'INSERT INTO comentarios (idPublicacion, idUsuario, contenido) VALUES (?, ?, ?)'
    );
    $stmt->execute([$idPublicacion, $idUsuario, $contenido]);

    respuestaOk([
        'mensaje'      => 'Comentario publicado.',
        'idComentario' => $pdo->lastInsertId(),
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT - dar like a un comentario */
if ($metodo === 'PUT') {
    requerirLogin();

    $datos        = json_decode(file_get_contents('php://input'), true) ?? [];
    $idComentario = (int)($datos['idComentario'] ?? 0);

    if (!$idComentario) {
        respuestaError('ID de comentario no valido.');
    }

    $pdo->prepare('UPDATE comentarios SET num_likes = num_likes + 1 WHERE idComentario = ?')
        ->execute([$idComentario]);

    respuestaOk(['mensaje' => 'Like registrado.']);
}

respuestaError('Metodo no permitido.', 405);