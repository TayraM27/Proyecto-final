<?php
/*--------------------------------------------------------------------------------------------
Devuelve un usuario por ID
GET ?id=1 */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/usuarios_crud.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta el parámetro id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int)$_GET['id'];

try {
    $pdo     = conectar();
    $usuario = getUsuarioById($pdo, $id);
    if ($usuario) {
        echo json_encode(['success' => true, 'data' => $usuario], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}