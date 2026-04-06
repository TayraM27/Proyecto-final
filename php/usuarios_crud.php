<?php
require_once 'db.php';

// Obtener todos los usuarios activos
function getUsuarios($pdo) {
    $stmt = $pdo->query('SELECT * FROM usuarios WHERE activo = 1');
    return $stmt->fetchAll();
}

// Obtener usuario por ID
function getUsuarioById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE idUsuario = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Crear usuario
function createUsuario($pdo, $data) {
    $sql = 'INSERT INTO usuarios (nombre, apellidos, email, password_hash, telefono, foto_perfil, rol, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['apellidos'],
        $data['email'],
        $data['password_hash'],
        $data['telefono'],
        $data['foto_perfil'],
        $data['rol'],
        $data['activo'] ?? 1
    ]);
}

// Actualizar usuario
function updateUsuario($pdo, $id, $data) {
    $sql = 'UPDATE usuarios SET nombre=?, apellidos=?, email=?, password_hash=?, telefono=?, foto_perfil=?, rol=?, activo=? WHERE idUsuario=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['apellidos'],
        $data['email'],
        $data['password_hash'],
        $data['telefono'],
        $data['foto_perfil'],
        $data['rol'],
        $data['activo'] ?? 1,
        $id
    ]);
}

// Eliminar (desactivar) usuario
function deleteUsuario($pdo, $id) {
    $sql = 'UPDATE usuarios SET activo = 0 WHERE idUsuario = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}
?>
