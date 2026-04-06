<?php
require_once 'db.php';

// Obtener todas las mascotas activas
function getMascotas($pdo) {
    $stmt = $pdo->query('SELECT * FROM mascotas WHERE activa = 1');
    return $stmt->fetchAll();
}

// Obtener mascota por ID
function getMascotaById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM mascotas WHERE idMascota = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Crear mascota
function createMascota($pdo, $data) {
    $sql = 'INSERT INTO mascotas (nombre, especie, raza, edad, sexo, descripcion, foto, idProtectora, activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['especie'],
        $data['raza'],
        $data['edad'],
        $data['sexo'],
        $data['descripcion'],
        $data['foto'],
        $data['idProtectora'],
        $data['activa'] ?? 1
    ]);
}

// Actualizar mascota
function updateMascota($pdo, $id, $data) {
    $sql = 'UPDATE mascotas SET nombre=?, especie=?, raza=?, edad=?, sexo=?, descripcion=?, foto=?, idProtectora=?, activa=? WHERE idMascota=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['especie'],
        $data['raza'],
        $data['edad'],
        $data['sexo'],
        $data['descripcion'],
        $data['foto'],
        $data['idProtectora'],
        $data['activa'] ?? 1,
        $id
    ]);
}

// Eliminar (desactivar) mascota
function deleteMascota($pdo, $id) {
    $sql = 'UPDATE mascotas SET activa = 0 WHERE idMascota = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}
?>
