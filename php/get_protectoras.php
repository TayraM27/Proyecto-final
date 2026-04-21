<?php
/*--------------------------------------------------------------------------------------------
Devuelve todas las protectoras activas
GET — sin parámetros */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/protectoras_crud.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo         = conectar();
    $protectoras = getProtectoras($pdo);
    echo json_encode(['success' => true, 'data' => $protectoras], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}