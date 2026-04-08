<?php
/*--------------------------------------------------------------------------------------------
Obtiene todas las mascotas activas con sus fotos y datos de protectora */

require_once __DIR__ . '/../backend/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = conectar();
    
    /*------- obtener mascotas con join a protectoras -------*/
    $stmt = $pdo->query('
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
            p.nombre AS protectora,
            p.localidad AS ubicacion,
            COALESCE(mf.ruta, "img/mascotas/default.jpg") AS foto
        FROM mascotas m
        JOIN protectoras p ON m.idProtectora = p.idProtectora
        LEFT JOIN mascotas_fotos mf ON m.idMascota = mf.idMascota AND mf.es_principal = 1
        WHERE m.activa = 1
        ORDER BY m.fecha_publicacion DESC
    ');
    
    $mascotas = $stmt->fetchAll();
    
    /*------- ajustar rutas de imágenes para que funcionen desde html/ -------*/
    foreach ($mascotas as &$mascota) {
        if (!str_starts_with($mascota['foto'], 'http')) {
            $mascota['foto'] = '../' . $mascota['foto'];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $mascotas], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>