<?php
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Recoger datos JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nombre']) || empty($data['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
    exit;
}

// Valores por defecto
$campos = [
    'nombre' => $data['nombre'],
    'descripcion' => $data['descripcion'] ?? null,
    'direccion' => $data['direccion'] ?? null,
    'localidad' => $data['localidad'] ?? null,
    'telefono' => $data['telefono'] ?? null,
    'email' => $data['email'],
    'web' => $data['web'] ?? null,
    'foto_logo' => $data['foto_logo'] ?? null,
    'latitud' => $data['latitud'] ?? null,
    'longitud' => $data['longitud'] ?? null,
    'verificada' => $data['verificada'] ?? 0,
    'activa' => $data['activa'] ?? 1
];

try {
    $ok = createProtectora($pdo, $campos);
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
