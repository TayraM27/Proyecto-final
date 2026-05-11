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
    $q      = trim($_GET['q']     ?? '');
    $estado = $_GET['estado']        ?? 'todos';
    $rol    = $_GET['rol']           ?? 'todos';
    $pagina = (int)($_GET['pagina']  ?? 1);
    $p      = pagina($pagina, 20);

    $where  = ['1'];
    $params = [];

    if ($q) {
        $where[]  = '(nombre LIKE ? OR username LIKE ? OR email LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($estado === 'activo')      { $where[] = 'activo = 1'; }
    if ($estado === 'bloqueado')   { $where[] = 'activo = 0'; }
    if ($rol === 'admin')          { $where[] = "rol = 'admin'"; }
    if ($rol === 'usuario')        { $where[] = "rol = 'usuario'"; }
    if ($rol === 'protectora')     { $where[] = "rol = 'protectora'"; }

    $cond = implode(' AND ', $where);

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT u.idUsuario, u.nombre, u.username, u.email,
                   CASE WHEN u.rol = 'protectora' THEN p.localidad ELSE u.localidad END AS localidad, u.rol, u.activo,
                   u.fecha_registro, u.ultimo_login
            FROM usuarios u
            LEFT JOIN protectoras p ON p.idUsuario = u.idUsuario
            WHERE $cond
            ORDER BY u.rol DESC, u.fecha_registro DESC
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
PUT — bloquear / desbloquear (el admin no puede modificar roles) */
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
    respuestaError('Acción no permitida. El admin solo puede bloquear o desbloquear usuarios.', 403);
}

respuestaError('Método no permitido.', 405);