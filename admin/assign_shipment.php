<?php
// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files - but NOT header.php yet
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
$stmt->execute([$id]);
$shipment = $stmt->fetch();

// Handle form submission BEFORE including header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $driver_id = (int)($_POST['driver_id'] ?? 0);
  if ($driver_id) {
    try {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE shipments SET driver_id = ?, status = 'assigned' WHERE id = ?")->execute([$driver_id, $id]);
      $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, user_id, status, note) VALUES (?, ?, 'assigned', 'Admin assigned driver')")->execute([$id, current_user()['id']]);
      $pdo->commit();
      flash('success', 'Driver assigned.');
      // Redirect BEFORE any HTML output
      header('Location: ' . base_path('/admin/index.php')); 
      exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      flash('error', 'Assign failed: ' . $e->getMessage());
    }
  } else {
    flash('error', 'Please select a driver.');
  }
}

// Get drivers data
$drivers = $pdo->query("SELECT u.id, u.name, dp.capacity_lbs, dp.availability FROM users u JOIN driver_profiles dp ON dp.user_id=u.id WHERE u.role='driver' ORDER BY dp.availability DESC, u.name")->fetchAll();

// NOW include header.php after all potential redirects
require_once __DIR__ . '/../partials/header.php';

// Check if shipment exists AFTER including header
if (!$shipment) { 
    echo '<div class="bg-red-50 text-red-700 p-3 rounded">Shipment not found</div>'; 
    require_once __DIR__ . '/../partials/footer.php'; 
    exit; 
}
?>

<div class="max-w-4xl mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Assign Driver to Shipment</h2>
  
  <?php if ($msg = flash('error')): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($msg); ?></div>
  <?php elseif ($msg = flash('success')): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <!-- Shipment Details -->
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold mb-4">Shipment Details</h3>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <p><strong>Order Number:</strong> <?= htmlspecialchars($shipment['order_number']); ?></p>
        <p><strong>Cargo Type:</strong> <?= htmlspecialchars($shipment['cargo_type'] ?? 'N/A'); ?></p>
        <p><strong>Weight:</strong> <?= (int)$shipment['weight_lbs']; ?> lbs</p>
        <p><strong>Container Size:</strong> <?= htmlspecialchars($shipment['container_size'] ?? 'N/A'); ?></p>
      </div>
      <div>
        <p><strong>Pickup:</strong> <?= htmlspecialchars($shipment['pickup_city'] ?? 'N/A'); ?>, <?= htmlspecialchars($shipment['pickup_state'] ?? 'N/A'); ?></p>
        <p><strong>Delivery:</strong> <?= htmlspecialchars($shipment['delivery_city'] ?? 'N/A'); ?>, <?= htmlspecialchars($shipment['delivery_state'] ?? 'N/A'); ?></p>
        <p><strong>Status:</strong> <span class="px-2 py-1 bg-gray-100 rounded text-sm"><?= htmlspecialchars($shipment['status']); ?></span></p>
        <p><strong>Total Amount:</strong> $<?= number_format((float)$shipment['total_amount'], 2); ?></p>
      </div>
    </div>
  </div>

  <!-- Driver Assignment Form -->
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Assign Driver</h3>
    
    <?php if (!$drivers): ?>
      <div class="bg-yellow-50 text-yellow-700 p-4 rounded mb-4">
        <p>No drivers found. Please ensure drivers are registered in the system.</p>
        <p class="text-sm mt-2">
          <a href="<?= base_path('/register.php'); ?>" class="text-blue-600 underline">Register a new driver</a> or 
          check that existing drivers have completed their profiles.
        </p>
      </div>
    <?php else: ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Driver</label>
        <select name="driver_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary">
          <option value="">-- Choose a driver --</option>
          <?php foreach ($drivers as $d): ?>
            <option value="<?= (int)$d['id']; ?>" <?= !$d['availability'] ? 'style="color: #999;"' : ''; ?>>
              <?= htmlspecialchars($d['name']); ?> 
              (Capacity: <?= number_format((int)$d['capacity_lbs']); ?> lbs)
              <?= $d['availability'] ? ' ✅ Available' : ' ⚪ Unavailable' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-sm text-gray-500 mt-1">
          ✅ Available drivers can accept new shipments immediately.<br>
          ⚪ Unavailable drivers are currently offline or busy.
        </p>
      </div>

      <div class="flex justify-between items-center">
        <a href="<?= base_path('/admin/index.php'); ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
          ← Back to Admin Dashboard
        </a>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Assign Driver
        </button>
      </div>
    </form>

    <?php endif; ?>
  </div>

  <!-- Available Drivers List -->
  <?php if ($drivers): ?>
  <div class="bg-white rounded-lg shadow p-6 mt-6">
    <h3 class="text-lg font-semibold mb-4">All Drivers (<?= count($drivers); ?>)</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suitable</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($drivers as $d): 
            $suitable = (int)$d['capacity_lbs'] >= (int)$shipment['weight_lbs'];
          ?>
          <tr class="<?= $d['availability'] ? '' : 'bg-gray-50 opacity-75'; ?>">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              <?= htmlspecialchars($d['name']); ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?= number_format((int)$d['capacity_lbs']); ?> lbs
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $d['availability'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                <?= $d['availability'] ? '✅ Available' : '⚪ Unavailable'; ?>
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $suitable ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                <?= $suitable ? '✅ Can handle' : '❌ Too heavy'; ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <div class="mt-4 text-sm text-gray-600">
      <p><strong>Shipment weight:</strong> <?= number_format((int)$shipment['weight_lbs']); ?> lbs</p>
      <p>Only drivers with sufficient capacity can handle this shipment.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>