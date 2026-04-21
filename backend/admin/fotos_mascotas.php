<?php
/*CRUD de fotos de mascotas para el administrador */

require_once __DIR__ . '/../includes/funciones.php';

iniciarSesionSegura();
requerirAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo = conectar();

switch ($_SERVER['REQUEST_METHOD']) {

    /*--------------------------------------------------------------------------------------------
    GET - listar fotos de una mascota */
    case 'GET':
        $idMascota = (int)($_GET['idMascota'] ?? 0);
        if (!$idMascota) {
            respuestaError('Falta idMascota.', 422);
        }
        $stmt = $pdo->prepare(
            'SELECT idFoto, ruta, es_principal, orden
             FROM mascotas_fotos
             WHERE idMascota = ?
             ORDER BY es_principal DESC, orden ASC'
        );
        $stmt->execute([$idMascota]);
        respuestaOk(['fotos' => $stmt->fetchAll()]);
        break;

    /*--------------------------------------------------------------------------------------------
    POST - subir nueva foto */
    case 'POST':
        if (!isset($_FILES['foto'], $_POST['idMascota'])) {
            respuestaError('Faltan datos obligatorios: foto e idMascota.', 422);
        }

        $idMascota = (int)$_POST['idMascota'];
        $file      = $_FILES['foto'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            respuestaError('Error al recibir el archivo.', 500);
        }

        $extension  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($extension, $permitidos)) {
            respuestaError('Formato no permitido. Usa JPG, PNG, WEBP o GIF.');
        }

        if ($file['size'] > 3 * 1024 * 1024) {
            respuestaError('La imagen no puede superar 3 MB.');
        }

        $directorio = __DIR__ . '/../../img/mascotas/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombre = 'mascota_' . $idMascota . '_' . uniqid() . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $directorio . $nombre)) {
            respuestaError('No se pudo guardar la imagen.', 500);
        }

        $ruta        = 'img/mascotas/' . $nombre;
        $esPrincipal = !empty($_POST['es_principal']) ? 1 : 0;

        if ($esPrincipal) {
            $pdo->prepare('UPDATE mascotas_fotos SET es_principal = 0 WHERE idMascota = ?')
                ->execute([$idMascota]);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO mascotas_fotos (idMascota, ruta, es_principal) VALUES (?,?,?)'
        );
        $ok = $stmt->execute([$idMascota, $ruta, $esPrincipal]);

        if ($ok) {
            respuestaOk(['mensaje' => 'Foto subida correctamente.', 'ruta' => $ruta]);
        } else {
            respuestaError('Error al guardar la foto en la base de datos.');
        }
        break;

    /*--------------------------------------------------------------------------------------------
    PUT - cambiar foto principal */
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idFoto'], $data['idMascota'])) {
            respuestaError('Faltan idFoto e idMascota.', 422);
        }

        $pdo->prepare('UPDATE mascotas_fotos SET es_principal = 0 WHERE idMascota = ?')
            ->execute([$data['idMascota']]);
        $pdo->prepare('UPDATE mascotas_fotos SET es_principal = 1 WHERE idFoto = ?')
            ->execute([$data['idFoto']]);

        respuestaOk(['mensaje' => 'Foto principal actualizada.']);
        break;

    /*--------------------------------------------------------------------------------------------
    DELETE - eliminar foto */
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['idFoto'])) {
            respuestaError('Falta idFoto.', 422);
        }

        $stmt = $pdo->prepare('SELECT ruta FROM mascotas_fotos WHERE idFoto = ?');
        $stmt->execute([$data['idFoto']]);
        $foto = $stmt->fetch();

        if ($foto) {
            $archivo = __DIR__ . '/../../' . $foto['ruta'];
            if (file_exists($archivo)) {
                unlink($archivo);
            }
        }

        $pdo->prepare('DELETE FROM mascotas_fotos WHERE idFoto = ?')
            ->execute([$data['idFoto']]);

        respuestaOk(['mensaje' => 'Foto eliminada correctamente.']);
        break;

    default:
        respuestaError('Método no permitido.', 405);
}