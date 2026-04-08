<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro id']);
    exit;
}

$id = intval($_GET['id']);

try {
    $protectora = getProtectoraById($pdo, $id);
    if ($protectora) {
        echo json_encode(['success' => true, 'data' => $protectora]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'No encontrada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
