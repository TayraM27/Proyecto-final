<?php
/*--------------------------------------------------------------------------------------------
Crea un nuevo usuario (desde el panel admin) */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nombre']) || empty($data['email']) || empty($data['password_hash'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios: nombre, email, password_hash']);
    exit;
}

$campos = [
    'nombre'        => $data['nombre'],
    'apellidos'     => $data['apellidos']     ?? null,
    'username'      => $data['username']      ?? null,
    'email'         => $data['email'],
    'password_hash' => $data['password_hash'],
    'localidad'     => $data['localidad']     ?? null,
    'telefono'      => $data['telefono']      ?? null,
    'foto_perfil'   => $data['foto_perfil']   ?? null,
    'rol'           => $data['rol']           ?? 'usuario',
    'activo'        => $data['activo']        ?? 1,
];

try {
    $pdo = conectar();
    $ok  = createUsuario($pdo, $campos);
    echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}