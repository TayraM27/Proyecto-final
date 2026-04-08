<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

parse_str(file_get_contents('php://input'), $data);

if (!isset($data['idProtectora'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro idProtectora']);
    exit;
}

$id = intval($data['idProtectora']);

$campos = [
    'nombre' => $data['nombre'] ?? '',
    'descripcion' => $data['descripcion'] ?? '',
    'direccion' => $data['direccion'] ?? '',
    'localidad' => $data['localidad'] ?? '',
    'telefono' => $data['telefono'] ?? '',
    'email' => $data['email'] ?? '',
    'web' => $data['web'] ?? '',
    'foto_logo' => $data['foto_logo'] ?? '',
    'latitud' => $data['latitud'] ?? '',
    'longitud' => $data['longitud'] ?? '',
    'verificada' => $data['verificada'] ?? 0,
    'activa' => $data['activa'] ?? 1
];

try {
    $ok = updateProtectora($pdo, $id, $campos);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
