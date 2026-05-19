<?php
/*--------------------------------------------------------------------------------------------
compartir.php — OG tags dinamicos para redes sociales + redirect al usuario */

require_once __DIR__ . '/backend/config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$ogNombre      = 'Adopta un animal – PetFamily';
$ogDescripcion = 'Encuentra a tu companero ideal en las protectoras de Asturias.';
$ogImagen      = '';

if ($id > 0) {
    try {
        $pdo  = conectar();
        $stmt = $pdo->prepare('
            SELECT m.nombre, m.descripcion,
                   COALESCE(mf.ruta, "") AS foto
            FROM mascotas m
            LEFT JOIN mascotas_fotos mf
                   ON m.idMascota = mf.idMascota AND mf.es_principal = 1
            WHERE m.idMascota = ? AND m.activa = 1
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $m = $stmt->fetch();

        if ($m) {
            $ogNombre = htmlspecialchars($m['nombre']) . ' busca hogar – PetFamily';
            $ogDescripcion = $m['descripcion']
                ? htmlspecialchars(mb_substr(strip_tags($m['descripcion']), 0, 160))
                : 'Conoce a ' . htmlspecialchars($m['nombre']) . ' y ayudale a encontrar familia en Asturias.';

            if ($m['foto']) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host     = $_SERVER['HTTP_HOST'];
                $ogImagen = $protocol . '://' . $host . '/' . ltrim($m['foto'], '/');
            }
        }
    } catch (Exception $e) {
        /* Si falla la BD se usan valores por defecto */
    }
}

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$ogUrl     = $protocol . '://' . $host . '/html/fichaAnimal.html?id=' . $id;
$ogUrlComp = $protocol . '://' . $host . '/compartir.php?id=' . $id;

if (!$ogImagen) {
    $ogImagen = $protocol . '://' . $host . '/img/og-default.jpg';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=<?php echo htmlspecialchars($ogUrl); ?>">

    <title><?php echo $ogNombre; ?></title>

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?php echo htmlspecialchars($ogUrlComp); ?>">
    <meta property="og:title"       content="<?php echo $ogNombre; ?>">
    <meta property="og:description" content="<?php echo $ogDescripcion; ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars($ogImagen); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale"     content="es_ES">
    <meta property="og:site_name"  content="PetFamily">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?php echo $ogNombre; ?>">
    <meta name="twitter:description" content="<?php echo $ogDescripcion; ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($ogImagen); ?>">
</head>
<body>
    <p>Redirigiendo... <a href="<?php echo htmlspecialchars($ogUrl); ?>">Haz clic aqui si no te redirige</a></p>
</body>
</html>