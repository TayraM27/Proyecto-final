<?php
/*--------------------------------------------------------------------------------------------
CRUD — Entidades Colaboradoras */

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
        $buscar = $_GET['buscar'] ?? '';
        $tipo = $_GET['tipo'] ?? '';
        $activos = $_GET['activos'] ?? '1';
        
        $sql = "SELECT * FROM entidades_colaboradoras WHERE 1=1";
        $params = [];
        
        if ($buscar) {
            $sql .= " AND (nombre LIKE :buscar OR descripcion LIKE :buscar2)";
            $params[':buscar'] = "%$buscar%";
            $params[':buscar2'] = "%$buscar%";
        }
        
        if ($tipo) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        
        if ($activos === '1') {
            $sql .= " AND activa=1";
        }
        
        $sql .= " ORDER BY nombre ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respuestaOk(['entidades' => $stmt->fetchAll()]);
        break;
    
    case 'crear':
        $data = jsonInput();
        
        $sql = "INSERT INTO entidades_colaboradoras (nombre, descripcion, tipo, web, descuento, activa)
                VALUES (:nombre, :descripcion, :tipo, :web, :descuento, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':tipo' => $data['tipo'] ?? 'tienda',
            ':web' => $data['web'] ?? null,
            ':descuento' => $data['descuento'] ?? null,
        ]);
        
        respuestaOk(['id' => $pdo->lastInsertId()]);
        break;
    
    case 'actualizar':
        $data = jsonInput();
        
        $sql = "UPDATE entidades_colaboradoras SET nombre=:nombre, descripcion=:descripcion, tipo=:tipo, 
                web=:web, descuento=:descuento, activa=:activa
                WHERE idEntidad=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':tipo' => $data['tipo'] ?? 'tienda',
            ':web' => $data['web'] ?? null,
            ':descuento' => $data['descuento'] ?? null,
            ':activa' => $data['activa'] ?? 1,
        ]);
        
        respuestaOk(['ok' => true]);
        break;
    
    case 'eliminar':
        $stmt = $pdo->prepare("UPDATE entidades_colaboradoras SET activa=0 WHERE idEntidad = ?");
        $stmt->execute([$id]);
        respuestaOk(['ok' => true]);
        break;
    
    case 'activar':
        $stmt = $pdo->prepare("UPDATE entidades_colaboradoras SET activa=1 WHERE idEntidad = ?");
        $stmt->execute([$id]);
        respuestaOk(['ok' => true]);
        break;
}