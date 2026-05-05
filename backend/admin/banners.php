<?php
/*--------------------------------------------------------------------------------------------
CRUD — Banners */

require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
metodoGetOPost();

requerirAdmin();
session_write_close();

$pdo = conectar();

$id = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? 'listar';

switch ($accion) {
    case 'listar':
        $tipo = $_GET['tipo'] ?? '';
        $activos = $_GET['activos'] ?? '1';
        
        $sql = "SELECT * FROM banners WHERE 1=1";
        $params = [];
        
        if ($tipo) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        
        if ($activos === '1') {
            $sql .= " AND activo=1";
        }
        
        $sql .= " ORDER BY orden ASC, idBanner ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respuestaOk(['banners' => $stmt->fetchAll()]);
        break;
    
    case 'crear':
        $data = jsonInput();
        
        $sql = "INSERT INTO banners (titulo, subtitulo, tipo, imagen, enlace, texto_boton, orden, activo)
                VALUES (:titulo, :subtitulo, :tipo, :imagen, :enlace, :texto_boton, :orden, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $data['titulo'] ?? null,
            ':subtitulo' => $data['subtitulo'] ?? null,
            ':tipo' => $data['tipo'] ?? 'general',
            ':imagen' => $data['imagen'],
            ':enlace' => $data['enlace'] ?? null,
            ':texto_boton' => $data['texto_boton'] ?? null,
            ':orden' => $data['orden'] ?? 0,
        ]);
        
        respuestaOk(['id' => $pdo->lastInsertId()]);
        break;
    
    case 'actualizar':
        $data = jsonInput();
        
        $sql = "UPDATE banners SET titulo=:titulo, subtitulo=:subtitulo, tipo=:tipo, imagen=:imagen,
                enlace=:enlace, texto_boton=:texto_boton, orden=:orden, activo=:activo
                WHERE idBanner=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':titulo' => $data['titulo'] ?? null,
            ':subtitulo' => $data['subtitulo'] ?? null,
            ':tipo' => $data['tipo'] ?? 'general',
            ':imagen' => $data['imagen'],
            ':enlace' => $data['enlace'] ?? null,
            ':texto_boton' => $data['texto_boton'] ?? null,
            ':orden' => $data['orden'] ?? 0,
            ':activo' => $data['activo'] ?? 1,
        ]);
        
        respuestaOk(['ok' => true]);
        break;
    
    case 'eliminar':
        $stmt = $pdo->prepare("UPDATE banners SET activo=0 WHERE idBanner = ?");
        $stmt->execute([$id]);
        respuestaOk(['ok' => true]);
        break;
    
    case 'activar':
        $stmt = $pdo->prepare("UPDATE banners SET activo=1 WHERE idBanner = ?");
        $stmt->execute([$id]);
        respuestaOk(['ok' => true]);
        break;
}