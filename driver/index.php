<?php
require_once __DIR__ . '/../auth.php';
require_role('driver');
require_once __DIR__ . '/../partials/header.php';

$uid = current_user()['id'];

$stmt = $pdo->prepare("SELECT * FROM shipments WHERE driver_id = ? AND status IN ('assigned','en_route_to_pickup','at_pickup','in_transit') ORDER BY created_at DESC");
$stmt->execute([$uid]);
$jobs = $stmt->fetchAll();
$assigned = array_filter($jobs, fn($j) => $j['status'] === 'assigned');

$av = $pdo->prepare("SELECT availability FROM driver_profiles WHERE user_id = ?");
$av->execute([$uid]);
$availability = (int)($av->fetch()['availability'] ?? 1);
?>

<section id="driver" class="py-4 bg-gray-50 section">
  <h2 class="text-3xl font-bold text-center mb-6 text-dark">Driver Dashboard</h2>

  <div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="md:flex">
      <!-- Sidebar -->
      <div class="md:w-1/4 bg-dark text-white p-6">
        <div class="flex items-center space-x-3 mb-8">
          <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center"><i class="fas fa-user text-xl"></i></div>
          <div><h3 class="font-medium"><?= htmlspecialchars(current_user()['name']); ?></h3><p class="text-xs text-gray-300">Driver</p></div>
        </div>

        <div class="space-y-1 mb-8">
          <div class="flex items-center space-x-3 p-2 rounded-lg bg-gray-700"><i class="fas fa-tachometer-alt w-5 text-center"></i><span>Dashboard</span></div>
          <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-700"><i class="fas fa-clipboard-list w-5 text-center"></i><span>My Orders</span></div>
          <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-700"><i class="fas fa-map-marked-alt w-5 text-center"></i><span>Navigation</span></div>
          <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-700"><i class="fas fa-chart-line w-5 text-center"></i><span>Earnings</span></div>
          <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-700"><i class="fas fa-star w-5 text-center"></i><span>Ratings</span></div>
        </div>

        <div class="bg-gray-700 rounded-lg p-4">
          <h4 class="text-sm font-medium mb-2">Availability</h4>
          <form method="post" action="toggle_availability.php" class="flex items-center justify-between">
            <span class="text-xs">Currently:</span>
            <input type="hidden" name="availability" value="<?= $availability ? 0 : 1; ?>">
            <button class="px-3 py-1 rounded text-xs <?= $availability ? 'bg-green-500' : 'bg-gray-500'; ?>">
              <?= $availability ? 'Available' : 'Unavailable'; ?>
            </button>
          </form>
          <p class="text-xs mt-2 text-gray-300">You're <?= $availability ? 'visible' : 'not visible'; ?> for new orders.</p>
        </div>
      </div>

      <!-- Main -->
      <div class="md:w-3/4 p-8">
        <?php if ($assigned): ?>
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold">Current Assignment</h3>
        </div>
        <div class="bg-amber-50 border border-amber-200 p-4 rounded mb-6">
          <h4 class="font-medium mb-3">Assignments to Accept</h4>
          <?php foreach ($assigned as $job): ?>
          <div class="bg-white p-4 rounded-lg shadow-sm flex items-center justify-between mb-3">
            <div>
              <div class="font-semibold"><?= htmlspecialchars($job['order_number']); ?></div>
              <div class="text-sm text-slate-600"><?= htmlspecialchars($job['pickup_city']); ?> → <?= htmlspecialchars($job['delivery_city']); ?> • <?= (int)$job['weight_lbs']; ?> lbs</div>
            </div>
            <div class="space-x-2">
              <form method="post" action="update_status.php" class="inline">
                <input type="hidden" name="shipment_id" value="<?= (int)$job['id']; ?>">
                <input type="hidden" name="action" value="accept">
                <button class="text-sm bg-primary text-white px-4 py-1 rounded">Accept</button>
              </form>
              <form method="post" action="update_status.php" class="inline">
                <input type="hidden" name="shipment_id" value="<?= (int)$job['id']; ?>">
                <input type="hidden" name="action" value="decline">
                <button class="text-sm bg-gray-200 text-gray-700 px-4 py-1 rounded">Decline</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 class="font-medium mb-2">My Active Shipments</h3>
        <?php if (!$jobs): ?>
          <div class="bg-white rounded p-6 shadow text-slate-600">No active shipments.</div>
        <?php endif; ?>

        <?php foreach ($jobs as $job): ?>
        <div class="bg-white rounded p-6 shadow mb-4">
          <div class="flex justify-between items-center">
            <div>
              <div class="font-semibold text-lg"><?= htmlspecialchars($job['order_number']); ?></div>
              <div class="text-sm text-slate-600"><?= htmlspecialchars($job['pickup_city']); ?> → <?= htmlspecialchars($job['delivery_city']); ?> | Status: <span class="font-medium"><?= htmlspecialchars($job['status']); ?></span></div>
            </div>
            <a class="text-blue-600" target="_blank" href="<?= base_path('/customer/track.php'); ?>?order=<?= urlencode($job['order_number']); ?>">View Tracking</a>
          </div>

          <div class="grid md:grid-cols-3 gap-4 mt-4">
            <div class="bg-blue-50 p-4 rounded">
              <div class="font-medium mb-1">Pickup</div>
              <div class="text-sm"><?= htmlspecialchars($job['pickup_name'] ?? ''); ?> — <?= htmlspecialchars($job['pickup_phone'] ?? ''); ?></div>
              <div class="text-xs text-slate-600"><?= htmlspecialchars($job['pickup_address1'] ?? ''); ?>, <?= htmlspecialchars($job['pickup_city'] ?? ''); ?>, <?= htmlspecialchars($job['pickup_state'] ?? ''); ?> <?= htmlspecialchars($job['pickup_zip'] ?? ''); ?></div>
              <div class="text-xs text-slate-600 mt-1">When: <?= htmlspecialchars($job['pickup_datetime'] ?? ''); ?></div>
            </div>
            <div class="bg-emerald-50 p-4 rounded">
              <div class="font-medium mb-1">Delivery</div>
              <div class="text-sm"><?= htmlspecialchars($job['delivery_name'] ?? ''); ?> — <?= htmlspecialchars($job['delivery_phone'] ?? ''); ?></div>
              <div class="text-xs text-slate-600"><?= htmlspecialchars($job['delivery_address1'] ?? ''); ?>, <?= htmlspecialchars($job['delivery_city'] ?? ''); ?>, <?= htmlspecialchars($job['delivery_state'] ?? ''); ?> <?= htmlspecialchars($job['delivery_zip'] ?? ''); ?></div>
              <div class="text-xs text-slate-600 mt-1">Deadline: <?= htmlspecialchars($job['delivery_deadline'] ?? ''); ?></div>
            </div>
      <div class="bg-gray-50 p-4 rounded">
  <div class="font-medium mb-1">Actions</div>
  <form method="post" action="update_status.php" class="space-x-2">
    <input type="hidden" name="shipment_id" value="<?= (int)$job['id']; ?>">
    <select name="status" class="border rounded px-2 py-1">
      <option value="en_route_to_pickup">En Route to Pickup</option>
      <option value="at_pickup">At Pickup</option>
      <option value="in_transit">In Transit</option>
      <option value="delivered">Delivered</option>
    </select>
    <button class="bg-primary text-white px-3 py-1 rounded">Update</button>
  </form>
  
  <!-- Chat Buttons -->
  <div class="mt-3 space-y-2">
    <button class="w-full bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700"
            onclick="openShipmentChat(<?= (int)$job['id']; ?>, 'Customer Chat - Order #<?= htmlspecialchars($job['order_number']); ?>')">
      Chat with Customer
    </button>
    <button class="w-full bg-gray-700 text-white px-3 py-1 rounded text-sm hover:bg-gray-800"
            onclick="openAdminDriverChat(<?= (int)current_user()['id']; ?>, 'Admin Support')">
      Chat with Admin
    </button>
  </div>
  
  <button id="share-<?= (int)$job['id']; ?>" class="mt-3 bg-slate-800 text-white px-3 py-1 rounded">Share Live Location</button>
</div>
          </div>
        </div>

        <script>
          (function(){
            const btn = document.getElementById('share-<?= (int)$job['id']; ?>');
            let watchId = null;
            btn.addEventListener('click', () => {
              if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
              if (watchId) { navigator.geolocation.clearWatch(watchId); watchId = null; btn.textContent = 'Share Live Location'; return; }
              btn.textContent = 'Sharing... Click to stop';
              watchId = navigator.geolocation.watchPosition(async (pos) => {
                const body = new URLSearchParams({
                  shipment_id: '<?= (int)$job['id']; ?>',
                  lat: pos.coords.latitude, lng: pos.coords.longitude,
                  speed: pos.coords.speed ? (pos.coords.speed * 2.23694) : ''
                });
                await fetch('update_location.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body });
              }, (err) => { alert('Location error: ' + err.message); }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 });
            });
          })();
        </script>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../chat-widget.php'; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
