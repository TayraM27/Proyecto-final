<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json');

try {
    $protectoras = getProtectoras($pdo);
    echo json_encode(['success' => true, 'data' => $protectoras]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
