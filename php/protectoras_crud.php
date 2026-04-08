<?php
/*--------------------------------------------------------------------------------------------
 gestión de protectoras */

require_once __DIR__ . '/../backend/config/db.php';
/*--------------------------------------------------------------------------------------------
obtener todas las protectoras activas */

function getProtectoras($pdo) {
    $stmt = $pdo->query('SELECT * FROM protectoras WHERE activa = 1 ORDER BY nombre ASC');
    return $stmt->fetchAll();
}

/*--------------------------------------------------------------------------------------------
obtener protectora por ID */

function getProtectoraById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM protectoras WHERE idProtectora = ? AND activa = 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/*--------------------------------------------------------------------------------------------
crear protectora */

function createProtectora($pdo, $data) {
    $sql = 'INSERT INTO protectoras (nombre, descripcion, email, telefono, direccion, ciudad, logo, sitioWeb, activa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['descripcion'] ?? null,
        $data['email'],
        $data['telefono'] ?? null,
        $data['direccion'] ?? null,
        $data['ciudad'] ?? null,
        $data['logo'] ?? null,
        $data['sitioWeb'] ?? null,
    ]);
}

/*--------------------------------------------------------------------------------------------
actualizar protectora */

function updateProtectora($pdo, $id, $data) {
    $sql = 'UPDATE protectoras
            SET nombre=?, descripcion=?, email=?, telefono=?, direccion=?, ciudad=?, logo=?, sitioWeb=?, activa=?
            WHERE idProtectora=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['descripcion'] ?? null,
        $data['email'],
        $data['telefono'] ?? null,
        $data['direccion'] ?? null,
        $data['ciudad'] ?? null,
        $data['logo'] ?? null,
        $data['sitioWeb'] ?? null,
        $data['activa'] ?? 1,
        $id
    ]);
}

/*--------------------------------------------------------------------------------------------
eliminar  protectora */

function deleteProtectora($pdo, $id) {
    $sql = 'UPDATE protectoras SET activa = 0 WHERE idProtectora = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}