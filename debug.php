<?php
/* TEMPORAL — eliminar después del diagnóstico */
ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

$info = [
    'php_version'    => PHP_VERSION,
    'https'          => $_SERVER['HTTPS'] ?? 'not set',
    'x_forwarded'    => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set',
    'session_save'   => ini_get('session.save_handler'),
    'document_root'  => $_SERVER['DOCUMENT_ROOT'],
    'script'         => $_SERVER['SCRIPT_FILENAME'],
];

/* Probar conexión BD */
try {
    require_once __DIR__ . '/backend/config/db.php';
    $pdo = conectar();
    $info['db'] = 'OK';
    /* Verificar tabla sesiones */
    $r = $pdo->query('SHOW TABLES LIKE "php_sessions"')->fetchAll();
    $info['php_sessions_table'] = count($r) > 0 ? 'EXISTS' : 'MISSING';
} catch (Exception $e) {
    $info['db'] = 'ERROR: ' . $e->getMessage();
}

/* Probar sesión */
try {
    require_once __DIR__ . '/backend/includes/funciones.php';
    iniciarSesionSegura();
    $_SESSION['test'] = 'ok';
    session_write_close();
    $info['session'] = 'OK - id: ' . session_id();
} catch (Exception $e) {
    $info['session'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT);