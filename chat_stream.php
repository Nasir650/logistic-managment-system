<?php
require_once __DIR__ . '/auth.php';
require_login();

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Prevent output buffering
if (ob_get_level()) ob_end_clean();

$room = $_GET['room'] ?? '';
if (!$room) {
    echo "data: " . json_encode(['error' => 'Room parameter required']) . "\n\n";
    exit;
}

$user = current_user();

// Check room permissions (same function as above)
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
    echo "data: " . json_encode(['error' => 'Access denied']) . "\n\n";
    exit;
}

// Send initial connection confirmation
echo "data: " . json_encode(['type' => 'connected', 'room' => $room]) . "\n\n";
flush();

// Get last message ID from client
$lastId = (int)($_GET['last_id'] ?? 0);

// Main SSE loop
$maxDuration = 300; // 5 minutes max connection time
$startTime = time();

while (time() - $startTime < $maxDuration) {
    // Check for new messages
    try {
        $stmt = $pdo->prepare("
            SELECT id, sender_user_id, sender_role, body, created_at 
            FROM chat_messages 
            WHERE room = ? AND id > ? 
            ORDER BY id ASC 
            LIMIT 10
        ");
        $stmt->execute([$room, $lastId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($messages) {
            foreach ($messages as $msg) {
                $lastId = $msg['id'];
                echo "data: " . json_encode([
                    'type' => 'message',
                    'id' => $msg['id'],
                    'sender_user_id' => $msg['sender_user_id'],
                    'sender_role' => $msg['sender_role'],
                    'body' => $msg['body'],
                    'created_at' => $msg['created_at']
                ]) . "\n\n";
            }
            flush();
        }

        // Send heartbeat every 30 seconds
        if ((time() - $startTime) % 30 === 0) {
            echo "data: " . json_encode(['type' => 'heartbeat']) . "\n\n";
            flush();
        }

    } catch (Exception $e) {
        echo "data: " . json_encode(['type' => 'error', 'message' => 'Database error']) . "\n\n";
        flush();
        break;
    }

    // Check connection status
    if (connection_aborted()) {
        break;
    }

    sleep(2); // Poll every 2 seconds
}
?>