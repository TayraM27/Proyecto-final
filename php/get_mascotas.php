<?php
require_once 'db.php';
require_once 'mascotas_crud.php';

header('Content-Type: application/json');

try {
    $mascotas = getMascotas($pdo);
    echo json_encode(['success' => true, 'data' => $mascotas]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
