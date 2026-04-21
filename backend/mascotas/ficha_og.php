<?php
/*--------------------------------------------------------------------------------------------
ficha_og.php — página PHP que genera meta Open Graph para compartir en redes
Los crawlers de redes sociales leen los meta OG; el usuario ve fichaAnimal.html */

require_once __DIR__ . '/../includes/funciones.php';

$id = (int)($_GET['id'] ?? 0);

/* Datos por defecto */
$titulo     = 'PetFamily – Adopta, no compres';
$descripcion= 'Encuentra tu compañero ideal en PetFamily. Adopta un perro o gato en Asturias.';
$imagen     = '';
$url        = 'http://localhost/ProyectoCat/html/fichaAnimal.html';

if ($id) {
    try {
        $pdo  = conectar();
        $stmt = $pdo->prepare(
            'SELECT m.nombre, m.especie, m.raza, m.descripcion, m.urgencia,
                    p.nombre AS protectora,
                    (SELECT mf.ruta FROM mascotas_fotos mf
                     WHERE mf.idMascota = m.idMascota AND mf.es_principal = 1
                     LIMIT 1) AS foto_principal
             FROM mascotas m
             JOIN protectoras p ON m.idProtectora = p.idProtectora
             WHERE m.idMascota = ? AND m.activa = 1
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $m = $stmt->fetch();

        if ($m) {
            $especie    = $m['especie'] === 'perro' ? 'perrito' : 'gatito';
            $titulo     = $m['nombre'] . ' busca una familia – PetFamily';
            $urgente    = $m['urgencia'] === 'urgente' ? '¡URGENTE! ' : '';
            $desc_animal= $m['descripcion']
                ? mb_substr($m['descripcion'], 0, 150) . (mb_strlen($m['descripcion']) > 150 ? '…' : '')
                : ($m['nombre'] . ' es un ' . $especie . ' en ' . $m['protectora'] . ' buscando hogar.');
            $descripcion = $urgente . $desc_animal;
            $url        = 'http://localhost/ProyectoCat/html/fichaAnimal.html?id=' . $id;

            if ($m['foto_principal']) {
                $imagen = 'http://localhost/ProyectoCat/' . $m['foto_principal'];
            }
        }
    } catch (Exception $e) {
        /* Si falla la BD, usar defaults */
    }
}

/* Imagen fallback */
if (!$imagen) {
    $imagen = 'http://localhost/ProyectoCat/img/iconosHeader/gato_logo.png';
}

/* Redirigir al usuario a fichaAnimal.html inmediatamente
   Los crawlers de redes leen el HTML antes de ejecutar el JS de redirección */
?><!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= htmlspecialchars($url) ?>">
    <meta property="og:title"       content="<?= htmlspecialchars($titulo) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($descripcion) ?>">
    <meta property="og:image"       content="<?= htmlspecialchars($imagen) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height"content="630">
    <meta property="og:locale"      content="es_ES">
    <meta property="og:site_name"   content="PetFamily">

    <!-- Twitter Card -->
    <meta name="twitter:card"       content="summary_large_image">
    <meta name="twitter:title"      content="<?= htmlspecialchars($titulo) ?>">
    <meta name="twitter:description"content="<?= htmlspecialchars($descripcion) ?>">
    <meta name="twitter:image"      content="<?= htmlspecialchars($imagen) ?>">

    <!-- Descripción general -->
    <meta name="description" content="<?= htmlspecialchars($descripcion) ?>">

    <!-- Redirigir al usuario a fichaAnimal.html -->
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($url) ?>">
</head>
<body>
    <p>Redirigiendo... <a href="<?= htmlspecialchars($url) ?>">Haz clic aquí si no te redirige</a></p>
    <script>window.location.replace('<?= addslashes($url) ?>');</script>
</body>
</html>