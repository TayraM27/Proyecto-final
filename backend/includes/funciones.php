<?php
/*--------------------------------------------------------------------------------------------
Utilidades compartidas: sesión, respuestas JSON, validaciones, seguridad */

require_once __DIR__ . '/../config/db.php';
/*--------------------------------------------------------------------------------------------
sesión */

function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function usuarioLogueado(): bool {
    iniciarSesionSegura();
    return isset($_SESSION['idUsuario']);
}

/**
 * Comprueba si el usuario actual es administrador
 *
 * @return bool Verdadero TRUE si el usuario es administrador, FALSE en caso contrario
 */
function esAdmin(): bool {
    iniciarSesionSegura();
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
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

/*--------------------------------------------------------------------------------------------
respuestas JSON */

function respuestaOk(array $datos = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true] + $datos);
    exit;
}

function respuestaError(string $mensaje, int $codigo = 400): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $mensaje]);
    exit;
}

/*--------------------------------------------------------------------------------------------
validaciones */

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

/*--------------------------------------------------------------------------------------------
CSRF */

function generarTokenCSRF(): string {
    iniciarSesionSegura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF(string $token): bool {
    iniciarSesionSegura();
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/*--------------------------------------------------------------------------------------------
paginación */

function paginacion(int $pagina, int $porPagina = 12): array {
    $pagina = max(1, $pagina);
    $offset = ($pagina - 1) * $porPagina;
    return ['limite' => $porPagina, 'offset' => $offset, 'pagina' => $pagina];
}