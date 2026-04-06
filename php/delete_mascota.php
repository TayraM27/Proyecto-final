<?php
require_once 'db.php';
require_once 'mascotas_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

parse_str(file_get_contents('php://input'), $data);

if (!isset($data['idMascota'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro idMascota']);
    exit;
}

$id = intval($data['idMascota']);

try {
    $ok = deleteMascota($pdo, $id);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
