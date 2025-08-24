<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../partials/header.php';

$totOrders = (int)$pdo->query("SELECT COUNT(*) c FROM shipments")->fetch()['c'];
$activeDrivers = (int)$pdo->query("SELECT COUNT(*) c FROM driver_profiles WHERE availability = 1")->fetch()['c'];
$revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM shipments WHERE status = 'delivered'")->fetch()['s'];
$pending = (int)$pdo->query("SELECT COUNT(*) c FROM shipments WHERE status IN ('pending','assigned')")->fetch()['c'];

$latest = $pdo->query("SELECT s.*, u.name AS customer_name FROM shipments s JOIN users u ON u.id = s.customer_id ORDER BY s.created_at DESC LIMIT 10")->fetchAll();
$drivers = $pdo->query("SELECT u.id, u.name, dp.capacity_lbs, dp.availability FROM users u JOIN driver_profiles dp ON dp.user_id=u.id ORDER BY u.name")->fetchAll();

$activeShipments = $pdo->query("
  SELECT s.id, s.order_number, s.status,
         tp.lat, tp.lng, tp.recorded_at
  FROM shipments s
  LEFT JOIN (
    SELECT t1.* FROM tracking_points t1
    JOIN (
      SELECT shipment_id, MAX(recorded_at) maxd FROM tracking_points GROUP BY shipment_id
    ) t2 ON t1.shipment_id=t2.shipment_id AND t1.recorded_at=t2.maxd
  ) tp ON tp.shipment_id = s.id
  WHERE s.status IN ('en_route_to_pickup','at_pickup','in_transit')
")->fetchAll();

$onDeliveryDrivers = (int)$pdo->query("SELECT COUNT(DISTINCT driver_id) c FROM shipments WHERE status IN ('en_route_to_pickup','at_pickup','in_transit') AND driver_id IS NOT NULL")->fetch()['c'];
$offlineDrivers = (int)$pdo->query("SELECT COUNT(*) c FROM driver_profiles WHERE availability = 0")->fetch()['c'];
?>

<section id="admin" class="py-6 bg-white section">
  <h2 class="text-3xl font-bold text-center mb-12 text-dark">Admin Dashboard</h2>

  <div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-blue-50 p-6 rounded-lg shadow-sm">
      <div class="flex justify-between items-start">
        <div><p class="text-sm text-gray-600">Total Orders</p><h3 class="text-2xl font-bold mt-1"><?= $totOrders; ?></h3></div>
        <div class="bg-blue-100 p-3 rounded-full"><i class="fas fa-clipboard-list text-primary"></i></div>
      </div>
    </div>
    <div class="bg-green-50 p-6 rounded-lg shadow-sm">
      <div class="flex justify-between items-start">
        <div><p class="text-sm text-gray-600">Active Drivers</p><h3 class="text-2xl font-bold mt-1"><?= $activeDrivers; ?></h3></div>
        <div class="bg-green-100 p-3 rounded-full"><i class="fas fa-truck text-secondary"></i></div>
      </div>
    </div>
    <div class="bg-yellow-50 p-6 rounded-lg shadow-sm">
      <div class="flex justify-between items-start">
        <div><p class="text-sm text-gray-600">Revenue</p><h3 class="text-2xl font-bold mt-1">$<?= number_format($revenue, 2); ?></h3></div>
        <div class="bg-yellow-100 p-3 rounded-full"><i class="fas fa-dollar-sign text-accent"></i></div>
      </div>
    </div>
    <div class="bg-purple-50 p-6 rounded-lg shadow-sm">
      <div class="flex justify-between items-start">
        <div><p class="text-sm text-gray-600">Pending/Assigned</p><h3 class="text-2xl font-bold mt-1"><?= $pending; ?></h3></div>
        <div class="bg-purple-100 p-3 rounded-full"><i class="fas fa-exclamation-circle text-purple-600"></i></div>
      </div>
    </div>
  </div>

  <div class="grid md:grid-cols-3 gap-8">
    <!-- Orders Management -->
    <div class="bg-gray-50 rounded-xl p-6 shadow-md">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold">Orders Management</h3>
        <span class="text-primary text-sm font-medium">Latest</span>
      </div>
      <div class="space-y-4">
        <?php foreach ($latest as $o): ?>
        <div class="bg-white p-4 rounded-lg shadow-sm">
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">#<?= htmlspecialchars($o['order_number']); ?></span>
            <span class="text-xs px-2 py-1 rounded
              <?= $o['status']==='pending'?'bg-blue-100 text-blue-800':
                  ($o['status']==='in_transit'?'bg-green-100 text-green-800':
                  ($o['status']==='assigned'?'bg-yellow-100 text-yellow-800':'bg-gray-100 text-gray-800')) ?>">
              <?= htmlspecialchars($o['status']); ?>
            </span>
          </div>
          <p class="text-sm text-gray-600"><?= htmlspecialchars($o['cargo_type']); ?> • <?= (int)$o['weight_lbs']; ?> lbs</p>
        <div class="flex justify-between items-center mt-3">
  <span class="text-sm"><?= htmlspecialchars($o['pickup_city']); ?> → <?= htmlspecialchars($o['delivery_city']); ?></span>
  <div class="space-x-2">
    <a class="text-sm font-medium text-primary" href="assign_shipment.php?id=<?= (int)$o['id']; ?>">Assign</a>
    <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700"
            onclick="openShipmentChat(<?= (int)$o['id']; ?>, 'Shipment #<?= htmlspecialchars($o['order_number']); ?>')">
      Chat
    </button>
  </div>
</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Driver Management -->
    <div class="bg-gray-50 rounded-xl p-6 shadow-md">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold">Driver Management</h3>
        <span class="text-primary text-sm font-medium">Overview</span>
      </div>
      <div class="space-y-4">
        <?php foreach ($drivers as $d): ?>
        <div class="bg-white p-4 rounded-lg shadow-sm">
          <div class="flex items-center justify-between">
  <div>
    <h4 class="font-medium"><?= htmlspecialchars($d['name']); ?></h4>
    <p class="text-xs text-gray-600">Capacity: <?= (int)$d['capacity_lbs']; ?> lbs</p>
  </div>
  <div class="flex items-center space-x-2">
    <span class="text-xs px-2 py-1 rounded <?= $d['availability'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
      <?= $d['availability'] ? 'Available' : 'Unavailable'; ?>
    </span>
    <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700"
            onclick="openAdminDriverChat(<?= (int)$d['id']; ?>, 'Chat: <?= htmlspecialchars($d['name']); ?>')">
      Chat
    </button>
  </div>
</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Revenue Analytics -->
    <div class="bg-gray-50 rounded-xl p-6 shadow-md">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold">Revenue Analytics</h3>
        <div class="flex space-x-2">
          <button class="text-xs bg-primary text-white px-2 py-1 rounded">Weekly</button>
          <button class="text-xs bg-white text-gray-600 px-2 py-1 rounded">Monthly</button>
          <button class="text-xs bg-white text-gray-600 px-2 py-1 rounded">Yearly</button>
        </div>
      </div>
      <div class="h-48 bg-white rounded-lg p-4 flex items-center justify-center mb-4">
        <div class="w-full h-full flex items-end space-x-2">
          <div class="w-1/6 bg-blue-200 rounded-t" style="height: 30%;"></div>
          <div class="w-1/6 bg-blue-300 rounded-t" style="height: 50%;"></div>
          <div class="w-1/6 bg-blue-400 rounded-t" style="height: 70%;"></div>
          <div class="w-1/6 bg-blue-500 rounded-t" style="height: 90%;"></div>
          <div class="w-1/6 bg-blue-600 rounded-t" style="height: 60%;"></div>
          <div class="w-1/6 bg-blue-700 rounded-t" style="height: 40%;"></div>
        </div>
      </div>
      <div class="space-y-3">
        <div class="flex justify-between"><span class="text-sm">Total Revenue</span><span class="font-medium">$<?= number_format($revenue,2); ?></span></div>
        <div class="flex justify-between"><span class="text-sm">Completed Orders</span><span class="font-medium"><?= (int)$pdo->query("SELECT COUNT(*) c FROM shipments WHERE status='delivered'")->fetch()['c']; ?></span></div>
        <div class="flex justify-between"><span class="text-sm">Average Order Value</span><span class="font-medium">$<?= number_format($revenue / max(1,(int)$pdo->query("SELECT COUNT(*) c FROM shipments WHERE status='delivered'")->fetch()['c']), 0); ?></span></div>
        <div class="flex justify-between"><span class="text-sm">Top Route</span><span class="font-medium">—</span></div>
      </div>
    </div>
  </div>

  <!-- Fleet Tracking -->
  <div class="mt-8 bg-gray-50 rounded-xl p-6 shadow-md">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-xl font-semibold">Real-time Fleet Tracking</h3>
      <span class="text-primary text-sm font-medium">Live</span>
    </div>

    <div id="fleet-map" class="h-64 w-full rounded-lg relative"></div>

    <div class="mt-4 grid md:grid-cols-3 gap-4">
      <div class="bg-white p-3 rounded-lg shadow-sm flex items-center space-x-2">
        <div class="w-3 h-3 rounded-full bg-green-500"></div><span class="text-sm">Available (<?= $activeDrivers; ?>)</span>
      </div>
      <div class="bg-white p-3 rounded-lg shadow-sm flex items-center space-x-2">
        <div class="w-3 h-3 rounded-full bg-blue-500"></div><span class="text-sm">On Delivery (<?= $onDeliveryDrivers; ?>)</span>
      </div>
      <div class="bg-white p-3 rounded-lg shadow-sm flex items-center space-x-2">
        <div class="w-3 h-3 rounded-full bg-gray-500"></div><span class="text-sm">Offline (<?= $offlineDrivers; ?>)</span>
      </div>
    </div>
  </div>
</section>

<script>
  const fMap = L.map('fleet-map').setView([39.5,-98.35], 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap' }).addTo(fMap);
  const shipments = <?= json_encode($activeShipments); ?>;
  const markers = [];
  shipments.forEach(s => {
    if (!s.lat || !s.lng) return;
    const m = L.marker([s.lat, s.lng]).addTo(fMap).bindPopup(`#${s.order_number} • ${s.status}`);
    markers.push(m);
  });
  if (markers.length) {
    const group = L.featureGroup(markers);
    fMap.fitBounds(group.getBounds(), { padding: [20,20] });
  }
</script>
<?php include __DIR__ . '/../chat-widget.php'; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
