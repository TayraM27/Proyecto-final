<?php
/*--------------------------------------------------------------------------------------------
Funciones CRUD para gestión de mascotas (php/) */

require_once __DIR__ . '/../backend/config/db.php';

/*--------------------------------------------------------------------------------------------
obtener todas las mascotas activas */

function getMascotas($pdo) {
    $stmt = $pdo->query(
        'SELECT m.*, p.nombre AS protectora_nombre, p.localidad AS ubicacion
         FROM mascotas m
         JOIN protectoras p ON m.idProtectora = p.idProtectora
         WHERE m.activa = 1
         ORDER BY m.idMascota DESC'
    );
    return $stmt->fetchAll();
}

/*--------------------------------------------------------------------------------------------
obtener mascota por ID */

function getMascotaById($pdo, $id) {
    $stmt = $pdo->prepare(
        'SELECT m.*, p.nombre AS protectora_nombre, p.localidad AS ubicacion
         FROM mascotas m
         JOIN protectoras p ON m.idProtectora = p.idProtectora
         WHERE m.idMascota = ? AND m.activa = 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/*--------------------------------------------------------------------------------------------
crear mascota */

function createMascota($pdo, $data) {
    $sql = 'INSERT INTO mascotas
                (idProtectora, nombre, especie, raza, sexo, tamanyo, color, descripcion,
                 estado_salud, urgencia, fecha_nacimiento, fecha_entrada,
                 vacunado, esterilizado, microchip, desparasitado,
                 compatible_ninos, compatible_perros, compatible_gatos, apto_piso,
                 disponible_apadrinamiento)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['idProtectora'],
        $data['nombre'],
        $data['especie'],
        $data['raza']              ?? null,
        $data['sexo']              ?? 'macho',
        $data['tamanyo']           ?? 'mediano',
        $data['color']             ?? null,
        $data['descripcion']       ?? null,
        $data['estado_salud']      ?? null,
        $data['urgencia']          ?? 'normal',
        $data['fecha_nacimiento']  ?? null,
        $data['fecha_entrada']     ?? null,
        (int)($data['vacunado']    ?? 0),
        (int)($data['esterilizado']?? 0),
        (int)($data['microchip']   ?? 0),
        (int)($data['desparasitado']?? 0),
        (int)($data['compatible_ninos'] ?? 0),
        (int)($data['compatible_perros']?? 0),
        (int)($data['compatible_gatos'] ?? 0),
        (int)($data['apto_piso']   ?? 0),
        (int)($data['disponible_apadrinamiento'] ?? 1),
    ]);
}

/*--------------------------------------------------------------------------------------------
actualizar mascota */

function updateMascota($pdo, $id, $data) {
    $sql = 'UPDATE mascotas
            SET idProtectora=?, nombre=?, especie=?, raza=?, sexo=?, tamanyo=?,
                color=?, descripcion=?, estado_salud=?, urgencia=?,
                fecha_nacimiento=?, fecha_entrada=?,
                vacunado=?, esterilizado=?, microchip=?, desparasitado=?,
                compatible_ninos=?, compatible_perros=?, compatible_gatos=?,
                apto_piso=?, disponible_apadrinamiento=?, activa=?
            WHERE idMascota=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['idProtectora'],
        $data['nombre'],
        $data['especie'],
        $data['raza']              ?? null,
        $data['sexo']              ?? 'macho',
        $data['tamanyo']           ?? 'mediano',
        $data['color']             ?? null,
        $data['descripcion']       ?? null,
        $data['estado_salud']      ?? null,
        $data['urgencia']          ?? 'normal',
        $data['fecha_nacimiento']  ?? null,
        $data['fecha_entrada']     ?? null,
        (int)($data['vacunado']    ?? 0),
        (int)($data['esterilizado']?? 0),
        (int)($data['microchip']   ?? 0),
        (int)($data['desparasitado']?? 0),
        (int)($data['compatible_ninos'] ?? 0),
        (int)($data['compatible_perros']?? 0),
        (int)($data['compatible_gatos'] ?? 0),
        (int)($data['apto_piso']   ?? 0),
        (int)($data['disponible_apadrinamiento'] ?? 1),
        (int)($data['activa']      ?? 1),
        $id,
    ]);
}

/*--------------------------------------------------------------------------------------------
eliminar mascota */

function deleteMascota($pdo, $id) {
    $stmt = $pdo->prepare('UPDATE mascotas SET activa = 0 WHERE idMascota = ?');
    return $stmt->execute([$id]);
}