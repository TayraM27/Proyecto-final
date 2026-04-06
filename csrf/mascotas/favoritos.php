<?php
/*--------------------------------------------------------------------------------------------
GET  — lista los favoritos del usuario logueado
POST { idMascota } — toggle: añade si no esta, quita si ya estaba
Usado por adopta.html y fichaAnimal.html */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
requerirLogin();

$pdo      = conectar();
$idUsuario = (int)$_SESSION['idUsuario'];
$metodo   = $_SERVER['REQUEST_METHOD'];

/*--------------------------------------------------------------------------------------------
GET - listar favoritos */
if ($metodo === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT m.idMascota, m.nombre, m.especie, m.raza, m.tamanyo, m.urgencia,
                f.ruta AS foto, p.localidad
         FROM favoritos fav
         JOIN mascotas m ON fav.idMascota = m.idMascota
         JOIN protectoras p ON m.idProtectora = p.idProtectora
         LEFT JOIN mascotas_fotos f ON f.idMascota = m.idMascota AND f.es_principal = 1
         WHERE fav.idUsuario = ? AND m.activa = 1
         ORDER BY fav.fecha DESC'
    );
    $stmt->execute([$idUsuario]);
    respuestaOk(['favoritos' => $stmt->fetchAll()]);
}

/*--------------------------------------------------------------------------------------------
POST - toggle favorito */
if ($metodo === 'POST') {
    $datos     = json_decode(file_get_contents('php://input'), true);
    $idMascota = (int)($datos['idMascota'] ?? 0);

    if (!$idMascota) {
        respuestaError('ID de mascota no valido.');
    }

    // Comprobar si existe
    $stmt = $pdo->prepare(
        'SELECT 1 FROM favoritos WHERE idUsuario = ? AND idMascota = ? LIMIT 1'
    );
    $stmt->execute([$idUsuario, $idMascota]);

    if ($stmt->fetch()) {
        // Ya esta en favoritos — quitar
        $pdo->prepare('DELETE FROM favoritos WHERE idUsuario = ? AND idMascota = ?')
            ->execute([$idUsuario, $idMascota]);
        respuestaOk(['accion' => 'eliminado']);
    } else {
        // No esta — añadir
        $pdo->prepare('INSERT INTO favoritos (idUsuario, idMascota) VALUES (?, ?)')
            ->execute([$idUsuario, $idMascota]);
        respuestaOk(['accion' => 'guardado']);
    }
}

respuestaError('Metodo no permitido.', 405);