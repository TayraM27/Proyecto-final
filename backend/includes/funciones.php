<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        /* En produccion (Render/HTTPS) la cookie debe ser Secure */
        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $esHttps,
            'httponly' => true,
            'samesite' => $esHttps ? 'None' : 'Lax',
        ]);
        session_start();
    }
}

function usuarioLogueado(): bool {
    iniciarSesionSegura();
    return isset($_SESSION['idUsuario']);
}

function esAdmin(): bool {
    iniciarSesionSegura();
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function esProtectora(): bool {
    iniciarSesionSegura();
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'protectora';
}

function esAdminOProtectora(): bool {
    iniciarSesionSegura();
    return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'protectora']);
}

function getIdProtectoraUsuario(): ?int {
    iniciarSesionSegura();
    return isset($_SESSION['idProtectora']) ? (int)$_SESSION['idProtectora'] : null;
}

function requerirLogin(): void {
    if (!usuarioLogueado()) {
        respuestaError('Debes iniciar sesión para realizar esta acción.', 401);
    }
}

function requerirAdmin(): void {
    if (!esAdmin()) {
        respuestaError('Acceso restringido a administradores.', 403);
    }
}

function requerirProtectora(): void {
    if (!esProtectora()) {
        respuestaError('Acceso restringido a protectoras.', 403);
    }
}

function requerirAdminOProtectora(): void {
    if (!esAdminOProtectora()) {
        respuestaError('Acceso restringido.', 403);
    }
}

function respuestaOk(array $datos = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => true], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

function respuestaError(string $mensaje, int $codigo = 400): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

function validarEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarPassword(string $pass): bool {
    return strlen($pass) >= 8;
}

function limpiar(string $valor): string {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

function soloNumerico(mixed $valor): bool {
    return filter_var($valor, FILTER_VALIDATE_INT) !== false;
}

function generarTokenCSRF(): string {
    iniciarSesionSegura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF(string $token): bool {
    iniciarSesionSegura();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function pagina(int $pagina, int $porPagina = 12): array {
    $pagina = max(1, $pagina);
    $offset = ($pagina - 1) * $porPagina;
    return ['limite' => $porPagina, 'offset' => $offset, 'pagina' => $pagina];
}

function jsonInput(): array {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respuestaError('Datos JSON inválidos', 400);
    }
    return $data ?? [];
}

function crearNotificacion(int $idUsuario, string $tipo, string $mensaje, string $ruta_destino = ''): bool {
    $pdo = conectar();
    $stmt = $pdo->prepare('INSERT INTO notificaciones (idUsuario, tipo, mensaje, ruta_destino) VALUES (?, ?, ?, ?)');
    return $stmt->execute([$idUsuario, $tipo, $mensaje, $ruta_destino]);
}

function obtenerUsuarioSesion(): ?array {
    iniciarSesionSegura();
    if (!isset($_SESSION['idUsuario'])) return null;
    return [
        'idUsuario' => $_SESSION['idUsuario'],
        'nombre' => $_SESSION['nombre'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'rol' => $_SESSION['rol'] ?? '',
        'idProtectora' => $_SESSION['idProtectora'] ?? null
    ];
}