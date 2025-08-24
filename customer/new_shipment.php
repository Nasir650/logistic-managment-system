<?php
require_once __DIR__ . '/../auth.php';
require_role('customer');
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  set_old($_POST);
  $user = current_user();

  $cargo_type = $_POST['cargo_type'] ?? 'General Goods';
  $container_size = $_POST['container_size'] ?? 'Medium';
  $weight_lbs = (int)($_POST['weight_lbs'] ?? 0);
  $volume_cuft = (int)($_POST['volume_cuft'] ?? 0);
  $special = $_POST['special_instructions'] ?? '';
  $hazmat = isset($_POST['hazmat']);

  $pickup_name = $_POST['pickup_name'] ?? '';
  $pickup_address1 = $_POST['pickup_address1'] ?? '';
  $pickup_city = $_POST['pickup_city'] ?? '';
  $pickup_state = $_POST['pickup_state'] ?? '';
  $pickup_zip = $_POST['pickup_zip'] ?? '';
  $pickup_contact = $_POST['pickup_contact'] ?? '';
  $pickup_phone = $_POST['pickup_phone'] ?? '';
  $pickup_datetime = $_POST['pickup_datetime'] ?? null;

  $delivery_name = $_POST['delivery_name'] ?? '';
  $delivery_address1 = $_POST['delivery_address1'] ?? '';
  $delivery_city = $_POST['delivery_city'] ?? '';
  $delivery_state = $_POST['delivery_state'] ?? '';
  $delivery_zip = $_POST['delivery_zip'] ?? '';
  $delivery_contact = $_POST['delivery_contact'] ?? '';
  $delivery_phone = $_POST['delivery_phone'] ?? '';
  $delivery_deadline = $_POST['delivery_deadline'] ?? null;

  $pricing = price_estimate($weight_lbs, $container_size, $hazmat);

  try {
    $pdo->beginTransaction();
    $order = generate_order_number($pdo);
    
    // Fixed SQL - removed 'paid' column and matched parameters exactly
    $stmt = $pdo->prepare("INSERT INTO shipments
      (order_number, customer_id, cargo_type, container_size, weight_lbs, volume_cuft, special_instructions,
       pickup_name, pickup_address1, pickup_city, pickup_state, pickup_zip, pickup_contact, pickup_phone, pickup_datetime,
       delivery_name, delivery_address1, delivery_city, delivery_state, delivery_zip, delivery_contact, delivery_phone, delivery_deadline,
       status, base_rate, distance_fee, surcharge, total_amount)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
    
    // Fixed parameter array - exactly 28 parameters to match 28 placeholders
    $stmt->execute([
      $order,                 // 1 - order_number
      $user['id'],           // 2 - customer_id
      $cargo_type,           // 3 - cargo_type
      $container_size,       // 4 - container_size
      $weight_lbs,           // 5 - weight_lbs
      $volume_cuft,          // 6 - volume_cuft
      $special,              // 7 - special_instructions
      $pickup_name,          // 8 - pickup_name
      $pickup_address1,      // 9 - pickup_address1
      $pickup_city,          // 10 - pickup_city
      $pickup_state,         // 11 - pickup_state
      $pickup_zip,           // 12 - pickup_zip
      $pickup_contact,       // 13 - pickup_contact
      $pickup_phone,         // 14 - pickup_phone
      $pickup_datetime,      // 15 - pickup_datetime
      $delivery_name,        // 16 - delivery_name
      $delivery_address1,    // 17 - delivery_address1
      $delivery_city,        // 18 - delivery_city
      $delivery_state,       // 19 - delivery_state
      $delivery_zip,         // 20 - delivery_zip
      $delivery_contact,     // 21 - delivery_contact
      $delivery_phone,       // 22 - delivery_phone
      $delivery_deadline,    // 23 - delivery_deadline
      // status = 'pending' is hardcoded in SQL, not a parameter
      $pricing['base'],      // 24 - base_rate
      $pricing['distance'],  // 25 - distance_fee
      $pricing['surcharge'], // 26 - surcharge
      $pricing['total']      // 27 - total_amount
    ]);
    
    $id = (int)$pdo->lastInsertId();
    
    // Create status history entry
    $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, user_id, status, note) VALUES (?, ?, 'pending', 'Shipment created')")
        ->execute([$id, $user['id']]);
        
    $pdo->commit();
    flash('success', 'Shipment created: ' . $order);
    clear_old();
    header('Location: ' . base_path('/customer/index.php')); 
    exit;
    
  } catch (Exception $e) {
    $pdo->rollBack(); 
    flash('error', 'Error: '.$e->getMessage()); 
    header('Location: ' . base_path('/customer/new_shipment.php')); 
    exit;
  }
}

