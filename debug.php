<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/backend/includes/funciones.php';

/* Iniciar sesión PRIMERO, luego leer el handler */
iniciarSesionSegura();
$_SESSION['test'] = 'ok_' . time();
session_write_close();

$info = [
    'php_version'    => PHP_VERSION,
    'https'          => $_SERVER['HTTPS'] ?? 'not set',
    'x_forwarded'    => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set',
    'session_save'   => ini_get('session.save_handler'),  /* ahora sí es post-handler */
    'session_id'     => session_id(),
    'session_test'   => $_SESSION['test'] ?? 'NO SESSION',
    'document_root'  => $_SERVER['DOCUMENT_ROOT'],
];

/* Verificar que se guardó en MySQL */
try {
    $pdo  = conectar();
    $stmt = $pdo->prepare('SELECT id, expires FROM php_sessions WHERE id = ?');
    $stmt->execute([session_id()]);
    $row  = $stmt->fetch();
    $info['mysql_session'] = $row ? 'SAVED — expires: ' . $row['expires'] : 'NOT IN DB';
    $total = $pdo->query('SELECT COUNT(*) FROM php_sessions')->fetchColumn();
    $info['total_sessions'] = (int)$total;
} catch (Exception $e) {
    $info['mysql_session'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT);