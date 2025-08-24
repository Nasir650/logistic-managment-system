<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$order = $_GET['order'] ?? null;
$shipment_id = (int)($_GET['shipment_id'] ?? 0);

if ($order) {
  $s = $pdo->prepare("SELECT id FROM shipments WHERE order_number = ?");
  $s->execute([$order]);
  $row = $s->fetch();
  if ($row) $shipment_id = (int)$row['id'];
}

if (!$shipment_id) {
  echo json_encode(['points' => []]); exit;
}

$p = $pdo->prepare("SELECT lat, lng, speed_mph, recorded_at FROM tracking_points WHERE shipment_id = ? ORDER BY recorded_at ASC");
$p->execute([$shipment_id]);
$points = $p->fetchAll();

echo json_encode(['points' => $points]);
