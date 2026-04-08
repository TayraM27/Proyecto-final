<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json');

try {
    $usuarios = getUsuarios($pdo);
    echo json_encode(['success' => true, 'data' => $usuarios]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
