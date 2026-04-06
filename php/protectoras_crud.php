<?php
require_once 'db.php';

// Obtener todas las protectoras
function getProtectoras($pdo) {
    $stmt = $pdo->query('SELECT * FROM protectoras WHERE activa = 1');
    return $stmt->fetchAll();
}

// Obtener una protectora por ID
function getProtectoraById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM protectoras WHERE idProtectora = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Crear una nueva protectora
function createProtectora($pdo, $data) {
    $sql = 'INSERT INTO protectoras (nombre, descripcion, direccion, localidad, telefono, email, web, foto_logo, latitud, longitud, verificada, activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['descripcion'],
        $data['direccion'],
        $data['localidad'],
        $data['telefono'],
        $data['email'],
        $data['web'],
        $data['foto_logo'],
        $data['latitud'],
        $data['longitud'],
        $data['verificada'],
        $data['activa']
    ]);
}

// Actualizar protectora
function updateProtectora($pdo, $id, $data) {
    $sql = 'UPDATE protectoras SET nombre=?, descripcion=?, direccion=?, localidad=?, telefono=?, email=?, web=?, foto_logo=?, latitud=?, longitud=?, verificada=?, activa=? WHERE idProtectora=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['descripcion'],
        $data['direccion'],
        $data['localidad'],
        $data['telefono'],
        $data['email'],
        $data['web'],
        $data['foto_logo'],
        $data['latitud'],
        $data['longitud'],
        $data['verificada'],
        $data['activa'],
        $id
    ]);
}

// Eliminar (desactivar) protectora
function deleteProtectora($pdo, $id) {
    $sql = 'UPDATE protectoras SET activa = 0 WHERE idProtectora = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

?>
