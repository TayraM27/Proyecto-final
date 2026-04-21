<?php
/*--------------------------------------------------------------------------------------------
GET — devuelve todos los puntos del mapa con filtro de categoria
Parametros: categoria (protectora|veterinaria|tienda|donacion|refugio|todos)
Usado por mapa.html para sustituir los datos hardcodeados en el JS */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaError('Metodo no permitido.', 405);
}

$pdo       = conectar();
$categoria = $_GET['categoria'] ?? 'todos';

$categoriasValidas = ['protectora', 'veterinaria', 'tienda', 'donacion', 'refugio'];

$where  = ['activo = 1'];
$params = [];

if ($categoria !== 'todos' && in_array($categoria, $categoriasValidas)) {
    $where[]  = 'categoria = ?';
    $params[] = $categoria;
}

$condicion = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT idPunto, nombre, categoria, descripcion, direccion,
            localidad, telefono, email, web, latitud, longitud
     FROM puntos_mapa
     WHERE $condicion
     ORDER BY categoria, nombre"
);
$stmt->execute($params);

respuestaOk(['puntos' => $stmt->fetchAll()]);