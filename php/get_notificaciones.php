<?php
/*--------------------------------------------------------------------------------------------
Devuelve las notificaciones del usuario logueado:
- Comentarios en sus publicaciones del foro
- Seguimientos de sus apadrinamientos (fotos, vídeos, mensajes de protectoras) */

require_once __DIR__ . '/../backend/config/db.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(['success' => false, 'notificaciones' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int)$_SESSION['idUsuario'];

try {
    $pdo = conectar();
    $notificaciones = [];

    /*------- comentarios en publicaciones del usuario -------*/
    $stmt = $pdo->prepare('
        SELECT
            c.idComentario AS id,
            "comentario"   AS tipo,
            u.nombre       AS actor,
            p.titulo       AS referencia,
            c.fecha        AS fecha
        FROM comentarios c
        JOIN publicaciones p ON c.idPublicacion = p.idPublicacion
        JOIN usuarios u      ON c.idUsuario     = u.idUsuario
        WHERE p.idUsuario = ?
          AND c.idUsuario != ?
          AND c.activo = 1
        ORDER BY c.fecha DESC
        LIMIT 10
    ');
    $stmt->execute([$idUsuario, $idUsuario]);
    foreach ($stmt->fetchAll() as $fila) {
        $notificaciones[] = $fila;
    }

    /*------- seguimientos de apadrinamientos del usuario -------*/
    $stmt = $pdo->prepare('
        SELECT
            s.idSeguimiento  AS id,
            s.tipo_archivo   AS tipo,
            p.nombre         AS actor,
            m.nombre         AS referencia,
            s.fecha          AS fecha
        FROM seguimientos s
        JOIN apadrinamientos a  ON s.idApadrinamiento = a.idApadrinamiento
        JOIN mascotas m         ON a.idMascota        = m.idMascota
        JOIN protectoras p      ON m.idProtectora     = p.idProtectora
        WHERE a.idUsuario = ?
          AND a.estado    = "activo"
        ORDER BY s.fecha DESC
        LIMIT 10
    ');
    $stmt->execute([$idUsuario]);
    foreach ($stmt->fetchAll() as $fila) {
        $notificaciones[] = $fila;
    }

    /*------- ordenar por fecha descendente -------*/
    usort($notificaciones, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    echo json_encode([
        'success'        => true,
        'notificaciones' => array_slice($notificaciones, 0, 15),
        'total'          => count($notificaciones)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'notificaciones' => []], JSON_UNESCAPED_UNICODE);
}
?>