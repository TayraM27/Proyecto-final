<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesión.', 401);
}

$usuario = obtenerUsuarioSesion();
$idUsuario = $usuario['idUsuario'];

$pagina = intval($_GET['pagina'] ?? 1);
$porPagina = intval($_GET['porPagina'] ?? 10);
$filtro = $_GET['filtro'] ?? 'todas';
$idAnimalFiltro = intval($_GET['idAnimal'] ?? 0);
$offset = ($pagina - 1) * $porPagina;

$pdo = conectar();

$condicionFiltro = '';
if ($filtro === 'no_leidas') {
    $condicionFiltro = 'AND ap.leido = 0';
} elseif ($filtro === 'leidas') {
    $condicionFiltro = 'AND ap.leido = 1';
}

$params = [$idUsuario, $porPagina, $offset];
$whereAnimal = '';
if ($idAnimalFiltro > 0) {
    $whereAnimal = 'AND a.idAnimal = ?';
    $params = [$idUsuario, $idAnimalFiltro, $porPagina, $offset];
}

$sql = "SELECT a.idActualizacion, a.mensaje, a.fotos, a.video_url, a.fecha, a.idAnimal, 
               an.nombre as animalNombre, an.foto_principal as foto, ap.leido
        FROM actualizaciones a
        INNER JOIN actualizacion_padrinos ap ON a.idActualizacion = ap.idActualizacion
        INNER JOIN animales an ON a.idAnimal = an.idAnimal
        WHERE ap.idUsuario = ? $whereAnimal $condicionFiltro
        ORDER BY a.fecha DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$actualizaciones = $stmt->fetchAll();

$countParams = [$idUsuario];
$whereCount = '';
if ($idAnimalFiltro > 0) {
    $whereCount = 'AND a.idAnimal = ?';
    $countParams = [$idUsuario, $idAnimalFiltro];
}

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM actualizaciones a 
                        INNER JOIN actualizacion_padrinos ap ON a.idActualizacion = ap.idActualizacion 
                        WHERE ap.idUsuario = ? $whereCount $condicionFiltro");
$stmt->execute($countParams);
$total = $stmt->fetch()['total'];

respuestaOk([
    'actualizaciones' => $actualizaciones,
    'total' => intval($total),
    'pagina' => $pagina,
    'porPagina' => $porPagina,
    'totalPaginas' => ceil($total / $porPagina)
]);
