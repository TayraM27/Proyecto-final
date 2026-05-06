<?php
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];

iniciarSesionSegura();

$idUsuario = $_SESSION['idUsuario'] ?? null;
$idProtectora = $_SESSION['idProtectora'] ?? null;

if (!$idUsuario && !$idProtectora) {
    respuestaOk(['success' => true, 'notificaciones' => [], 'total' => 0]);
}

if ($metodo === 'GET') {
    if ($idUsuario) {
        $stmt = $pdo->prepare(
            "SELECT n.*, n.idPublicacion, n.ruta_destino
             FROM notificaciones n
             WHERE n.idUsuario = ?
             ORDER BY n.fecha DESC LIMIT 50"
        );
        $stmt->execute([$idUsuario]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT n.*, n.idPublicacion, n.ruta_destino
             FROM notificaciones n
             WHERE n.idProtectora = ?
             ORDER BY n.fecha DESC LIMIT 50"
        );
        $stmt->execute([$idProtectora]);
    }
    $notificaciones = $stmt->fetchAll();
    $noLeidas = 0;
    foreach ($notificaciones as $n) {
        if (!$n['leida']) $noLeidas++;
    }
    respuestaOk(['success' => true, 'notificaciones' => $notificaciones, 'total' => $noLeidas]);
}

if ($metodo === 'POST' && ($idUsuario || $idProtectora)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['leer'])) {
        $idNotif = (int)($input['id'] ?? 0);
        if ($idNotif) {
            if ($idUsuario) {
                $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idNotificacion = ? AND idUsuario = ?")
                     ->execute([$idNotif, $idUsuario]);
            } else {
                $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idNotificacion = ? AND idProtectora = ?")
                     ->execute([$idNotif, $idProtectora]);
            }
        }
    }
    if (isset($input['leerTodos'])) {
        if ($idUsuario) {
            $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idUsuario = ?")
                 ->execute([$idUsuario]);
        } else {
            $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE idProtectora = ?")
                 ->execute([$idProtectora]);
        }
    }
    respuestaOk(['success' => true]);
}

if ($metodo === 'DELETE' && ($idUsuario || $idProtectora)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $idNotif = (int)($input['idNotificacion'] ?? 0);
    if ($idNotif) {
        if ($idUsuario) {
            $pdo->prepare("DELETE FROM notificaciones WHERE idNotificacion = ? AND idUsuario = ?")
                 ->execute([$idNotif, $idUsuario]);
        } else {
            $pdo->prepare("DELETE FROM notificaciones WHERE idNotificacion = ? AND idProtectora = ?")
                 ->execute([$idNotif, $idProtectora]);
        }
    }
    respuestaOk(['success' => true]);
}

respuestaError('Método no permitido.', 405);