require_once __DIR__ . '/../partials/header.php';
?>

<section id="booking" class="py-4 bg-gray-50 section w-full">
  <h2 class="text-3xl font-bold text-center mb-8 text-dark">Customer Booking Form</h2>
  <?php if ($msg = flash('error')): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded mb-4 max-w-3xl mx-auto"><?= htmlspecialchars($msg); ?></div>
  <?php elseif ($msg = flash('success')): ?>
    <div class="bg-emerald-50 text-emerald-700 p-3 rounded mb-4 max-w-3xl mx-auto"><?= htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="md:flex">
      <!-- Steps -->
      <div class="md:w-1/4 bg-gray-100 p-6">
        <div class="space-y-6" id="stepNav">
          <div class="flex items-center space-x-3 step-item" data-step="0">
            <div class="step-dot bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center">1</div><span class="font-medium">Cargo Details</span>
          </div>
          <div class="flex items-center space-x-3 step-item text-gray-500" data-step="1">
            <div class="step-dot border-2 border-gray-300 rounded-full w-8 h-8 flex items-center justify-center">2</div><span>Pickup Information</span>
          </div>
          <div class="flex items-center space-x-3 step-item text-gray-500" data-step="2">
            <div class="step-dot border-2 border-gray-300 rounded-full w-8 h-8 flex items-center justify-center">3</div><span>Delivery Information</span>
          </div>
          <div class="flex items-center space-x-3 step-item text-gray-500" data-step="3">
            <div class="step-dot border-2 border-gray-300 rounded-full w-8 h-8 flex items-center justify-center">4</div><span>Additional Services</span>
          </div>
          <div class="flex items-center space-x-3 step-item text-gray-500" data-step="4">
            <div class="step-dot border-2 border-gray-300 rounded-full w-8 h-8 flex items-center justify-center">5</div><span>Payment & Confirmation</span>
          </div>
        </div>

        <div class="mt-12">
          <h4 class="font-medium mb-3">Need Help?</h4>
          <div class="flex items-center text-sm text-gray-600 mb-2"><i class="fas fa-phone-alt mr-2"></i><span>+1 (800) 123-4567</span></div>
          <div class="flex items-center text-sm text-gray-600"><i class="fas fa-envelope mr-2"></i><span>support@logitrack.com</span></div>
        </div>
      </div>

      <!-- Form -->
      <div class="md:w-3/4 p-8">
        <form id="bookingForm" method="post" class="space-y-6">
          <!-- Step 0 -->
          <div class="step-panel" data-step="0">
            <h3 class="text-xl font-semibold mb-6">Cargo Details</h3>
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cargo Type</label>
                <select name="cargo_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  <option>General Goods</option>
                  <option>Hazardous Materials</option>
                  <option>Perishable Goods</option>
                  <option>Oversized Load</option>
                  <option>Fragile Items</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Container Size</label>
                <select name="container_size" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  <option>Small</option>
                  <option selected>Medium</option>
                  <option>Large</option>
                  <option>Extra Large</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Weight (lbs)</label>
                <input type="number" name="weight_lbs" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter weight">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Volume (cu ft)</label>
                <input type="number" name="volume_cuft" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter volume">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Special Handling Instructions</label>
                <textarea name="special_instructions" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" rows="3" placeholder="Any special requirements for your cargo"></textarea>
              </div>
            </div>
          </div>

          <!-- Step 1 -->
          <div class="step-panel hidden" data-step="1">
            <h3 class="text-xl font-semibold mb-6">Pickup Information</h3>
            <div class="grid md:grid-cols-2 gap-6">
              <input name="pickup_name" class="w-full px-4 py-2 border rounded-lg" placeholder="Company/Name">
              <input name="pickup_contact" class="w-full px-4 py-2 border rounded-lg" placeholder="Contact Name">
              <input name="pickup_phone" class="w-full px-4 py-2 border rounded-lg" placeholder="Phone">
              <input name="pickup_address1" class="w-full px-4 py-2 border rounded-lg" placeholder="Address">
              <input name="pickup_city" class="w-full px-4 py-2 border rounded-lg" placeholder="City">
              <input name="pickup_state" class="w-full px-4 py-2 border rounded-lg" placeholder="State">
              <input name="pickup_zip" class="w-full px-4 py-2 border rounded-lg" placeholder="ZIP">
              <input type="datetime-local" name="pickup_datetime" class="w-full px-4 py-2 border rounded-lg">
            </div>
          </div>

          <!-- Step 2 -->
          <div class="step-panel hidden" data-step="2">
            <h3 class="text-xl font-semibold mb-6">Delivery Information</h3>
            <div class="grid md:grid-cols-2 gap-6">
              <input name="delivery_name" class="w-full px-4 py-2 border rounded-lg" placeholder="Company/Name">
              <input name="delivery_contact" class="w-full px-4 py-2 border rounded-lg" placeholder="Contact Name">
              <input name="delivery_phone" class="w-full px-4 py-2 border rounded-lg" placeholder="Phone">
              <input name="delivery_address1" class="w-full px-4 py-2 border rounded-lg" placeholder="Address">
              <input name="delivery_city" class="w-full px-4 py-2 border rounded-lg" placeholder="City">
              <input name="delivery_state" class="w-full px-4 py-2 border rounded-lg" placeholder="State">
              <input name="delivery_zip" class="w-full px-4 py-2 border rounded-lg" placeholder="ZIP">
              <input type="datetime-local" name="delivery_deadline" class="w-full px-4 py-2 border rounded-lg">
            </div>
          </div>

          <!-- Step 3 -->
          <div class="step-panel hidden" data-step="3">
            <h3 class="text-xl font-semibold mb-6">Additional Services</h3>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="hazmat"><span>Hazardous Materials (+$50)</span>
            </label>
          </div>

          <!-- Step 4 -->
          <div class="step-panel hidden" data-step="4">
            <h3 class="text-xl font-semibold mb-6">Payment & Confirmation</h3>
            <div class="bg-blue-50 p-6 rounded-lg" id="priceBox">
              <h4 class="font-medium mb-4">Estimated Price</h4>
              <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600">Base Rate</span><span class="font-medium" id="price-base">$0.00</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Surcharges</span><span class="font-medium" id="price-surcharge">$0.00</span></div>
                <div class="border-t border-gray-300 my-2"></div>
                <div class="flex justify-between font-bold text-lg"><span>Total Estimate</span><span id="price-total">$0.00</span></div>
              </div>
              <p class="text-sm text-gray-500 mt-3">Final price may vary based on exact distance and additional services selected.</p>
            </div>
          </div>

          <!-- Nav buttons -->
          <div class="mt-8 flex justify-between">
            <a href="<?= base_path('/customer/index.php'); ?>" class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
            <div class="space-x-2">
              <button type="button" id="prevBtn" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hidden">Back</button>
              <button type="button" id="nextBtn" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">Next</button>
              <button type="submit" id="submitBtn" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 hidden">Submit Booking</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
  let step = 0;
  const panels = [...document.querySelectorAll('.step-panel')];
  const navItems = [...document.querySelectorAll('#stepNav .step-item')];
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const submitBtn = document.getElementById('submitBtn');

  function updateUI() {
    panels.forEach(p => p.classList.toggle('hidden', +p.dataset.step !== step));
    navItems.forEach(n => {
      const idx = +n.dataset.step;
      const dot = n.querySelector('.step-dot');
      if (idx < step) { dot.classList.remove('border-2','border-gray-300'); dot.classList.add('bg-primary','text-white'); }
      else if (idx === step) { dot.classList.remove('border-2','border-gray-300'); dot.classList.add('bg-primary','text-white'); }
      else { dot.classList.remove('bg-primary','text-white'); dot.classList.add('border-2','border-gray-300'); }
      n.classList.toggle('text-gray-500', idx > step);
    });
    prevBtn.classList.toggle('hidden', step === 0);
    nextBtn.classList.toggle('hidden', step === panels.length - 1);
    submitBtn.classList.toggle('hidden', step !== panels.length - 1);
    if (step === panels.length - 1) calcPrice();
  }
  function calcPrice() {
    const form = document.getElementById('bookingForm');
    const size = form.container_size.value;
    const weight = parseInt(form.weight_lbs.value || '0', 10);
    const hazmat = form.hazmat.checked;
    let base = 200;
    if (size === 'Small') base = 150;
    if (size === 'Medium') base = 250;
    if (size === 'Large') base = 400;
    if (size === 'Extra Large') base = 600;
    const weightSurcharge = Math.max(0, Math.floor(weight/500)*25);
    const hazmatFee = hazmat ? 50 : 0;
    const total = base + weightSurcharge + hazmatFee;
    document.getElementById('price-base').textContent = `$${base.toFixed(2)}`;
    document.getElementById('price-surcharge').textContent = `$${(weightSurcharge+hazmatFee).toFixed(2)}`;
    document.getElementById('price-total').textContent = `$${total.toFixed(2)}`;
  }
  nextBtn.addEventListener('click', () => { if (step < panels.length - 1) step++; updateUI(); });
  prevBtn.addEventListener('click', () => { if (step > 0) step--; updateUI(); });
  updateUI();
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>