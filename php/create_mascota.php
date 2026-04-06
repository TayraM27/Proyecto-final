<?php
require_once 'db.php';
require_once 'mascotas_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nombre']) || empty($data['especie']) || empty($data['idProtectora'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
    exit;
}

$campos = [
    'nombre' => $data['nombre'],
    'especie' => $data['especie'],
    'raza' => $data['raza'] ?? null,
    'edad' => $data['edad'] ?? null,
    'sexo' => $data['sexo'] ?? null,
    'descripcion' => $data['descripcion'] ?? null,
    'foto' => $data['foto'] ?? null,
    'idProtectora' => $data['idProtectora'],
    'activa' => $data['activa'] ?? 1
];

try {
    $ok = createMascota($pdo, $campos);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
