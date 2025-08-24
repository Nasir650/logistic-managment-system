<?php
require_once __DIR__ . '/../auth.php';
require_role('driver');

$shipment_id = (int)($_POST['shipment_id'] ?? 0);
$action = $_POST['action'] ?? null;
$status = $_POST['status'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ? AND (driver_id = ? OR status='assigned')");
$stmt->execute([$shipment_id, current_user()['id']]);
$shipment = $stmt->fetch();

if (!$shipment) { http_response_code(404); exit('Not found'); }

try {
  $pdo->beginTransaction();

  if ($action === 'accept' && $shipment['status'] === 'assigned') {
    // Set driver if not already set
    if (!$shipment['driver_id']) {
      $pdo->prepare("UPDATE shipments SET driver_id = ? WHERE id = ?")->execute([current_user()['id'], $shipment_id]);
    }
    $pdo->prepare("UPDATE shipments SET status = 'en_route_to_pickup' WHERE id = ?")->execute([$shipment_id]);
    $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, user_id, status, note) VALUES (?, ?, 'en_route_to_pickup', 'Driver accepted')")->execute([$shipment_id, current_user()['id']]);
  } elseif ($action === 'decline' && $shipment['status'] === 'assigned') {
    $pdo->prepare("UPDATE shipments SET driver_id = NULL, status = 'pending' WHERE id = ?")->execute([$shipment_id]);
    $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, user_id, status, note) VALUES (?, ?, 'pending', 'Driver declined')")->execute([$shipment_id, current_user()['id']]);
  } elseif ($status) {
    $allowed = ['en_route_to_pickup','at_pickup','in_transit','delivered'];
    if (!in_array($status, $allowed, true)) throw new Exception('Invalid status');
    $pdo->prepare("UPDATE shipments SET status = ? WHERE id = ? AND driver_id = ?")->execute([$status, $shipment_id, current_user()['id']]);
    $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, user_id, status) VALUES (?, ?, ?)")->execute([$shipment_id, current_user()['id'], $status]);
  }

  $pdo->commit();
  header('Location: ' . base_path('/driver/index.php'));
  exit;
} catch (Exception $e) {
  $pdo->rollBack();
  exit('Error: ' . $e->getMessage());
}
