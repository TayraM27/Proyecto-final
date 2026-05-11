<?php
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['ok' => false, 'error' => 'ID inválido']); exit; }

require_once __DIR__ . '/../includes/funciones.php';
$pdo = conectar();

$sql = 'SELECT m.*, p.nombre AS protectora_nombre, p.telefono AS telefono_protectora, p.email AS email_protectora, p.foto_logo AS logo_protectora FROM mascotas m JOIN protectoras p ON m.idProtectora = p.idProtectora WHERE m.idMascota = ? AND m.activa = 1 LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) { echo json_encode(['ok' => false, 'error' => 'No encontrada']); exit; }

$pdo->prepare('UPDATE mascotas SET num_vistas = num_vistas + 1 WHERE idMascota = ?')->execute([$id]);

$stmtF = $pdo->prepare('SELECT ruta, es_principal FROM mascotas_fotos WHERE idMascota = ? ORDER BY orden ASC');
$stmtF->execute([$id]);
$m['fotos'] = $stmtF->fetchAll();

echo json_encode(['ok' => true, 'mascota' => $m]);