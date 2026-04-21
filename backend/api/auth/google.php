<?php
/*--------------------------------------------------------------------------------------------
Google OAuth — recibe el JWT de Google, verifica y crea/inicia sesión
POST { credential: <JWT> }
Devuelve: JSON ok/error */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido.', 405);
}

$datos      = json_decode(file_get_contents('php://input'), true);
$credential = $datos['credential'] ?? '';

if (!$credential) {
    respuestaError('Token de Google no recibido.');
}

/*--------------------------------------------------------------------------------------------
verificar JWT con Google tokeninfo */

$url      = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$respuesta = @file_get_contents($url);

if (!$respuesta) {
    respuestaError('No se pudo verificar el token con Google. Comprueba la conexión.');
}

$payload = json_decode($respuesta, true);

if (!$payload || isset($payload['error'])) {
    respuestaError('Token de Google no válido.');
}

$googleId = $payload['sub']     ?? '';
$email    = $payload['email']   ?? '';
$nombre   = $payload['name']    ?? '';
$foto     = $payload['picture'] ?? null;

if (!$googleId || !$email) {
    respuestaError('Datos de Google incompletos.');
}

$pdo = conectar();

/*--------------------------------------------------------------------------------------------
buscar usuario existente */

$stmt = $pdo->prepare(
    'SELECT idUsuario, nombre, username, rol, foto_perfil, activo
     FROM usuarios
     WHERE google_id = ? OR email = ?
     LIMIT 1'
);
$stmt->execute([$googleId, $email]);
$usuario = $stmt->fetch();

if ($usuario) {
    if (!$usuario['activo']) {
        respuestaError('Tu cuenta está desactivada. Contacta con el administrador.');
    }

    $pdo->prepare(
        'UPDATE usuarios SET google_id = ?, ultimo_login = NOW() WHERE idUsuario = ?'
    )->execute([$googleId, $usuario['idUsuario']]);

} else {
    /*--------------------------------------------------------------------------------------------
    crear usuario nuevo */

    $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombre));
    if (strlen($baseUsername) < 3) {
        $baseUsername = 'user' . $baseUsername;
    }

    $username = $baseUsername;
    $sufijo   = 1;

    while (true) {
        $check = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE username = ? LIMIT 1');
        $check->execute([$username]);
        if (!$check->fetch()) {
            break;
        }
        $username = $baseUsername . $sufijo;
        $sufijo++;
    }

    $hashAleatorio = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, username, email, password_hash, foto_perfil, google_id, rol)
         VALUES (?, ?, ?, ?, ?, ?, "usuario")'
    );
    $stmt->execute([$nombre, $username, $email, $hashAleatorio, $foto, $googleId]);

    $usuario = [
        'idUsuario'   => (int)$pdo->lastInsertId(),
        'nombre'      => $nombre,
        'username'    => $username,
        'rol'         => 'usuario',
        'foto_perfil' => $foto,
    ];
}

/*--------------------------------------------------------------------------------------------
iniciar sesión */

iniciarSesionSegura();
session_regenerate_id(true);
$_SESSION['idUsuario']   = $usuario['idUsuario'];
$_SESSION['nombre']      = $usuario['nombre'];
$_SESSION['username']    = $usuario['username'];
$_SESSION['rol']         = $usuario['rol'];
$_SESSION['foto_perfil'] = $usuario['foto_perfil'];

respuestaOk([
    'usuario' => [
        'idUsuario'   => $usuario['idUsuario'],
        'nombre'      => $usuario['nombre'],
        'username'    => $usuario['username'],
        'rol'         => $usuario['rol'],
        'foto_perfil' => $usuario['foto_perfil'],
    ],
    'redirigir' => $usuario['rol'] === 'admin' ? '../admin/dashboard.html' : 'index.html',
]);