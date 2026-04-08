<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

parse_str(file_get_contents('php://input'), $data);

if (!isset($data['idUsuario'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro idUsuario']);
    exit;
}

$id = intval($data['idUsuario']);

try {
    $ok = deleteUsuario($pdo, $id);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
