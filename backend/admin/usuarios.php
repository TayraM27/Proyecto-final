<?php
/*--------------------------------------------------------------------------------------------
GET    — lista usuarios con filtros
PUT    — bloquear / desbloquear / hacer_admin / quitar_admin
DELETE — eliminar usuario (soft delete) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdmin();
session_write_close();

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $q      = limpiar($_GET['q']     ?? '');
    $estado = $_GET['estado']        ?? 'todos';
    $rol    = $_GET['rol']           ?? 'todos';
    $pagina = (int)($_GET['pagina']  ?? 1);
    $p      = paginacion($pagina, 20);

    $where  = ['1'];
    $params = [];

    if ($q) {
        $where[]  = '(nombre LIKE ? OR username LIKE ? OR email LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($estado === 'activo')    { $where[] = 'activo = 1'; }
    if ($estado === 'bloqueado') { $where[] = 'activo = 0'; }
    if ($rol === 'admin')        { $where[] = "rol = 'admin'"; }
    if ($rol === 'usuario')      { $where[] = "rol = 'usuario'"; }

    $cond = implode(' AND ', $where);

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT idUsuario, nombre, username, email, localidad, rol, activo,
                   fecha_registro, ultimo_login
            FROM usuarios
            WHERE $cond
            ORDER BY rol DESC, fecha_registro DESC
            LIMIT ? OFFSET ?";

    $params[] = $p['limite'];
    $params[] = $p['offset'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk([
        'usuarios'    => $stmt->fetchAll(),
        'total'       => $total,
        'pagina'      => $p['pagina'],
        'totalPaginas'=> (int)ceil($total / $p['limite']),
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT — bloquear / desbloquear / hacer_admin / quitar_admin */
if ($metodo === 'PUT') {
    $datos  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($datos['idUsuario'] ?? 0);
    $accion = $datos['accion'] ?? '';
    if (!$id) respuestaError('idUsuario requerido.');

    if ($accion === 'bloquear') {
        $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE idUsuario = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Usuario bloqueado.']);
    }
    if ($accion === 'desbloquear') {
        $pdo->prepare('UPDATE usuarios SET activo = 1 WHERE idUsuario = ?')->execute([$id]);
        respuestaOk(['mensaje' => 'Usuario desbloqueado.']);
    }
    if ($accion === 'hacer_admin') {
        $pdo->prepare("UPDATE usuarios SET rol = 'admin' WHERE idUsuario = ?")->execute([$id]);
        respuestaOk(['mensaje' => 'Usuario promovido a administrador.']);
    }
    if ($accion === 'quitar_admin') {
        $pdo->prepare("UPDATE usuarios SET rol = 'usuario' WHERE idUsuario = ?")->execute([$id]);
        respuestaOk(['mensaje' => 'Permisos de administrador retirados.']);
    }
    respuestaError('Acción no válida.');
}

/*--------------------------------------------------------------------------------------------
DELETE */
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($datos['idUsuario'] ?? 0);
    if (!$id) respuestaError('idUsuario requerido.');
    $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE idUsuario = ?')->execute([$id]);
    respuestaOk(['mensaje' => 'Usuario eliminado.']);
}

respuestaError('Método no permitido.', 405);