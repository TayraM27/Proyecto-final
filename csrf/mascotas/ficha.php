<?php
/*--------------------------------------------------------------------------------------------
GET ?id=1 — devuelve todos los datos de una mascota para fichaAnimal.html
Incluye fotos, datos de la protectora y otras mascotas de la misma */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    respuestaError('ID de mascota no valido.');
}

$pdo = conectar();

// Datos principales
$stmt = $pdo->prepare(
    'SELECT
        m.idMascota,
        m.nombre,
        m.especie,
        m.raza,
        m.sexo,
        m.edad_texto,
        m.tamanyo,
        m.color,
        m.descripcion,
        m.estado_salud,
        m.urgencia,
        m.estado_adopcion,
        m.tiempo_en_adopcion,
        m.disponible_apadrinamiento,
        m.compatible_ninos,
        m.compatible_perros,
        m.compatible_gatos,
        m.apto_piso,
        m.vacunado,
        m.esterilizado,
        m.microchip,
        m.desparasitado,
        m.num_vistas,
        p.idProtectora,
        p.nombre     AS protectora_nombre,
        p.localidad  AS protectora_localidad,
        p.telefono   AS protectora_telefono,
        p.email      AS protectora_email,
        p.web        AS protectora_web
     FROM mascotas m
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE m.idMascota = ? AND m.activa = 1
     LIMIT 1'
);
$stmt->execute([$id]);
$mascota = $stmt->fetch();

if (!$mascota) {
    respuestaError('Mascota no encontrada.', 404);
}

// Registrar vista
$pdo->prepare('UPDATE mascotas SET num_vistas = num_vistas + 1 WHERE idMascota = ?')
    ->execute([$id]);
$mascota['num_vistas']++;

// Fotos
$stmtFotos = $pdo->prepare(
    'SELECT ruta, es_principal
     FROM mascotas_fotos
     WHERE idMascota = ?
     ORDER BY orden ASC'
);
$stmtFotos->execute([$id]);
$mascota['fotos'] = $stmtFotos->fetchAll();

// Otras mascotas disponibles (max 5, excluyendo la actual)
$stmtOtras = $pdo->prepare(
    'SELECT m.idMascota, m.nombre, m.especie, m.raza, m.tamanyo, f.ruta AS foto
     FROM mascotas m
     LEFT JOIN mascotas_fotos f ON f.idMascota = m.idMascota AND f.es_principal = 1
     WHERE m.idMascota != ? AND m.activa = 1 AND m.estado_adopcion = "disponible"
     ORDER BY RAND()
     LIMIT 5'
);
$stmtOtras->execute([$id]);
$mascota['otras'] = $stmtOtras->fetchAll();

respuestaOk(['mascota' => $mascota]);