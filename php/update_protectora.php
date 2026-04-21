<?php
/*--------------------------------------------------------------------------------------------
Actualiza una protectora */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['idProtectora'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro idProtectora']);
    exit;
}

$id = (int)$data['idProtectora'];

$campos = [
    'nombre'      => $data['nombre']      ?? '',
    'descripcion' => $data['descripcion'] ?? null,
    'direccion'   => $data['direccion']   ?? null,
    'localidad'   => $data['localidad']   ?? null,
    'telefono'    => $data['telefono']    ?? null,
    'email'       => $data['email']       ?? null,
    'web'         => $data['web']         ?? null,
    'foto_logo'   => $data['foto_logo']   ?? null,
    'activa'      => $data['activa']      ?? 1,
];

try {
    $pdo = conectar();
    $ok  = updateProtectora($pdo, $id, $campos);
    echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}