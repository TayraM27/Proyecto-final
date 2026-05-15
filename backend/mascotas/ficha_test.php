<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'mascota' => ['idMascota' => 1, 'nombre' => 'Test', 'especie' => 'perro', 'sexo' => 'macho', 'fotos' => []]]);
?>