<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json');

/* Simular exactamente lo que hace login.php */
ob_start(); /* Capturar cualquier output antes del JSON */

require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/backend/includes/funciones.php';

$output_before = ob_get_clean();

iniciarSesionSegura();

$info = [
    'session_save'    => ini_get('session.save_handler'),
    'session_id'      => session_id(),
    'output_before'   => $output_before, /* Cualquier error/warning antes del JSON */
    'cookie_params'   => session_get_cookie_params(),
    'headers_sent'    => headers_sent($file, $line) ? "YES - at $file:$line" : 'NO',
];

/* Intentar escribir sesión */
$_SESSION['debug'] = 'test';
session_write_close();

/* Ver si hay entrada en BD */
try {
    $stmt = conectar()->prepare('SELECT id FROM php_sessions WHERE id = ?');
    $stmt->execute([session_id()]);
    $info['in_db'] = $stmt->fetch() ? 'YES' : 'NO';
} catch (Exception $e) {
    $info['in_db'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);