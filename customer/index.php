<?php
require_once __DIR__ . '/../auth.php';
require_role('customer');
require_once __DIR__ . '/../partials/header.php';

$uid = current_user()['id'];
$shipments = $pdo->prepare("SELECT * FROM shipments WHERE customer_id = ? ORDER BY created_at DESC");
$shipments->execute([$uid]);
$shipments = $shipments->fetchAll();

$recentDelivered = $pdo->prepare("SELECT * FROM shipments WHERE customer_id = ? AND status='delivered' ORDER BY updated_at DESC LIMIT 3");
$recentDelivered->execute([$uid]);
$recentDelivered = $recentDelivered->fetchAll();

$active = $pdo->prepare("
  SELECT s.*, tp.lat, tp.lng FROM shipments s
  LEFT JOIN (
    SELECT t1.* FROM tracking_points t1
    JOIN (SELECT shipment_id, MAX(recorded_at) maxd FROM tracking_points GROUP BY shipment_id) t2
      ON t1.shipment_id=t2.shipment_id AND t1.recorded_at=t2.maxd
  ) tp ON tp.shipment_id = s.id
  WHERE s.customer_id = ? AND s.status IN ('assigned','en_route_to_pickup','at_pickup','in_transit')
  ORDER BY s.updated_at DESC LIMIT 1
");
$active->execute([$uid]);
$preview = $active->fetch();
?>
<section id="customer" class="py-6 bg-white section">
  <h2 class="text-3xl font-bold text-center mb-8 text-dark">Customer Dashboard</h2>
  <div class="bg-gray-50 rounded-xl shadow-lg overflow-hidden">
    <div class="md:flex">
      <!-- Sidebar -->
      <div class="md:w-1/4 bg-primary text-white p-6">
        <div class="flex items-center space-x-3 mb-8">
          <div class="w-12 h-12 rounded-full bg-white text-primary flex items-center justify-center"><i class="fas fa-user-tie text-xl"></i></div>
          <div>
            <h3 class="font-medium"><?= htmlspecialchars(current_user()['name']); ?></h3>
            <p class="text-xs text-blue-100"><?= htmlspecialchars(current_user()['company'] ?? ''); ?></p>
          </div>
        </div>
        <div class="space-y-1 mb-8">
          <div class="flex items-center space-x-3 p-2 rounded-lg bg-blue-700">
            <i class="fas fa-tachometer-alt w-5 text-center"></i><span>Dashboard</span>
          </div>
          <a class="flex items-center space-x-3 p-2 rounded-lg hover:bg-blue-700" href="<?= base_path('/customer/index.php'); ?>">
            <i class="fas fa-shipping-fast w-5 text-center"></i><span>My Shipments</span>
          </a>
          <a class="flex items-center space-x-3 p-2 rounded-lg hover:bg-blue-700" href="<?= base_path('/customer/new_shipment.php'); ?>">
            <i class="fas fa-file-invoice-dollar w-5 text-center"></i><span>New Shipment</span>
          </a>
          <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-blue-700 cursor-pointer" id="trackAnother">
            <i class="fas fa-map-marker-alt w-5 text-center"></i><span>Track Another</span>
          </div>
        </div>
        <div class="bg-blue-700 rounded-lg p-4">
          <h4 class="text-sm font-medium mb-2">Quick Actions</h4>
          <a href="<?= base_path('/customer/new_shipment.php'); ?>" class="w-full bg-white text-primary text-sm py-1 rounded mb-2 block text-center">New Shipment</a>
          <button id="trackBtn" class="w-full border border-white text-white text-sm py-1 rounded">Track Another</button>
        </div>
      </div>

      <!-- Main content -->
      <div class="md:w-3/4 p-8">
        <div class="flex justify-between items-center mb-8">
          <h3 class="text-xl font-semibold">My Shipments</h3>
          <a href="<?= base_path('/customer/new_shipment.php'); ?>" class="text-sm bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ New Shipment</a>
        </div>

        <!-- Shipments table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($shipments as $s): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= htmlspecialchars($s['order_number']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars(($s['pickup_city'] ?? '').' → '.($s['delivery_city'] ?? '')); ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                    <?= $s['status']==='in_transit'?'bg-blue-100 text-blue-800':
                        ($s['status']==='delivered'?'bg-green-100 text-green-800':
                        ($s['status']==='assigned'?'bg-yellow-100 text-yellow-800':'bg-gray-100 text-gray-800')) ?>">
                    <?= htmlspecialchars($s['status']); ?>
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?= number_format((float)$s['total_amount'],2); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
  <a href="<?= base_path('/customer/track.php'); ?>?order=<?= urlencode($s['order_number']); ?>" class="text-primary hover:text-blue-700 mr-3">Track</a>
  <?php if ($s['status'] !== 'pending' && $s['status'] !== 'delivered'): ?>
    <button class="bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700 mr-2"
            onclick="openShipmentChat(<?= (int)$s['id']; ?>, 'Chat with Driver - Order #<?= htmlspecialchars($s['order_number']); ?>')">
      Chat
    </button>
  <?php endif; ?>
  <span class="text-gray-400">Details</span>
</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Recent Shipments -->
        <div class="mb-8">
          <h4 class="font-medium mb-4">Recent Shipments</h4>
          <div class="grid md:grid-cols-3 gap-4">
            <?php foreach ($recentDelivered as $r): ?>
            <div class="bg-white p-4 rounded-lg shadow-sm">
              <div class="flex justify-between items-start mb-2">
                <span class="font-medium">#<?= htmlspecialchars($r['order_number']); ?></span>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Delivered</span>
              </div>
              <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($r['cargo_type']); ?> • <?= (int)$r['weight_lbs']; ?> lbs</p>
              <p class="text-sm mb-1"><?= htmlspecialchars($r['pickup_city']); ?> → <?= htmlspecialchars($r['delivery_city']); ?></p>
              <p class="text-xs text-gray-500">Updated: <?= htmlspecialchars($r['updated_at']); ?></p>
              <div class="mt-3 flex justify-between items-center">
  <span class="text-sm font-medium">$<?= number_format((float)$r['total_amount'],2); ?></span>
  <div class="space-x-1">
    <a class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded" href="<?= base_path('/customer/track.php'); ?>?order=<?= urlencode($r['order_number']); ?>">View</a>
  </div>
</div>
            </div>
            <?php endforeach; ?>
            <?php if (!$recentDelivered): ?>
              <div class="text-sm text-slate-600">No delivered shipments yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tracking Preview -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h4 class="font-medium mb-4">Tracking Preview <?= $preview ? '#'.$preview['order_number'] : ''; ?></h4>
          <div id="preview-map" class="h-48 rounded-lg mb-4"></div>
          <div class="flex justify-between items-center">
            <div>
              <p class="font-medium">Current Location</p>
              <p class="text-sm text-gray-600" id="preview-info"><?= $preview && $preview['lat'] && $preview['lng'] ? 'Showing last known position' : 'No tracking data yet'; ?></p>
            </div>
            <div class="text-right">
              <p class="font-medium">Status</p>
              <p class="text-sm text-gray-600"><?= htmlspecialchars($preview['status'] ?? '—'); ?></p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
  document.getElementById('trackBtn')?.addEventListener('click', () => {
    const order = prompt('Enter your Order Number (e.g., ORD-2025-01234)');
    if (order) window.location = '<?= base_path('/customer/track.php'); ?>?order=' + encodeURIComponent(order);
  });
  document.getElementById('trackAnother')?.addEventListener('click', () => document.getElementById('trackBtn')?.click());

  const pv = L.map('preview-map').setView([39.5, -98.35], 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap' }).addTo(pv);
  <?php if ($preview && $preview['lat'] && $preview['lng']): ?>
    const m = L.marker([<?= $preview['lat']; ?>, <?= $preview['lng']; ?>]).addTo(pv);
    pv.setView([<?= $preview['lat']; ?>, <?= $preview['lng']; ?>], 6);
  <?php endif; ?>
</script>
<?php include __DIR__ . '/../chat-widget.php'; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
