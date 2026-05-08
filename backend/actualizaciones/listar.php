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
$idMascotaFiltro = intval($_GET['idMascota'] ?? 0);
$offset = ($pagina - 1) * $porPagina;

$pdo = conectar();

$condicionFiltro = '';
if ($filtro === 'no_leidas') {
    $condicionFiltro = 'AND ap.leido = 0';
} elseif ($filtro === 'leidas') {
    $condicionFiltro = 'AND ap.leido = 1';
}

$params = [$idUsuario, $porPagina, $offset];
$whereMascota = '';
if ($idMascotaFiltro > 0) {
    $whereMascota = 'AND a.idMascota = ?';
    $params = [$idUsuario, $idMascotaFiltro, $porPagina, $offset];
}

$sql = "SELECT a.idActualizacion, a.mensaje, a.fotos, a.video_url, a.fecha, a.idMascota,
               m.nombre AS animalNombre,
               (SELECT mf.ruta FROM mascotas_fotos mf WHERE mf.idMascota = m.idMascota AND mf.es_principal = 1 LIMIT 1) AS foto,
               ap.leido
        FROM actualizaciones a
        INNER JOIN actualizacion_padrinos ap ON a.idActualizacion = ap.idActualizacion
        INNER JOIN mascotas m ON a.idMascota = m.idMascota
        WHERE ap.idUsuario = ? $whereMascota $condicionFiltro
        ORDER BY a.fecha DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$actualizaciones = $stmt->fetchAll();

$countParams = [$idUsuario];
$whereCount = '';
if ($idMascotaFiltro > 0) {
    $whereCount = 'AND a.idMascota = ?';
    $countParams = [$idUsuario, $idMascotaFiltro];
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
