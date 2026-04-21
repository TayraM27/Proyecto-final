<?php
/*--------------------------------------------------------------------------------------------
Funciones CRUD para gestión de protectoras (php/) */

require_once __DIR__ . '/../backend/config/db.php';

/*--------------------------------------------------------------------------------------------
obtener todas las protectoras activas */

function getProtectoras($pdo) {
    $stmt = $pdo->query(
        'SELECT idProtectora, nombre, descripcion, localidad, telefono,
                email, web, tipo_pagina, iban, bizum, teaming,
                foto_logo, verificada, activa
         FROM protectoras
         WHERE activa = 1
         ORDER BY nombre ASC'
    );
    return $stmt->fetchAll();
}

/*--------------------------------------------------------------------------------------------
obtener protectora por ID */

function getProtectoraById($pdo, $id) {
    $stmt = $pdo->prepare(
        'SELECT * FROM protectoras WHERE idProtectora = ? AND activa = 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/*--------------------------------------------------------------------------------------------
crear protectora */

function createProtectora($pdo, $data) {
    $sql = 'INSERT INTO protectoras
                (nombre, descripcion, direccion, localidad, telefono, email,
                 web, tipo_pagina, iban, bizum, teaming, foto_logo, activa)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['descripcion'] ?? null,
        $data['direccion']   ?? null,
        $data['localidad']   ?? null,
        $data['telefono']    ?? null,
        $data['email']       ?? null,
        $data['web']         ?? null,
        $data['tipo_pagina'] ?? 'sin_pagina',
        $data['iban']        ?? null,
        $data['bizum']       ?? null,
        $data['teaming']     ?? null,
        $data['foto_logo']   ?? null,
    ]);
}

/*--------------------------------------------------------------------------------------------
actualizar protectora */

function updateProtectora($pdo, $id, $data) {
    $sql = 'UPDATE protectoras
            SET nombre=?, descripcion=?, direccion=?, localidad=?,
                telefono=?, email=?, web=?, tipo_pagina=?,
                iban=?, bizum=?, teaming=?, foto_logo=?, activa=?
            WHERE idProtectora=?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['descripcion'] ?? null,
        $data['direccion']   ?? null,
        $data['localidad']   ?? null,
        $data['telefono']    ?? null,
        $data['email']       ?? null,
        $data['web']         ?? null,
        $data['tipo_pagina'] ?? 'sin_pagina',
        $data['iban']        ?? null,
        $data['bizum']       ?? null,
        $data['teaming']     ?? null,
        $data['foto_logo']   ?? null,
        (int)($data['activa'] ?? 1),
        $id,
    ]);
}

/*--------------------------------------------------------------------------------------------
eliminar protectora (borrado lógico) */

function deleteProtectora($pdo, $id) {
    $stmt = $pdo->prepare('UPDATE protectoras SET activa = 0 WHERE idProtectora = ?');
    return $stmt->execute([$id]);
}