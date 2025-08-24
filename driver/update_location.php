<?php
require_once __DIR__ . '/../auth.php';
require_role('driver');

$shipment_id = (int)($_POST['shipment_id'] ?? 0);
$lat = (float)($_POST['lat'] ?? 0);
$lng = (float)($_POST['lng'] ?? 0);
$speed = isset($_POST['speed']) && $_POST['speed'] !== '' ? (float)$_POST['speed'] : null;

$stmt = $pdo->prepare("SELECT id FROM shipments WHERE id = ? AND driver_id = ?");
$stmt->execute([$shipment_id, current_user()['id']]);
if (!$stmt->fetch()) { http_response_code(403); exit('Forbidden'); }

$ins = $pdo->prepare("INSERT INTO tracking_points (shipment_id, driver_id, lat, lng, speed_mph) VALUES (?, ?, ?, ?, ?)");
$ins->execute([$shipment_id, current_user()['id'], $lat, $lng, $speed]);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
