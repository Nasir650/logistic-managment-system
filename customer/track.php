<?php
// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';

$order_number = $_GET['order'] ?? '';

if (!$order_number) {
    require_once __DIR__ . '/../partials/header.php';
    echo '<div class="bg-red-50 text-red-700 p-4 rounded">Please provide an order number to track.</div>';
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

// Get shipment details
$stmt = $pdo->prepare("SELECT s.*, u.name as customer_name, d.name as driver_name FROM shipments s 
                       LEFT JOIN users u ON s.customer_id = u.id 
                       LEFT JOIN users d ON s.driver_id = d.id 
                       WHERE s.order_number = ?");
$stmt->execute([$order_number]);
$shipment = $stmt->fetch();

if (!$shipment) {
    require_once __DIR__ . '/../partials/header.php';
    echo '<div class="bg-red-50 text-red-700 p-4 rounded">Shipment not found. Please check your order number.</div>';
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

// Get tracking history
$tracking_stmt = $pdo->prepare("SELECT * FROM tracking_points WHERE shipment_id = ? ORDER BY recorded_at ASC");
$tracking_stmt->execute([$shipment['id']]);
$tracking_points = $tracking_stmt->fetchAll();

// Get status history
$status_stmt = $pdo->prepare("SELECT ssh.*, u.name as user_name FROM shipment_status_history ssh 
                              LEFT JOIN users u ON ssh.user_id = u.id 
                              WHERE ssh.shipment_id = ? ORDER BY ssh.created_at DESC");
$status_stmt->execute([$shipment['id']]);
$status_history = $status_stmt->fetchAll();

// Get latest location
$latest_location = null;
if ($tracking_points) {
    $latest_location = end($tracking_points);
}

require_once __DIR__ . '/../partials/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Track Your Shipment</h1>
        <p class="text-gray-600">Order #<?= htmlspecialchars($shipment['order_number']); ?></p>
    </div>

    <!-- Status Overview -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid md:grid-cols-4 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Current Status</h3>
                <p class="mt-1">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        <?php
                        switch($shipment['status']) {
                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                            case 'assigned': echo 'bg-blue-100 text-blue-800'; break;
                            case 'en_route_to_pickup': echo 'bg-purple-100 text-purple-800'; break;
                            case 'at_pickup': echo 'bg-orange-100 text-orange-800'; break;
                            case 'in_transit': echo 'bg-indigo-100 text-indigo-800'; break;
                            case 'delivered': echo 'bg-green-100 text-green-800'; break;
                            default: echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?= ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                    </span>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Driver</h3>
                <p class="mt-1 text-sm text-gray-900">
                    <?= $shipment['driver_name'] ? htmlspecialchars($shipment['driver_name']) : 'Not assigned yet'; ?>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Last Update</h3>
                <p class="mt-1 text-sm text-gray-900">
                    <?= $latest_location ? date('M j, Y g:i A', strtotime($latest_location['recorded_at'])) : 'No updates yet'; ?>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Estimated Delivery</h3>
                <p class="mt-1 text-sm text-gray-900">
                    <?= $shipment['delivery_deadline'] ? date('M j, Y', strtotime($shipment['delivery_deadline'])) : 'TBD'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Live Map -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Live Tracking Map</h2>
        <div id="tracking-map" class="h-96 rounded-lg border"></div>
        <div class="mt-4 flex justify-between items-center">
            <div>
                <button id="refresh-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    🔄 Refresh Location
                </button>
                <button id="auto-refresh-btn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 ml-2">
                    ▶️ Auto Refresh
                </button>
            </div>
            <div class="text-sm text-gray-500">
                <?php if ($latest_location): ?>
                    Last update: <?= date('g:i A', strtotime($latest_location['recorded_at'])); ?>
                    <?php if ($latest_location['speed_mph']): ?>
                        • Speed: <?= round($latest_location['speed_mph']); ?> mph
                    <?php endif; ?>
                <?php else: ?>
                    No location data available yet
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Shipment Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Shipment Details</h2>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Cargo Type:</span>
                        <p class="font-medium"><?= htmlspecialchars($shipment['cargo_type'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Weight:</span>
                        <p class="font-medium"><?= number_format($shipment['weight_lbs']); ?> lbs</p>
                    </div>
                </div>
                
                <div class="border-t pt-3">
                    <span class="text-sm text-gray-500">Pickup Location:</span>
                    <p class="font-medium"><?= htmlspecialchars($shipment['pickup_name'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-gray-600">
                        <?= htmlspecialchars($shipment['pickup_address1'] ?? ''); ?><br>
                        <?= htmlspecialchars($shipment['pickup_city'] ?? ''); ?>, <?= htmlspecialchars($shipment['pickup_state'] ?? ''); ?> <?= htmlspecialchars($shipment['pickup_zip'] ?? ''); ?>
                    </p>
                </div>
                
                <div class="border-t pt-3">
                    <span class="text-sm text-gray-500">Delivery Location:</span>
                    <p class="font-medium"><?= htmlspecialchars($shipment['delivery_name'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-gray-600">
                        <?= htmlspecialchars($shipment['delivery_address1'] ?? ''); ?><br>
                        <?= htmlspecialchars($shipment['delivery_city'] ?? ''); ?>, <?= htmlspecialchars($shipment['delivery_state'] ?? ''); ?> <?= htmlspecialchars($shipment['delivery_zip'] ?? ''); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Status History -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Status History</h2>
            <div class="space-y-4">
                <?php foreach ($status_history as $history): ?>
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full
                        <?php
                        switch($history['status']) {
                            case 'pending': echo 'bg-yellow-400'; break;
                            case 'assigned': echo 'bg-blue-400'; break;
                            case 'en_route_to_pickup': echo 'bg-purple-400'; break;
                            case 'at_pickup': echo 'bg-orange-400'; break;
                            case 'in_transit': echo 'bg-indigo-400'; break;
                            case 'delivered': echo 'bg-green-400'; break;
                            default: echo 'bg-gray-400';
                        }
                        ?>"></div>
                    <div class="flex-grow">
                        <p class="font-medium"><?= ucfirst(str_replace('_', ' ', $history['status'])); ?></p>
                        <p class="text-sm text-gray-500">
                            <?= date('M j, Y g:i A', strtotime($history['created_at'])); ?>
                            <?php if ($history['user_name']): ?>
                                • by <?= htmlspecialchars($history['user_name']); ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($history['note']): ?>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($history['note']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (!$status_history): ?>
                    <p class="text-gray-500 text-sm">No status updates yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize map
const map = L.map('tracking-map').setView([39.5, -98.35], 4);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Track data
const orderNumber = '<?= htmlspecialchars($order_number); ?>';
const shipmentId = <?= (int)$shipment['id']; ?>;
let autoRefreshInterval = null;

// Load initial tracking data
async function loadTrackingData() {
    try {
        const response = await fetch(`../tracking_get.php?shipment_id=${shipmentId}`);
        const data = await response.json();
        updateMap(data.points);
    } catch (error) {
        console.error('Error loading tracking data:', error);
    }
}

function updateMap(points) {
    // Clear existing markers and routes
    map.eachLayer(layer => {
        if (layer instanceof L.Marker || layer instanceof L.Polyline) {
            map.removeLayer(layer);
        }
    });
    
    if (points && points.length > 0) {
        // Create route line
        const latlngs = points.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
        L.polyline(latlngs, {color: 'blue', weight: 3}).addTo(map);
        
        // Add markers
        const startPoint = points[0];
        const endPoint = points[points.length - 1];
        
        // Start marker
        L.marker([parseFloat(startPoint.lat), parseFloat(startPoint.lng)])
         .bindPopup('Route started: ' + startPoint.recorded_at)
         .addTo(map);
        
        // Current position marker
        L.marker([parseFloat(endPoint.lat), parseFloat(endPoint.lng)], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        })
        .bindPopup('Current position: ' + endPoint.recorded_at)
        .addTo(map);
        
        // Fit map to route
        map.fitBounds(latlngs, {padding: [20, 20]});
    } else {
        // No tracking data yet
        const notice = L.popup()
            .setLatLng([39.5, -98.35])
            .setContent('No tracking data available yet. Driver will start sharing location when shipment begins.')
            .openOn(map);
    }
}

// Refresh functions
document.getElementById('refresh-btn').addEventListener('click', loadTrackingData);

document.getElementById('auto-refresh-btn').addEventListener('click', function() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        this.textContent = '▶️ Auto Refresh';
        this.className = 'px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 ml-2';
    } else {
        autoRefreshInterval = setInterval(loadTrackingData, 30000); // Every 30 seconds
        this.textContent = '⏸️ Stop Auto Refresh';
        this.className = 'px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 ml-2';
    }
});

// Load initial data
loadTrackingData();
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>