<?php

require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';

/*--------------------------------------------------------------------------------------------
obtener todas las mascotas activas */

function getMascotas($pdo) {
    $stmt = $pdo->query('SELECT * FROM mascotas WHERE activa = 1 ORDER BY idMascota DESC');
    return $stmt->fetchAll();
}

/*--------------------------------------------------------------------------------------------
obtener mascota por ID */

function getMascotaById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM mascotas WHERE idMascota = ? AND activa = 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/*--------------------------------------------------------------------------------------------
crear mascota */

function createMascota($pdo, $data) {
    $sql = 'INSERT INTO mascotas (nombre, especie, raza, edad, sexo, descripcion, foto, idProtectora, activa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['especie'],
        $data['raza'] ?? null,
        $data['edad'] ?? null,
        $data['sexo'] ?? null,
        $data['descripcion'] ?? null,
        $data['foto'] ?? null,
        $data['idProtectora'],
    ]);
}

/*--------------------------------------------------------------------------------------------
actualizar mascota */

function updateMascota($pdo, $id, $data) {
    $sql = 'UPDATE mascotas
            SET nombre=?, especie=?, raza=?, edad=?, sexo=?, descripcion=?, foto=?, idProtectora=?, activa=?
            WHERE idMascota=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['especie'],
        $data['raza'] ?? null,
        $data['edad'] ?? null,
        $data['sexo'] ?? null,
        $data['descripcion'] ?? null,
        $data['foto'] ?? null,
        $data['idProtectora'],
        $data['activa'] ?? 1,
        $id
    ]);
}

/*--------------------------------------------------------------------------------------------
eliminar mascota */

function deleteMascota($pdo, $id) {
    $sql = 'UPDATE mascotas SET activa = 0 WHERE idMascota = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}