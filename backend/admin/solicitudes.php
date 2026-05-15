<?php
/*--------------------------------------------------------------------------------------------
GET — lista solicitudes con filtro de estado y paginación
PUT — cambia estado de una solicitud */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

requerirAdminOProtectora();

$pdo    = conectar();
$metodo = $_SERVER['REQUEST_METHOD'];
$esAdmin = esAdmin();
$idProtectoraUsuario = getIdProtectoraUsuario();

session_write_close();

/*--------------------------------------------------------------------------------------------
GET */
if ($metodo === 'GET') {
    $estado = $_GET['estado'] ?? 'todos';
    $pagina = (int)($_GET['pagina'] ?? 1);
    $p      = pagina($pagina, 15);

    $where  = [];
    $params = [];

    if (!$esAdmin && $idProtectoraUsuario) {
        $where[] = 'm.idProtectora = ?';
        $params[] = $idProtectoraUsuario;
    }

    $estadosValidos = ['pendiente','en_revision','aprobada','rechazada'];
    if ($estado !== 'todos' && in_array($estado, $estadosValidos)) {
        $where[]  = 's.estado = ?';
        $params[] = $estado;
    }

    $cond = $where ? implode(' AND ', $where) : '1';

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM solicitudes_adopcion s JOIN mascotas m ON s.idMascota = m.idMascota WHERE $cond");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT
                s.idSolicitud,
                s.nombre, s.email, s.telefono,
                s.dni, s.fecha_nacimiento,
                s.direccion_completa,
                s.localidad,
                s.tipo_vivienda, s.vivienda_en_propiedad, s.permiso_propietario,
                s.personas_en_hogar, s.ninos_en_hogar,
                s.otros_animales, s.descripcion_otros_animales,
                s.experiencia_animales, s.tiempo_fuera_casa,
                s.motivo_adopcion, s.mensaje, s.mensaje_protectora,
                s.estado, s.fecha_envio, s.fecha_gestion,
                m.nombre   AS mascota,
                m.especie  AS mascota_especie,
                p.nombre   AS protectora
            FROM solicitudes_adopcion s
            JOIN mascotas    m ON s.idMascota    = m.idMascota
            JOIN protectoras p ON m.idProtectora = p.idProtectora
            WHERE $cond
            ORDER BY s.fecha_envio DESC
            LIMIT ? OFFSET ?";

    $params[] = $p['limite'];
    $params[] = $p['offset'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respuestaOk([
        'solicitudes'  => $stmt->fetchAll(),
        'total'        => $total,
        'pagina'       => $p['pagina'],
        'totalPaginas' => (int)ceil($total / $p['limite']),
    ]);
}

/*--------------------------------------------------------------------------------------------
PUT — cambiar estado (SOLO PROTECTORA sobre solicitudes de sus mascotas, admin NO puede) */
if ($metodo === 'PUT') {
    if ($esAdmin) {
        respuestaError('Los administradores no pueden gestionar solicitudes. Esta acción corresponde a la protectora.', 403);
    }

    if (!$idProtectoraUsuario) respuestaError('No tienes una protectora asignada.');

    $datos       = json_decode(file_get_contents('php://input'), true) ?? [];
    $id          = (int)($datos['idSolicitud'] ?? 0);
    $nuevoEstado = trim($datos['estado'] ?? '');
    $mensajeProt = trim($datos['mensaje_protectora'] ?? '');

    if (!$id) respuestaError('idSolicitud requerido.');

    $estadosValidos = ['pendiente','en_revision','aprobada','rechazada'];
    if (!in_array($nuevoEstado, $estadosValidos)) respuestaError('Estado no válido.');

    // Verificar que la solicitud pertenece a una mascota de esta protectora
    $stmt = $pdo->prepare(
        'SELECT s.idSolicitud, s.idMascota FROM solicitudes_adopcion s
         JOIN mascotas m ON s.idMascota = m.idMascota
         WHERE s.idSolicitud = ? AND m.idProtectora = ?'
    );
    $stmt->execute([$id, $idProtectoraUsuario]);
    if (!$stmt->fetch()) respuestaError('No puedes gestionar esta solicitud.', 403);

    if ($mensajeProt) {
        $pdo->prepare('UPDATE solicitudes_adopcion SET estado = ?, mensaje_protectora = ?, fecha_gestion = NOW() WHERE idSolicitud = ?')
            ->execute([$nuevoEstado, $mensajeProt, $id]);
    } else {
        $pdo->prepare('UPDATE solicitudes_adopcion SET estado = ?, fecha_gestion = NOW() WHERE idSolicitud = ?')
            ->execute([$nuevoEstado, $id]);
    }

    /* Obtener datos de la solicitud para notificación */
    $stmtInfo = $pdo->prepare(
        'SELECT s.idUsuario, s.nombre, m.nombre AS mascota FROM solicitudes_adopcion s
         JOIN mascotas m ON s.idMascota = m.idMascota WHERE s.idSolicitud = ?'
    );
    $stmtInfo->execute([$id]);
    $infoSol = $stmtInfo->fetch();

    if ($nuevoEstado === 'aprobada') {
        $stmtMasc = $pdo->prepare('SELECT idMascota FROM solicitudes_adopcion WHERE idSolicitud = ?');
        $stmtMasc->execute([$id]);
        $solMasc = $stmtMasc->fetch();
        if ($solMasc) {
            $pdo->prepare("UPDATE mascotas SET estado_adopcion = 'en_proceso' WHERE idMascota = ?")
                ->execute([$solMasc['idMascota']]);
        }
        if ($infoSol && $infoSol['idUsuario']) {
            crearNotificacion((int)$infoSol['idUsuario'], 'aprobacion',
                'Tu solicitud de adopción para ' . $infoSol['mascota'] . ' ha sido aprobada.',
                '../html/perfil.html?tab=apadrinamientos');
        }
    }

    if ($nuevoEstado === 'rechazada' && $infoSol && $infoSol['idUsuario']) {
        crearNotificacion((int)$infoSol['idUsuario'], 'rechazo',
            'Tu solicitud de adopción para ' . $infoSol['mascota'] . ' ha sido revisada. Contacta con la protectora para más información.',
            '../html/perfil.html?tab=apadrinamientos');
    }

    /* Notificar a todos los administradores */
    if ($nuevoEstado === 'aprobada' || $nuevoEstado === 'rechazada') {
        $accionTexto = $nuevoEstado === 'aprobada' ? 'APROBADA' : 'RECHAZADA';
        $stmtAdmins = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE rol = "admin" AND activo = 1');
        $stmtAdmins->execute();
        while ($admin = $stmtAdmins->fetch()) {
            $pdo->prepare(
                'INSERT INTO notificaciones (idUsuario, tipo, mensaje, ruta_destino)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $admin['idUsuario'],
                'solicitud_' . $nuevoEstado,
                'Solicitud de adopción para ' . $infoSol['mascota'] . ' de ' . $infoSol['nombre'] . ' ha sido ' . $accionTexto . '.',
                'admin/solicitudes.html',
            ]);
        }
    }

    respuestaOk(['mensaje' => 'Estado actualizado correctamente.']);
}

respuestaError('Método no permitido.', 405);