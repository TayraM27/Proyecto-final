<?php
/*--------------------------------------------------------------------------------------------
api/foro/publicaciones.php
GET  — lista publicaciones con filtro de categoria y busqueda
POST — crea nueva publicacion (requiere login)
Usado por foro.html */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar publicaciones */
if ($metodo === 'GET') {
    $categoria = $_GET['categoria'] ?? 'todas';
    $busqueda  = limpiar($_GET['q'] ?? '');
    $pagina    = (int)($_GET['pagina'] ?? 1);
    $p         = paginacion($pagina, 15);

    $where  = ['pub.activa = 1'];
    $params = [];

    $categoriasValidas = ['adopcion', 'cuidados', 'acogida', 'salud', 'informacion'];
    if ($categoria !== 'todas' && in_array($categoria, $categoriasValidas)) {
        $where[]  = 'pub.categoria = ?';
        $params[] = $categoria;
    }

    if ($busqueda) {
        $where[]  = '(pub.titulo LIKE ? OR pub.contenido LIKE ?)';
        $params[] = '%' . $busqueda . '%';
        $params[] = '%' . $busqueda . '%';
    }

    $condicion = implode(' AND ', $where);

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM publicaciones pub WHERE $condicion");
    $stmtTotal->execute($params);
    $total = (int)$stmtTotal->fetch()['total'];

    $sql = "SELECT
                pub.idPublicacion,
                pub.titulo,
                pub.contenido,
                pub.categoria,
                pub.imagen,
                pub.num_likes,
                pub.num_vistas,
                pub.fijada,
                pub.fecha,
                u.nombre   AS autor_nombre,
                u.username AS autor_username,
                u.rol      AS autor_rol,
                u.foto_perfil AS autor_foto,
                (SELECT COUNT(*) FROM comentarios c WHERE c.idPublicacion = pub.idPublicacion AND c.activo = 1)
                    AS num_comentarios
            FROM publicaciones pub
            JOIN usuarios u ON pub.idUsuario = u.idUsuario
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

    $datos     = json_decode(file_get_contents('php://input'), true) ?? [];
    $titulo    = limpiar($datos['titulo']    ?? '');
    $contenido = limpiar($datos['contenido'] ?? '');
    $categoria = $datos['categoria']         ?? 'informacion';

    if (!$titulo || !$contenido) {
        respuestaError('Titulo y contenido son obligatorios.');
    }

    if (strlen($titulo) > 200) {
        respuestaError('El titulo no puede superar 200 caracteres.');
    }

    $categoriasValidas = ['adopcion', 'cuidados', 'acogida', 'salud', 'informacion'];
    if (!in_array($categoria, $categoriasValidas)) {
        $categoria = 'informacion';
    }

    $idUsuario = (int)$_SESSION['idUsuario'];

    $stmt = $pdo->prepare(
        'INSERT INTO publicaciones (idUsuario, titulo, contenido, categoria)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$idUsuario, $titulo, $contenido, $categoria]);

    respuestaOk([
        'mensaje'       => 'Publicacion creada.',
        'idPublicacion' => $pdo->lastInsertId(),
    ]);
}

respuestaError('Metodo no permitido.', 405);