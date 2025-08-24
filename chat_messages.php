<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Check authentication
if (!function_exists('current_user') || !current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$room = $_GET['room'] ?? '';
$since = (int)($_GET['since'] ?? 0);

if (!$room) {
    http_response_code(400);
    echo json_encode(['error' => 'Room parameter required']);
    exit;
}

$user = current_user();

// Basic room permission check (same as above)
$allowed = false;
if (str_starts_with($room, 'admin_driver:')) {
    $driverId = (int)substr($room, strlen('admin_driver:'));
    $allowed = ($user['role'] === 'admin') || ($user['role'] === 'driver' && $user['id'] === $driverId);
} elseif (str_starts_with($room, 'shipment:')) {
    $shipmentId = (int)substr($room, strlen('shipment:'));
    $allowed = ($user['role'] === 'admin');
    
    if (!$allowed && isset($pdo)) {
        if ($user['role'] === 'driver') {
            $stmt = $pdo->prepare("SELECT 1 FROM shipments WHERE id = ? AND driver_id = ?");
            $stmt->execute([$shipmentId, $user['id']]);
            $allowed = (bool)$stmt->fetchColumn();
        } elseif ($user['role'] === 'customer') {
            $stmt = $pdo->prepare("SELECT 1 FROM shipments WHERE id = ? AND customer_id = ?");
            $stmt->execute([$shipmentId, $user['id']]);
            $allowed = (bool)$stmt->fetchColumn();
        }
    }
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    if (!isset($pdo)) {
        http_response_code(500);
        echo json_encode(['error' => 'Database not available']);
        exit;
    }

    if ($since > 0) {
        // Get new messages since last check
        $stmt = $pdo->prepare("
            SELECT id, sender_user_id, sender_role, body, created_at 
            FROM chat_messages 
            WHERE room = ? AND id > ? 
            ORDER BY id ASC 
            LIMIT 20
        ");
        $stmt->execute([$room, $since]);
    } else {
        // Get recent message history
        $stmt = $pdo->prepare("
            SELECT id, sender_user_id, sender_role, body, created_at 
            FROM chat_messages 
            WHERE room = ? 
            ORDER BY id DESC 
            LIMIT 50
        ");
        $stmt->execute([$room]);
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($since === 0) {
        $messages = array_reverse($messages);
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>