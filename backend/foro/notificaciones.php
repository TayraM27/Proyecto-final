<?php
/*--------------------------------------------------------------------------------------------
backend/foro/notificaciones.php
GET  ?ultimas=5        — últimas N notificaciones (para dropdown navbar)
GET  ?solo_no_leidas=1 — solo no leídas
GET                    — todas (máx 80)
POST {leer:true, id}   — marcar una como leída
POST {leerTodos:true}  — marcar todas como leídas
(Sin DELETE: el usuario no puede eliminar notificaciones) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

iniciarSesionSegura();
$idUsuario    = $_SESSION['idUsuario']    ?? null;
$idProtectora = $_SESSION['idProtectora'] ?? null;
session_write_close();

if (!$idUsuario && !$idProtectora) {
    respuestaOk(['success' => true, 'notificaciones' => [], 'total' => 0, 'noLeidas' => 0]);
}

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $ultimas      = isset($_GET['ultimas'])        ? (int)$_GET['ultimas']       : 80;
    $soloNoLeidas = isset($_GET['solo_no_leidas']) && $_GET['solo_no_leidas'] == '1';

    $where  = $idUsuario ? 'n.idUsuario = ?' : 'n.idProtectora = ?';
    $params = [$idUsuario ?? $idProtectora];

    if ($soloNoLeidas) {
        $where .= ' AND n.leida = 0';
    }

    $stmt = $pdo->prepare(
        "SELECT n.idNotificacion, n.tipo, n.mensaje, n.leida, n.fecha,
                n.ruta_destino, n.idPublicacion
         FROM notificaciones n
         WHERE $where
         ORDER BY n.fecha DESC
         LIMIT $ultimas"
    );
    $stmt->execute($params);
    $notificaciones = $stmt->fetchAll();

    /* Contar no leídas del total (sin límite) */
    $stmtCount = $pdo->prepare(
        "SELECT COUNT(*) FROM notificaciones n WHERE $where AND n.leida = 0"
    );
    /* Quitar el filtro solo_no_leidas para contar sobre el total */
    $whereCount = $idUsuario ? 'idUsuario = ?' : 'idProtectora = ?';
    $stmtCount  = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE $whereCount AND leida = 0");
    $stmtCount->execute([$idUsuario ?? $idProtectora]);
    $noLeidas = (int)$stmtCount->fetchColumn();

    respuestaOk([
        'success'        => true,
        'notificaciones' => $notificaciones,
        'total'          => $noLeidas,
        'noLeidas'       => $noLeidas,
    ]);
}

/*--------------------------------------------------------------------------------------------
POST — marcar leída / marcar todas */
if ($metodo === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!empty($input['leerTodos'])) {
        if ($idUsuario) {
            $pdo->prepare('UPDATE notificaciones SET leida = 1 WHERE idUsuario = ?')->execute([$idUsuario]);
        } else {
            $pdo->prepare('UPDATE notificaciones SET leida = 1 WHERE idProtectora = ?')->execute([$idProtectora]);
        }
        respuestaOk(['success' => true, 'noLeidas' => 0]);
    }

    if (!empty($input['leer'])) {
        $idNotif = (int)($input['id'] ?? 0);
        if ($idNotif) {
            if ($idUsuario) {
                $pdo->prepare('UPDATE notificaciones SET leida = 1 WHERE idNotificacion = ? AND idUsuario = ?')
                    ->execute([$idNotif, $idUsuario]);
            } else {
                $pdo->prepare('UPDATE notificaciones SET leida = 1 WHERE idNotificacion = ? AND idProtectora = ?')
                    ->execute([$idNotif, $idProtectora]);
            }
        }
        /* Devolver nuevo total de no leídas */
        $whereCount = $idUsuario ? 'idUsuario = ?' : 'idProtectora = ?';
        $stmtCount  = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE $whereCount AND leida = 0");
        $stmtCount->execute([$idUsuario ?? $idProtectora]);
        respuestaOk(['success' => true, 'noLeidas' => (int)$stmtCount->fetchColumn()]);
    }

    respuestaOk(['success' => true]);
}

respuestaError('Método no permitido.', 405);