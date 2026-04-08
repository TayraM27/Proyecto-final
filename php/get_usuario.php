<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro id']);
    exit;
}

$id = intval($_GET['id']);

try {
    $usuario = getUsuarioById($pdo, $id);
    if ($usuario) {
        echo json_encode(['success' => true, 'data' => $usuario]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'No encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
