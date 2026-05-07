<?php
/*--------------------------------------------------------------------------------------------
backend/api/web/get_prioritarias.php — endpoint PÚBLICO sin restricción de sesión

Estructura:  ProyectoCat/backend/api/web/get_prioritarias.php
             ProyectoCat/backend/includes/funciones.php
             ProyectoCat/backend/config/db.php
Desde aquí:  ../../includes/funciones.php  */

require_once __DIR__ . '/../../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

$sql = "SELECT m.idMascota, m.nombre, m.especie, m.fecha_prioritaria, m.descripcion_slider,
               p.nombre AS protectora_nombre,
               (SELECT mf.ruta FROM mascotas_fotos mf
                WHERE mf.idMascota = m.idMascota AND mf.es_principal = 1
                LIMIT 1) AS foto_principal
        FROM mascotas m
        JOIN protectoras p ON m.idProtectora = p.idProtectora
        WHERE m.prioritaria = 1 AND m.activa = 1
        ORDER BY m.fecha_prioritaria DESC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$mascotas = array_map(function($m) {
    return [
        'idMascota'         => (int)$m['idMascota'],
        'nombre'            => $m['nombre'],
        'especie'           => $m['especie'],
        'foto_principal'    => $m['foto_principal'],
        'protectora_nombre' => $m['protectora_nombre'],
        'fecha_prioritaria' => $m['fecha_prioritaria'],
        'descripcion_slider'=> $m['descripcion_slider'],
    ];
}, $stmt->fetchAll());

echo json_encode(['ok' => true, 'mascotas' => $mascotas], JSON_UNESCAPED_UNICODE);