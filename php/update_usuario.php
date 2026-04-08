<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

$campos = [
    'nombre' => $data['nombre'] ?? '',
    'apellidos' => $data['apellidos'] ?? '',
    'email' => $data['email'] ?? '',
    'password_hash' => $data['password_hash'] ?? '',
    'telefono' => $data['telefono'] ?? '',
    'foto_perfil' => $data['foto_perfil'] ?? '',
    'rol' => $data['rol'] ?? 'usuario',
    'activo' => $data['activo'] ?? 1
];

try {
    $ok = updateUsuario($pdo, $id, $campos);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
