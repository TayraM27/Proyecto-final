<?php
/*--------------------------------------------------------------------------------------------
backend/actualizaciones/listar.php
Devuelve los seguimientos de los apadrinamientos activos del usuario logueado */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

if (!usuarioLogueado()) {
    respuestaError('Debes iniciar sesión.', 401);
}

$usuario   = obtenerUsuarioSesion();
$idUsuario = (int)$usuario['idUsuario'];

$pdo       = conectar();
$pagina    = max(1, (int)($_GET['pagina']    ?? 1));
$porPagina = min(50,  (int)($_GET['porPagina'] ?? 10));
$idMascota = (int)($_GET['idMascota'] ?? 0);
$offset    = ($pagina - 1) * $porPagina;

/*--------------------------------------------------------------------------------------------
construir condiciones */
$whereMascota = '';
$params       = [$idUsuario];

if ($idMascota > 0) {
    $whereMascota = 'AND ap.idMascota = ?';
    $params[]     = $idMascota;
}

/*--------------------------------------------------------------------------------------------
query principal */
$sql = "SELECT
            s.idSeguimiento,
            s.contenido    AS mensaje,
            s.ruta_archivo AS archivo,
            s.tipo_archivo,
            s.fecha,
            ap.idMascota,
            m.nombre       AS animalNombre,
            (SELECT mf.ruta
             FROM mascotas_fotos mf
             WHERE mf.idMascota = m.idMascota AND mf.es_principal = 1
             LIMIT 1)      AS foto
        FROM seguimientos s
        INNER JOIN apadrinamientos ap ON s.idApadrinamiento = ap.idApadrinamiento
        INNER JOIN mascotas        m  ON ap.idMascota       = m.idMascota
        WHERE ap.idUsuario = ?
          AND ap.estado    = 'activo'
          $whereMascota
        ORDER BY s.fecha DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$porPagina, $offset]));
$seguimientos = $stmt->fetchAll();

/*--------------------------------------------------------------------------------------------
total para paginación */
$stmtCount = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM seguimientos s
     INNER JOIN apadrinamientos ap ON s.idApadrinamiento = ap.idApadrinamiento
     WHERE ap.idUsuario = ?
       AND ap.estado    = 'activo'
       $whereMascota"
);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetch()['total'];

/*--------------------------------------------------------------------------------------------
mascotas apadrinadas para el selector de filtro */
$stmtMascotas = $pdo->prepare(
    "SELECT DISTINCT m.idMascota, m.nombre
     FROM apadrinamientos ap
     INNER JOIN mascotas m ON ap.idMascota = m.idMascota
     WHERE ap.idUsuario = ? AND ap.estado = 'activo'
     ORDER BY m.nombre ASC"
);
$stmtMascotas->execute([$idUsuario]);
$mascotas = $stmtMascotas->fetchAll();

respuestaOk([
    'actualizaciones' => $seguimientos,
    'mascotas'        => $mascotas,
    'total'           => $total,
    'pagina'          => $pagina,
    'porPagina'       => $porPagina,
    'totalPaginas'    => (int)ceil($total / $porPagina),
]);