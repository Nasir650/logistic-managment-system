<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

$room = $_GET['room'] ?? '';
if (!$room) {
    http_response_code(400);
    echo json_encode(['error' => 'Room parameter required']);
    exit;
}

$user = current_user();

// Check room permissions (same function)
function isAllowedRoom($user, $room, $pdo) {
    if (str_starts_with($room, 'admin_driver:')) {
        $driverId = (int)substr($room, strlen('admin_driver:'));
        if ($user['role'] === 'admin') return true;
        if ($user['role'] === 'driver' && $user['id'] === $driverId) return true;
        return false;
    }

    if (str_starts_with($room, 'shipment:')) {
        $shipmentId = (int)substr($room, strlen('shipment:'));
        if ($user['role'] === 'admin') return true;

        if ($user['role'] === 'driver') {
            $stmt = $pdo->prepare("SELECT 1 FROM shipments WHERE id = ? AND driver_id = ?");
            $stmt->execute([$shipmentId, $user['id']]);
            return (bool)$stmt->fetchColumn();
        }

        if ($user['role'] === 'customer') {
            $stmt = $pdo->prepare("SELECT 1 FROM shipments WHERE id = ? AND customer_id = ?");
            $stmt->execute([$shipmentId, $user['id']]);
            return (bool)$stmt->fetchColumn();
        }
    }
    return false;
}

if (!isAllowedRoom($user, $room, $pdo)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, sender_user_id, sender_role, body, created_at 
        FROM chat_messages 
        WHERE room = ? 
        ORDER BY id DESC 
        LIMIT 50
    ");
    $stmt->execute([$room]);
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get chat history']);
}
?>