<?php
/*--------------------------------------------------------------------------------------------
Actualiza un usuario */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['idUsuario'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro idUsuario']);
    exit;
}

$id = (int)$data['idUsuario'];

$campos = [
    'nombre' => $data['nombre'] ?? '',
    'email'  => $data['email']  ?? '',
    'rol'    => $data['rol']    ?? 'usuario',
    'activo' => $data['activo'] ?? 1,
];

try {
    $pdo = conectar();
    $ok  = updateUsuario($pdo, $id, $campos);
    echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}