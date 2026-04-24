<?php
/*--------------------------------------------------------------------------------------------
GET — lista mascotas para páginas públicas (adopta, acoge, apadrina)
Parámetros:
  pagina   — número de página (default 1)
  especie  — perro | gato | todos
  acogida  — 1 para filtrar por disponible_acogida (acoge.html)
  apadrina — 1 para filtrar por disponible_apadrinamiento (apadrina.html) */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

session_write_close();

$pdo = conectar();

$pagina  = max(1, (int)($_GET['pagina']  ?? 1));
$especie = $_GET['especie']              ?? 'todos';
$acogida = (int)($_GET['acogida']        ?? 0);
$apadrina= (int)($_GET['apadrina']       ?? 0);
$limite  = 30;
$offset  = ($pagina - 1) * $limite;

$where  = ['m.activa = 1'];
$params = [];

/* Filtro especie */
if ($especie !== 'todos') {
    $where[]  = 'm.especie = ?';
    $params[] = $especie;
}

/* Filtro según contexto de la página */
if ($acogida) {
    /* acoge.html: animales con acogida activa, no adoptados definitivamente */
    $where[] = 'm.disponible_acogida = 1';
    $where[] = 'm.estado_adopcion != "adoptado"';
} elseif ($apadrina) {
    /* apadrina.html: animales con apadrinamiento activo */
    $where[] = 'm.disponible_apadrinamiento = 1';
    $where[] = 'm.estado_adopcion != "adoptado"';
} else {
    /* adopta.html: solo disponibles o en proceso, nunca no_disponible ni adoptado */
    $where[] = 'm.estado_adopcion IN ("disponible", "en_proceso")';
}

$cond = implode(' AND ', $where);

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM mascotas m WHERE $cond");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$sql = "SELECT
            m.idMascota,
            m.idProtectora,
            m.nombre,
            m.especie,
            m.raza,
            m.sexo,
            m.tamanyo,
            m.color,
            m.descripcion,
            m.estado_salud,
            m.urgencia,
            m.estado_adopcion,
            m.edad_texto,
            m.badge_extra,
            m.disponible_apadrinamiento,
            m.disponible_acogida,
            m.compatible_ninos,
            m.compatible_perros,
            m.compatible_gatos,
            m.apto_piso,
            m.vacunado,
            m.esterilizado,
            m.microchip,
            m.desparasitado,
            m.num_vistas,
            m.fecha_nacimiento,
            m.fecha_entrada,
            TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) AS edad_anos,
            DATEDIFF(CURDATE(), m.fecha_entrada)               AS dias_en_adopcion,
            p.nombre   AS protectora_nombre,
            p.localidad AS ubicacion,
            p.telefono  AS telefono_protectora,
            p.email     AS email_protectora,
            p.web       AS web_protectora,
            p.foto_logo AS logo_protectora,
            (SELECT mf.ruta FROM mascotas_fotos mf
             WHERE mf.idMascota = m.idMascota AND mf.es_principal = 1
             LIMIT 1) AS foto_principal
        FROM mascotas m
        JOIN protectoras p ON m.idProtectora = p.idProtectora
        WHERE $cond
        ORDER BY m.urgencia = 'urgente' DESC, m.fecha_entrada ASC
        LIMIT ? OFFSET ?";

$params[] = $limite;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

respuestaOk([
    'mascotas'     => $stmt->fetchAll(),
    'total'        => $total,
    'pagina'       => $pagina,
    'totalPaginas' => (int)ceil($total / $limite),
]);