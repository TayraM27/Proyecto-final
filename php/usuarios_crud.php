<?php
/*--------------------------------------------------------------------------------------------
gestión de usuarios */

require_once __DIR__ . '/../backend/config/db.php';

/*--------------------------------------------------------------------------------------------
obtener todos los usuarios */

function getUsuarios($pdo) {
    $stmt = $pdo->query('SELECT idUsuario, nombre, username, email, rol, activo FROM usuarios ORDER BY nombre ASC');
    return $stmt->fetchAll();
}

/*--------------------------------------------------------------------------------------------
obtener usuario por ID */

function getUsuarioById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT idUsuario, nombre, username, email, rol, activo FROM usuarios WHERE idUsuario = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/*--------------------------------------------------------------------------------------------
actualizar usuario */

function updateUsuario($pdo, $id, $data) {
    $sql = 'UPDATE usuarios
            SET nombre=?, email=?, rol=?, activo=?
            WHERE idUsuario=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['email'],
        $data['rol'] ?? 'usuario',
        $data['activo'] ?? 1,
        $id
    ]);
}

/*--------------------------------------------------------------------------------------------
eliminar (desactivar) usuario */

function deleteUsuario($pdo, $id) {
    $sql = 'UPDATE usuarios SET activo = 0 WHERE idUsuario = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}