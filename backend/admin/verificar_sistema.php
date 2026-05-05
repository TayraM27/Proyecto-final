<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/bd.php';

$resultado = [
    'bd' => ['status' => 'ok', 'mensajes' => []],
    'archivos' => ['status' => 'ok', 'mensajes' => []]
];

try {
    $pdo = getPDO();
    
    $colPrioritaria = $pdo->query("SHOW COLUMNS FROM mascotas LIKE 'prioritaria'")->fetch();
    if ($colPrioritaria) {
        $resultado['bd']['mensajes'][] = 'Campo prioritaria existe en tabla mascotas.';
    } else {
        $resultado['bd']['status'] = 'error';
        $resultado['bd']['mensajes'][] = 'FALTA campo prioritaria en tabla mascotas.';
    }
    
    $colFecha = $pdo->query("SHOW COLUMNS FROM mascotas LIKE 'fecha_prioritaria'")->fetch();
    if ($colFecha) {
        $resultado['bd']['mensajes'][] = 'Campo fecha_prioritaria existe en tabla mascotas.';
    } else {
        $resultado['bd']['status'] = 'error';
        $resultado['bd']['mensajes'][] = 'FALTA campo fecha_prioritaria en tabla mascotas.';
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mascotas WHERE prioritaria = 1 AND activa = 1");
    $cnt = $stmt->fetch();
    $resultado['bd']['mensajes'][] = 'Mascotas prioritarias activas: ' . $cnt['total'];
    
} catch (Exception $e) {
    $resultado['bd']['status'] = 'error';
    $resultado['bd']['mensajes'][] = 'Error BD: ' . $e->getMessage();
}

$archivosRequeridos = [
    '/../../backend/admin/get_prioritarias.php',
    '/../../admin/mi-protectora.html',
    '/../../html/index.html',
    '/../../html/fichaAnimal.html',
    '/../../javascript/tidio-manager.js'
];

foreach ($archivosRequeridos as $arch) {
    $ruta = __DIR__ . $arch;
    if (file_exists($ruta)) {
        $resultado['archivos']['mensajes'][] = basename($ruta) . ' existe.';
    } else {
        $resultado['archivos']['status'] = 'error';
        $resultado['archivos']['mensajes'][] = 'FALTA: ' . basename($ruta);
    }
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
