<?php
/*--------------------------------------------------------------------------------------------
Conexión a base de datos mediante PDO */

/* Variables de entorno - cambiar según tu entorno */
define('DB_HOST', 'localhost');
define('DB_NAME', 'petfamily');
define('DB_USER', 'root');
define('DB_PASS', 'tayra27');
define('DB_CHARSET', 'utf8mb4');

/*--------------------------------------------------------------------------------------------
conectar */

function conectar(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST
         . ';dbname=' . DB_NAME
         . ';charset=' . DB_CHARSET;

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión con la base de datos.']);
        exit;
    }
}