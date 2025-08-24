<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// Simple error handling
function sendError($code, $message) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

// Check authentication
if (!function_exists('current_user') || !current_user()) {
    sendError(401, 'Not authenticated');
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Method not allowed');
}

// Get POST data
$room = trim($_POST['room'] ?? '');
$body = trim($_POST['body'] ?? '');

if (!$room || !$body) {
    sendError(400, 'Room and body are required');
}

$user = current_user();

// Basic room permission check
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
    sendError(403, 'Access denied');
}

// Limit message length
if (mb_strlen($body) > 2000) {
    $body = mb_substr($body, 0, 2000);
}

// Extract shipment ID
$shipmentId = null;
if (str_starts_with($room, 'shipment:')) {
    $shipmentId = (int)substr($room, strlen('shipment:'));
}

// Save message
try {
    if (!isset($pdo)) {
        sendError(500, 'Database not available');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (room, shipment_id, sender_user_id, sender_role, body) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$room, $shipmentId, $user['id'], $user['role'], $body]);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    sendError(500, 'Failed to send message');
}
?>