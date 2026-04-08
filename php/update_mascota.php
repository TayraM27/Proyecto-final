<?php
/*--------------------------------------------------------------------------------------------
Actualiza una mascota */
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/mascotas_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID obligatorio']);
    exit;
}

try {
    $pdo = conectar();
    $ok = updateMascota($pdo, $data['id'], $data);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}