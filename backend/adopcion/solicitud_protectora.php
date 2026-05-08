<?php
/*--------------------------------------------------------------------------------------------
Gestión de solicitudes de cuenta de protectora

GET  — Lista de solicitudes (admin) o estado de la propia solicitud (usuario)
POST — Crear nueva solicitud (usuario logueado)
PATCH — Revisar solicitud: aprobar/rechazar/pedir info (admin)
PUT  — Responder a solicitud de info adicional (usuario) */

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (esAdmin()) {
            $stmt = $pdo->prepare(
                'SELECT s.*, u.nombre AS nombre_usuario, u.email AS email_usuario, u.username,
                        adm.nombre AS nombre_admin
                 FROM solicitudes_protectora s
                 JOIN usuarios u ON s.idUsuario = u.idUsuario
                 LEFT JOIN usuarios adm ON s.id_admin_responde = adm.idUsuario
                 ORDER BY s.fecha_solicitud DESC'
            );
            $stmt->execute();
            respuestaOk(['solicitudes' => $stmt->fetchAll()]);
        }

        if (usuarioLogueado()) {
            $stmt = $pdo->prepare(
                'SELECT s.*, u.nombre AS nombre_usuario
                 FROM solicitudes_protectora s
                 JOIN usuarios u ON s.idUsuario = u.idUsuario
                 WHERE s.idUsuario = ?
                 ORDER BY s.fecha_solicitud DESC
                 LIMIT 1'
            );
            $stmt->execute([$_SESSION['idUsuario']]);
            $solicitud = $stmt->fetch();
            respuestaOk(['solicitud' => $solicitud ?: null]);
        }

        respuestaError('Debes iniciar sesión.', 401);
        break;

    case 'POST':
        if (!usuarioLogueado()) {
            respuestaError('Debes iniciar sesión para solicitar una cuenta de protectora.', 401);
        }

        if ($_SESSION['rol'] !== 'usuario') {
            respuestaError('Solo los usuarios con rol "usuario" pueden solicitar esta cuenta.', 400);
        }

        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombreProtectora = limpiar($datos['nombre_protectora'] ?? '');
        $cifNif           = limpiar($datos['cif_nif'] ?? '');
        $direccion        = limpiar($datos['direccion'] ?? '');
        $localidad        = limpiar($datos['localidad'] ?? '');
        $telefono         = limpiar($datos['telefono'] ?? '');
        $webRedes         = limpiar($datos['web_redes'] ?? '');

        if (!$nombreProtectora || !$cifNif || !$direccion) {
            respuestaError('Faltan campos obligatorios: nombre de la protectora, CIF/NIF y dirección.');
        }

        $stmt = $pdo->prepare(
            'SELECT idSolicitud FROM solicitudes_protectora
             WHERE idUsuario = ? AND estado IN ("pendiente","info_adicional")
             LIMIT 1'
        );
        $stmt->execute([$_SESSION['idUsuario']]);
        if ($stmt->fetch()) {
            respuestaError('Ya tienes una solicitud pendiente. Espera a que sea revisada.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes_protectora
                (idUsuario, nombre_protectora, cif_nif, direccion, localidad, telefono, web_redes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['idUsuario'],
            $nombreProtectora,
            $cifNif,
            $direccion,
            $localidad,
            $telefono ?: null,
            $webRedes ?: null,
        ]);

        $idSolicitud = $pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE rol = "admin" AND activo = 1');
        $stmt->execute();
        $admins = $stmt->fetchAll();

        foreach ($admins as $admin) {
            crearNotificacion(
                $admin['idUsuario'],
                'solicitud_protectora',
                'Nueva solicitud de cuenta de protectora: ' . $nombreProtectora,
                'admin/solicitudes-protectora.html'
            );
        }

        respuestaOk([
            'idSolicitud' => $idSolicitud,
            'mensaje' => 'Solicitud enviada correctamente. Será revisada por un administrador.',
        ]);
        break;

    case 'PATCH':
        if (!esAdmin()) {
            respuestaError('Acceso restringido a administradores.', 403);
        }

        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $idSolicitud    = (int)($datos['idSolicitud'] ?? 0);
        $nuevoEstado    = limpiar($datos['estado'] ?? '');
        $respuestaAdmin = limpiar($datos['respuesta_admin'] ?? '');
        $idProtectora   = isset($datos['idProtectora']) ? (int)$datos['idProtectora'] : null;

        if (!$idSolicitud || !in_array($nuevoEstado, ['aprobada', 'rechazada', 'info_adicional'])) {
            respuestaError('Faltan datos obligatorios o el estado no es valido.');
        }

        $stmt = $pdo->prepare('SELECT * FROM solicitudes_protectora WHERE idSolicitud = ?');
        $stmt->execute([$idSolicitud]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            respuestaError('Solicitud no encontrada.', 404);
        }

        if ($solicitud['estado'] !== 'pendiente' && $solicitud['estado'] !== 'info_adicional') {
            respuestaError('Esta solicitud ya ha sido revisada.', 400);
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE solicitudes_protectora
                 SET estado = ?, respuesta_admin = ?, id_admin_responde = ?, fecha_respuesta = NOW()
                 WHERE idSolicitud = ?'
            );
            $stmt->execute([
                $nuevoEstado,
                $respuestaAdmin ?: null,
                $_SESSION['idUsuario'],
                $idSolicitud,
            ]);

            if ($nuevoEstado === 'aprobada') {
                $protectoraId = $idProtectora;

                if (!$protectoraId) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO protectoras (nombre, direccion, localidad, telefono, email, web, red_social_url, verificada, activa, fecha_registro)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())'
                    );
                    $stmt->execute([
                        $solicitud['nombre_protectora'],
                        $solicitud['direccion'],
                        $solicitud['localidad'] ?? null,
                        $solicitud['telefono'] ?: null,
                        $solicitud['email_usuario'] ?? null,
                        $solicitud['web_redes'] ?: null,
                        $solicitud['web_redes'] ?: null,
                    ]);
                    $protectoraId = (int)$pdo->lastInsertId();
                }

                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET rol = "protectora", idProtectora = ? WHERE idUsuario = ?'
                );
                $stmt->execute([$protectoraId, $solicitud['idUsuario']]);

                $pdo->prepare(
                    'UPDATE protectoras SET idUsuario = ? WHERE idProtectora = ? AND idUsuario IS NULL'
                )->execute([$solicitud['idUsuario'], $protectoraId]);
            }

            $pdo->commit();

            $tiposEstado = [
                'aprobada' => 'protectora_aprobada',
                'rechazada' => 'protectora_rechazada',
                'info_adicional' => 'protectora_info_adicional',
            ];

            $mensajes = [
                'aprobada' => 'Tu solicitud para "' . $solicitud['nombre_protectora'] . '" ha sido aprobada. Ya puedes acceder al panel de gestion desde tu perfil o directamente desde admin/mi-protectora.html',
                'rechazada' => 'Tu solicitud para "' . $solicitud['nombre_protectora'] . '" ha sido rechazada.' . ($respuestaAdmin ? ' Motivo: ' . $respuestaAdmin : '') . ' Si crees que es un error, contacta con nosotros.',
                'info_adicional' => 'El administrador necesita informacion adicional sobre tu solicitud para "' . $solicitud['nombre_protectora'] . '".' . ($respuestaAdmin ? ' ' . $respuestaAdmin : '') . ' Responde desde tu perfil (tab: Solicitud) para que puedan revisarla de nuevo.',
            ];

            $rutas = [
                'aprobada' => 'admin/mi-protectora.html',
                'rechazada' => 'perfil.html?tab=solicitud-protectora',
                'info_adicional' => 'perfil.html?tab=solicitud-protectora',
            ];

            crearNotificacion(
                $solicitud['idUsuario'],
                $tiposEstado[$nuevoEstado],
                $mensajes[$nuevoEstado],
                $rutas[$nuevoEstado]
            );

            respuestaOk(['mensaje' => 'Solicitud actualizada correctamente.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            respuestaError('Error al procesar la solicitud: ' . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        if (!usuarioLogueado()) {
            respuestaError('Debes iniciar sesión.', 401);
        }
        $idSolicitud = (int)($_POST['idSolicitud'] ?? 0);
        $respuestaUsuario = limpiar($_POST['respuesta'] ?? '');

        if (!$idSolicitud || !$respuestaUsuario) {
            respuestaError('Faltan datos obligatorios.');
        }

        $archivos = [];
        if (!empty($_FILES['archivos'])) {
            $archivosNombres = $_FILES['archivos']['name'];
            $archivosTmp = $_FILES['archivos']['tmp_name'];
            $archivosError = $_FILES['archivos']['error'];
            $nombres = is_array($archivosNombres) ? $archivosNombres : [$archivosNombres];
            $dir = __DIR__ . '/../../uploads/solicitudes_protectora/';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            foreach ($nombres as $i => $name) {
                $error = is_array($archivosError) ? ($archivosError[$i] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) continue;
                $tmp = is_array($archivosTmp) ? ($archivosTmp[$i] ?? '') : '';
                if (!$tmp) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $permitidos = ['jpg','jpeg','png','gif','webp','pdf','doc','docx'];
                if (!in_array($ext, $permitidos)) continue;
                $nombre = uniqid('sol_') . '.' . $ext;
                move_uploaded_file($tmp, $dir . $nombre);
                $archivos[] = 'uploads/solicitudes_protectora/' . $nombre;
            }
        }

        $stmt = $pdo->prepare('SELECT s.*, u.nombre AS nombre_usuario FROM solicitudes_protectora s JOIN usuarios u ON s.idUsuario = u.idUsuario WHERE s.idSolicitud = ? AND s.idUsuario = ?');
        $stmt->execute([$idSolicitud, $_SESSION['idUsuario']]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            respuestaError('Solicitud no encontrada.', 404);
        }

        if ($solicitud['estado'] !== 'info_adicional') {
            respuestaError('Solo puedes responder cuando el administrador solicita informacion adicional.');
        }

        $pdo->beginTransaction();

        try {
            $archivosJson = !empty($archivos) ? json_encode($archivos) : null;
            $stmt = $pdo->prepare(
                'UPDATE solicitudes_protectora
                 SET estado = "pendiente", respuesta_usuario = ?, archivos_respuesta = ?, fecha_respuesta_usuario = NOW(), fecha_respuesta = NULL, id_admin_responde = NULL
                 WHERE idSolicitud = ?'
            );
            $stmt->execute([$respuestaUsuario, $archivosJson, $idSolicitud]);

            $stmt = $pdo->prepare('SELECT idUsuario, nombre FROM usuarios WHERE rol = "admin" AND activo = 1');
            $stmt->execute();
            $admins = $stmt->fetchAll();

            foreach ($admins as $admin) {
                crearNotificacion(
                    $admin['idUsuario'],
                    'solicitud_protectora_respuesta',
                    $solicitud['nombre_usuario'] . ' ha respondido a la solicitud de info adicional para "' . $solicitud['nombre_protectora'] . '"',
                    'admin/solicitudes-protectora.html'
                );
            }

            $pdo->commit();
            respuestaOk(['mensaje' => 'Respuesta enviada. El administrador revisara tu solicitud de nuevo.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            respuestaError('Error al enviar la respuesta: ' . $e->getMessage(), 500);
        }
        break;

    default:
        respuestaError('Metodo no permitido.', 405);
}
