<?php
/*--------------------------------------------------------------------------------------------
Gestion de usuarios para el administrador
GET    — lista usuarios con filtros
PUT    — bloquea/desbloquea usuario
DELETE — elimina usuario (borrado logico) */

require_once __DIR__ . '/../includes/funciones.php';

requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar usuarios */
if ($metodo === 'GET') {
    $busqueda = limpiar($_GET['q']    ?? '');
    $estado   = $_GET['estado']       ?? 'todos';
    $rol      = $_GET['rol']          ?? 'todos';
    $pagina   = (int)($_GET['pagina'] ?? 1);
    $p        = paginacion($pagina, 20);

    $where  = ["rol != 'admin'"];
    $params = [];

    if ($busqueda) {
        $where[]  = '(nombre LIKE ? OR username LIKE ? OR email LIKE ?)';
        $like     = '%' . $busqueda . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($estado === 'activo') {
        $where[] = 'activo = 1';
    } elseif ($estado === 'bloqueado') {
        $where[] = 'activo = 0';
    }

    $condicion = implode(' AND ', $where);

    $total = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE $condicion");
    $total->execute($params);
    $total = (int)$total->fetchColumn();

    $params[] = $p['limite'];
    $params[] = $p['offset'];

    $stmt = $pdo->prepare(
        "SELECT idUsuario, nombre, username, email, localidad,
                rol, activo, fecha_registro, ultimo_login
         FROM usuarios
         WHERE $condicion
         ORDER BY fecha_registro DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute($params);

    respuestaOk([
        'usuarios'    => $stmt->fetchAll(),
        'total'       => $total,
        'totalPaginas'=> (int)ceil($total / $p['limite']),
        'pagina'      => $p['pagina'],
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT - bloquear / desbloquear */
if ($metodo === 'PUT') {
    $datos     = json_decode(file_get_contents('php://input'), true) ?? [];
    $idUsuario = (int)($datos['idUsuario'] ?? 0);
    $accion    = $datos['accion']          ?? '';

    if (!$idUsuario || !in_array($accion, ['bloquear', 'desbloquear'])) {
        respuestaError('Datos no validos.');
    }

    // No se puede bloquear a otro admin
    $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE idUsuario = ? LIMIT 1');
    $stmt->execute([$idUsuario]);
    $u = $stmt->fetch();

    if (!$u) {
        respuestaError('Usuario no encontrado.', 404);
    }
    if ($u['rol'] === 'admin') {
        respuestaError('No se puede bloquear a un administrador.');
    }

    $nuevoEstado = $accion === 'bloquear' ? 0 : 1;
    $pdo->prepare('UPDATE usuarios SET activo = ? WHERE idUsuario = ?')
        ->execute([$nuevoEstado, $idUsuario]);

    $mensaje = $accion === 'bloquear' ? 'Usuario bloqueado.' : 'Usuario desbloqueado.';
    respuestaOk(['mensaje' => $mensaje]);
}

/*--------------------------------------------------------------------------------------------
DELETE - borrado logico */
if ($metodo === 'DELETE') {
    $datos     = json_decode(file_get_contents('php://input'), true) ?? [];
    $idUsuario = (int)($datos['idUsuario'] ?? $_GET['id'] ?? 0);

    if (!$idUsuario) {
        respuestaError('ID de usuario requerido.');
    }

    $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE idUsuario = ? LIMIT 1');
    $stmt->execute([$idUsuario]);
    $u = $stmt->fetch();

    if (!$u) {
        respuestaError('Usuario no encontrado.', 404);
    }
    if ($u['rol'] === 'admin') {
        respuestaError('No se puede eliminar a un administrador.');
    }

    // Borrado logico
    $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE idUsuario = ?')
        ->execute([$idUsuario]);

    respuestaOk(['mensaje' => 'Usuario eliminado correctamente.']);
}

respuestaError('Metodo no permitido.', 405);