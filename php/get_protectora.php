<?php
/*--------------------------------------------------------------------------------------------
Devuelve una protectora por ID
GET ?id=1 */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int)$_GET['id'];

try {
    $pdo        = conectar();
    $protectora = getProtectoraById($pdo, $id);
    if ($protectora) {
        echo json_encode(['success' => true, 'data' => $protectora], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Protectora no encontrada'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}