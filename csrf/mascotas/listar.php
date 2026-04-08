<?php
/*--------------------------------------------------------------------------------------------
GET — devuelve mascotas con filtros opcionales
Parametros query: especie, urgencia, ubicacion, tamano, edad, color, salud, sexo, pagina
Usado por adopta.html para el catalogo filtrado */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

// Filtros del frontend (adopta.html)
$especie  = $_GET['especie']  ?? 'todos';
$urgencia = $_GET['urgencia'] ?? 'todos';
$ubicacion= $_GET['ubicacion']?? 'todos';
$tamano   = $_GET['tamano']   ?? 'todos';
$edad     = $_GET['edad']     ?? 'todos';
$color    = $_GET['color']    ?? 'todos';
$salud    = $_GET['salud']    ?? 'todos';
$sexo     = $_GET['sexo']     ?? 'todos';
$pagina   = (int)($_GET['pagina'] ?? 1);

$p = paginacion($pagina, 12);

// Construccion dinamica de la consulta
$where  = ['m.activa = 1', 'm.estado_adopcion = "disponible"'];
$params = [];

if ($especie !== 'todos' && in_array($especie, ['perro', 'gato'])) {
    $where[]  = 'm.especie = ?';
    $params[] = $especie;
}

if ($urgencia !== 'todos' && in_array($urgencia, ['urgente', 'nuevo', 'normal'])) {
    $where[]  = 'm.urgencia = ?';
    $params[] = $urgencia;
}

// ubicacion filtra por localidad de la protectora
$mapaUbicacion = [
    'gijon'  => 'Gijón',
    'oviedo' => 'Oviedo',
    'aviles' => 'Avilés',
];
if ($ubicacion !== 'todos' && isset($mapaUbicacion[$ubicacion])) {
    $where[]  = 'p.localidad = ?';
    $params[] = $mapaUbicacion[$ubicacion];
}

$mapaTamano = [
    'pequeno' => 'pequeño',
    'mediano' => 'mediano',
    'grande'  => 'grande',
];
if ($tamano !== 'todos' && isset($mapaTamano[$tamano])) {
    $where[]  = 'm.tamanyo = ?';
    $params[] = $mapaTamano[$tamano];
}

if ($sexo !== 'todos' && in_array($sexo, ['macho', 'hembra'])) {
    $where[]  = 'm.sexo = ?';
    $params[] = $sexo;
}

// edad aproximada segun rango
$mapaEdad = [
    'cachorro' => 'TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) < 1',
    'joven'    => 'TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) BETWEEN 1 AND 3',
    'adulto'   => 'TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) BETWEEN 3 AND 8',
    'senior'   => 'TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) > 8',
];
if ($edad !== 'todos' && isset($mapaEdad[$edad])) {
    $where[] = '(' . $mapaEdad[$edad] . ' OR m.fecha_nacimiento IS NULL)';
    // Si no hay fecha_nacimiento (Sin determinar) se incluye igualmente
}

if ($salud === 'especial') {
    $where[] = "m.estado_salud NOT IN ('Bueno', 'bueno')";
} elseif ($salud === 'bueno') {
    $where[] = "m.estado_salud IN ('Bueno', 'bueno')";
}

$condicion = implode(' AND ', $where);

// Total para paginacion
$stmtTotal = $pdo->prepare(
    "SELECT COUNT(*) as total
     FROM mascotas m
     JOIN protectoras p ON m.idProtectora = p.idProtectora
     WHERE $condicion"
);
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetch()['total'];

// Consulta principal
$sql = "SELECT
            m.idMascota,
            m.nombre,
            m.especie,
            m.raza,
            m.sexo,
            m.edad_texto,
            m.tamanyo,
            m.urgencia,
            m.estado_salud,
            m.tiempo_en_adopcion,
            m.num_vistas,
            p.nombre   AS protectora_nombre,
            p.localidad AS ubicacion,
            f.ruta     AS foto_principal
        FROM mascotas m
        JOIN protectoras p ON m.idProtectora = p.idProtectora
        LEFT JOIN mascotas_fotos f ON f.idMascota = m.idMascota AND f.es_principal = 1
        WHERE $condicion
        ORDER BY
            FIELD(m.urgencia, 'urgente', 'nuevo', 'normal'),
            m.fecha_publicacion DESC
        LIMIT ? OFFSET ?";

$params[] = $p['limite'];
$params[] = $p['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mascotas = $stmt->fetchAll();

respuestaOk([
    'mascotas'   => $mascotas,
    'total'      => $total,
    'pagina'     => $p['pagina'],
    'porPagina'  => $p['limite'],
    'totalPaginas' => (int)ceil($total / $p['limite']),
]);