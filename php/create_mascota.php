<?php
/*--------------------------------------------------------------------------------------------
Crea una nueva mascota */

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/mascotas_crud.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nombre']) || empty($data['especie']) || empty($data['idProtectora'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios: nombre, especie, idProtectora']);
    exit;
}

$campos = [
    'idProtectora'             => $data['idProtectora'],
    'nombre'                   => $data['nombre'],
    'especie'                  => $data['especie'],
    'raza'                     => $data['raza']             ?? null,
    'sexo'                     => $data['sexo']             ?? 'macho',
    'tamanyo'                  => $data['tamanyo']          ?? 'mediano',
    'color'                    => $data['color']            ?? null,
    'descripcion'              => $data['descripcion']      ?? null,
    'estado_salud'             => $data['estado_salud']     ?? null,
    'urgencia'                 => $data['urgencia']         ?? 'normal',
    'fecha_nacimiento'         => $data['fecha_nacimiento'] ?? null,
    'fecha_entrada'            => $data['fecha_entrada']    ?? null,
    'vacunado'                 => $data['vacunado']         ?? 0,
    'esterilizado'             => $data['esterilizado']     ?? 0,
    'microchip'                => $data['microchip']        ?? 0,
    'desparasitado'            => $data['desparasitado']    ?? 0,
    'compatible_ninos'         => $data['compatible_ninos'] ?? 0,
    'compatible_perros'        => $data['compatible_perros']?? 0,
    'compatible_gatos'         => $data['compatible_gatos'] ?? 0,
    'apto_piso'                => $data['apto_piso']        ?? 0,
    'disponible_apadrinamiento'=> $data['disponible_apadrinamiento'] ?? 1,
];

try {
    $pdo = conectar();
    $ok  = createMascota($pdo, $campos);
    echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}