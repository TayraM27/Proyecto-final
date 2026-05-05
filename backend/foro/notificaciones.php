<?php
/*--------------------------------------------------------------------------------------------
GET  — lista notificaciones del usuario */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

iniciarSesionSegura();

$idUsuario = $_SESSION['idUsuario'] ?? null;
$idProtectora = $_SESSION['idProtectora'] ?? null;

if (!$idUsuario && !$idProtectora) {
    respuestaOk(['notificaciones' => [], 'noLeidas' => 0]);
}

/* --------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    if ($idUsuario) {
        $stmt = $pdo->prepare("
            SELECT n.*, n.idPublicacion, n.ruta_destino
            FROM notificaciones n
            WHERE n.idUsuario = ?
            ORDER BY n.fecha DESC
            LIMIT 50
        ");
        $stmt->execute([$idUsuario]);
    } elseif ($idProtectora) {
        $stmt = $pdo->prepare("
            SELECT n.*, n.idPublicacion, n.ruta_destino
            FROM notificaciones n
            WHERE n.idProtectora = ?
            ORDER BY n.fecha DESC
            LIMIT 50
        ");
        $stmt->execute([$idProtectora]);
    } else {
        respuestaOk(['notificaciones' => [], 'noLeidas' => 0]);
    }

    $notificaciones = $stmt->fetchAll();
    $noLeidas = array_filter($notificaciones, function($n) { return !$n['leida']; });

    respuestaOk([
        'notificaciones' => $notificaciones,
        'noLeidas' => count($noLeidas)
    ]);
}

/* --------------------------------------------------------------------------------------------
POST */
if ($metodo === 'POST' && ($idUsuario || $idProtectora)) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['leer'])) {
        $idNotif = (int)($input['id'] ?? 0);
        if ($idNotif) {
            if ($idUsuario) {
                $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idNotificacion = ? AND idUsuario = ?");
                $stmt->execute([$idNotif, $idUsuario]);
            } else {
                $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idNotificacion = ? AND idProtectora = ?");
                $stmt->execute([$idNotif, $idProtectora]);
            }
        }
    }

    if (isset($input['leerTodos'])) {
        if ($idUsuario) {
            $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idUsuario = ?");
            $stmt->execute([$idUsuario]);
        } else {
            $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idProtectora = ?");
            $stmt->execute([$idProtectora]);
        }
    }

    respuestaOk(['ok' => true]);
}

/* --------------------------------------------------------------------------------------------
DELETE */
if ($metodo === 'DELETE' && ($idUsuario || $idProtectora)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $idNotif = (int)($input['idNotificacion'] ?? 0);

    if ($idNotif) {
        if ($idUsuario) {
            $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE idNotificacion = ? AND idUsuario = ?");
            $stmt->execute([$idNotif, $idUsuario]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE idNotificacion = ? AND idProtectora = ?");
            $stmt->execute([$idNotif, $idProtectora]);
        }
    }

    respuestaOk(['ok' => true]);
}

respuestaOk(['ok' => true]);