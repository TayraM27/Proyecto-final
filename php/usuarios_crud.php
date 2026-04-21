<?php
/*--------------------------------------------------------------------------------------------
Funciones CRUD para gestión de usuarios */

require_once __DIR__ . '/../backend/config/db.php';

/*--------------------------------------------------------------------------------------------
obtener todos los usuarios */

function getUsuarios($pdo) {
    $stmt = $pdo->query(
        'SELECT idUsuario, nombre, username, email, rol, activo, fecha_registro
         FROM usuarios
         ORDER BY nombre ASC'
    );
    return $stmt->fetchAll();
}

/*--------------------------------------------------------------------------------------------
obtener usuario por ID */

function getUsuarioById($pdo, $id) {
    $stmt = $pdo->prepare(
        'SELECT idUsuario, nombre, username, email, rol, activo, fecha_registro
         FROM usuarios
         WHERE idUsuario = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/*--------------------------------------------------------------------------------------------
crear usuario */

function createUsuario($pdo, $data) {
    $sql = 'INSERT INTO usuarios
                (nombre, apellidos, username, email, password_hash,
                 localidad, telefono, foto_perfil, rol, activo)
            VALUES (?,?,?,?,?,?,?,?,?,?)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['apellidos']     ?? null,
        $data['username']      ?? null,
        $data['email'],
        $data['password_hash'],
        $data['localidad']     ?? null,
        $data['telefono']      ?? null,
        $data['foto_perfil']   ?? null,
        $data['rol']           ?? 'usuario',
        (int)($data['activo']  ?? 1),
    ]);
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
        $data['rol']    ?? 'usuario',
        (int)($data['activo'] ?? 1),
        $id,
    ]);
}

/*--------------------------------------------------------------------------------------------
eliminar (desactivar) usuario */

function deleteUsuario($pdo, $id) {
    $stmt = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE idUsuario = ?');
    return $stmt->execute([$id]);
}