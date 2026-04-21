<?php
/*--------------------------------------------------------------------------------------------
Obtiene una mascota específica por ID con todas sus fotos */

require_once __DIR__ . '/../backend/config/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = conectar();
    
    /*------- obtener mascota con datos de protectora -------*/
    $stmt = $pdo->prepare('
        SELECT 
            m.idMascota,
            m.nombre,
            m.especie,
            m.raza,
            m.sexo,
            m.tamanyo AS tamano,
            m.color,
            m.descripcion,
            m.estado_salud,
            m.urgencia,
            m.estado_adopcion,
            m.disponible_apadrinamiento,
            m.compatible_ninos,
            m.compatible_perros,
            m.compatible_gatos,
            m.apto_piso,
            m.vacunado,
            m.esterilizado,
            m.microchip,
            m.desparasitado,
            m.num_vistas AS vistas,
            m.fecha_nacimiento,
            m.fecha_entrada,
            p.idProtectora,
            p.nombre AS protectora,
            p.localidad AS ubicacion,
            p.telefono AS telefono_protectora,
            p.email AS email_protectora,
            p.foto_logo AS logo_protectora,
            COALESCE(mf.ruta, "img/mascotas/default.jpg") AS foto
        FROM mascotas m
        JOIN protectoras p ON m.idProtectora = p.idProtectora
        LEFT JOIN mascotas_fotos mf ON m.idMascota = mf.idMascota AND mf.es_principal = 1
        WHERE m.idMascota = ? AND m.activa = 1
        LIMIT 1
    ');
    
    $stmt->execute([$id]);
    $mascota = $stmt->fetch();
    
    if (!$mascota) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mascota no encontrada'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /*------- incrementar contador de vistas -------*/
    $pdo->prepare('UPDATE mascotas SET num_vistas = num_vistas + 1 WHERE idMascota = ?')
        ->execute([$id]);
    $mascota['vistas']++;

    /*------- obtener todas las fotos de la mascota -------*/
    $stmt = $pdo->prepare('
        SELECT ruta, es_principal, orden
        FROM mascotas_fotos
        WHERE idMascota = ?
        ORDER BY es_principal DESC, orden ASC
    ');
    $stmt->execute([$id]);
    $fotos = $stmt->fetchAll();
    
    /*------- ajustar rutas de imágenes para que funcionen desde html/ -------*/
    if (!str_starts_with($mascota['foto'], 'http')) {
        $mascota['foto'] = '../' . $mascota['foto'];
    }
    
    foreach ($fotos as &$foto) {
        if (!str_starts_with($foto['ruta'], 'http')) {
            $foto['ruta'] = '../' . $foto['ruta'];
        }
    }
    
    $mascota['fotos'] = $fotos;
    
    echo json_encode(['success' => true, 'data' => $mascota], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>