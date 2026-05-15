<?php
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    respuestaError('ID invalido');
}

$pdo = conectar();

$stmt = $pdo->prepare(
    'SELECT
        m.idMascota,
        m.nombre,
        m.especie,
        m.raza,
        m.sexo,
        m.tamanyo,
        m.color,
        m.descripcion,
        m.estado_salud,
        m.urgencia,
        m.estado_adopcion,
        m.disponible_apadrinamiento,
        m.disponible_acogida,
        m.compatible_ninos,
        m.compatible_perros,
        m.compatible_gatos,
        m.apto_piso,
        m.vacunado,
        m.esterilizado,
        m.microchip,
        m.desparasitado,
        m.num_vistas,
        m.fecha_nacimiento,
        m.fecha_entrada,
        p.idProtectora,
        p.nombre AS protectora_nombre,
        p.localidad AS protectora_localidad,
        p.localidad AS ubicacion,
        p.telefono AS telefono_protectora,
        p.email AS email_protectora,
        p.web AS web_protectora,
        p.bizum AS bizum_protectora,
        p.foto_logo AS logo_protectora,
        p.activa AS protectora_activa
     FROM mascotas m
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE m.idMascota = ? AND m.activa = 1
     LIMIT 1'
);
$stmt->execute([$id]);
$mascota = $stmt->fetch();

if (!$mascota) {
    respuestaError('Mascota no encontrada', 404);
}

$pdo->prepare('UPDATE mascotas SET num_vistas = num_vistas + 1 WHERE idMascota = ?')
    ->execute([$id]);
$mascota['num_vistas']++;

$stmtFotos = $pdo->prepare(
    'SELECT ruta, es_principal
     FROM mascotas_fotos
     WHERE idMascota = ?
     ORDER BY es_principal DESC, orden ASC'
);
$stmtFotos->execute([$id]);
$mascota['fotos'] = $stmtFotos->fetchAll();

respuestaOk([
    'mascota'               => $mascota,
    'protectora_suspendida' => !(bool)$mascota['protectora_activa'],
]);
?>