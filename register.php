<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  set_old($_POST);
  $role = $_POST['role'] ?? 'customer';
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $company = trim($_POST['company'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';

  if ($password !== $password2) { flash('error', 'Passwords do not match'); header('Location: register.php'); exit; }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Invalid email'); header('Location: register.php'); exit; }

  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (role, email, password_hash, name, company, phone, status, email_verified) VALUES (?, ?, ?, ?, ?, ?, 'active', 0)");
    $stmt->execute([$role, $email, password_hash($password, PASSWORD_BCRYPT), $name, $role==='customer' ? $company : null, $phone]);
    $uid = (int)$pdo->lastInsertId();

    if ($role === 'driver') {
      $p2 = $pdo->prepare("INSERT INTO driver_profiles (user_id, license_number, vehicle_make, vehicle_model, vehicle_plate, capacity_lbs, availability, rating) VALUES (?, ?, ?, ?, ?, ?, 1, 5.0)");
      $p2->execute([
        $uid,
        trim($_POST['license_number'] ?? ''), trim($_POST['vehicle_make'] ?? ''), trim($_POST['vehicle_model'] ?? ''), trim($_POST['vehicle_plate'] ?? ''), (int)($_POST['capacity_lbs'] ?? 0)
      ]);
    }
    $pdo->commit();
    flash('success', 'Registration successful. Please log in.');
    clear_old(); header('Location: login.php'); exit;
  } catch (Exception $e) {
    $pdo->rollBack(); flash('error', 'Registration failed: '.$e->getMessage()); header('Location: register.php'); exit;
  }
}

require_once __DIR__ . '/partials/header.php';
?>
<section id="registration" class="py-8 bg-white section -mt-4">
  <h2 class="text-3xl font-bold text-center mb-12 text-dark">User Registration & Authentication</h2>
  <div class="grid md:grid-cols-3 gap-8">
    <div class="bg-gray-50 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <div class="bg-blue-100 p-3 rounded-full mr-4"><i class="fas fa-user text-primary text-xl"></i></div>
        <h3 class="text-xl font-semibold text-dark">Customer Registration</h3>
      </div>
      <ul class="space-y-3 text-gray-700">
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Personal and company information</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Shipping preferences and history</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Payment method setup</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Email verification process</span></li>
      </ul>
      <a href="#reg-form" data-role="customer" class="mt-6 w-full bg-primary text-white py-2 rounded-lg hover:bg-blue-700 transition block text-center select-role">Register as Customer</a>
    </div>
    <div class="bg-gray-50 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <div class="bg-green-100 p-3 rounded-full mr-4"><i class="fas fa-id-card text-secondary text-xl"></i></div>
        <h3 class="text-xl font-semibold text-dark">Driver Registration</h3>
      </div>
      <ul class="space-y-3 text-gray-700">
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Driver license verification</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Vehicle details and documents</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Background check authorization</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Availability preferences</span></li>
      </ul>
      <a href="#reg-form" data-role="driver" class="mt-6 w-full bg-secondary text-white py-2 rounded-lg hover:bg-green-700 transition block text-center select-role">Register as Driver</a>
    </div>
    <div class="bg-gray-50 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <div class="bg-yellow-100 p-3 rounded-full mr-4"><i class="fas fa-user-shield text-accent text-xl"></i></div>
        <h3 class="text-xl font-semibold text-dark">Admin Registration</h3>
      </div>
      <ul class="space-y-3 text-gray-700">
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Company verification</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Multi-factor authentication</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Role-based access control</span></li>
        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span>Approval workflow</span></li>
      </ul>
      <button type="button" onclick="alert('Please contact an existing admin to request access.');" class="mt-6 w-full bg-accent text-white py-2 rounded-lg hover:bg-yellow-700 transition">Request Admin Access</button>
    </div>
  </div>

  <!-- Auth features (static) -->
  <div class="mt-16 grid md:grid-cols-2 gap-8">
    <div class="bg-gray-50 rounded-xl p-6 shadow-md">
      <h3 class="text-xl font-semibold mb-4 text-dark">Login & Security</h3>
      <div class="space-y-4">
        <div class="flex items-start">
          <div class="bg-blue-100 p-2 rounded-full mr-4"><i class="fas fa-lock text-primary"></i></div>
          <div><h4 class="font-medium">Secure Authentication</h4><p class="text-gray-600 text-sm">Password hashing (bcrypt) & prepared statements.</p></div>
        </div>
        <div class="flex items-start">
          <div class="bg-green-100 p-2 rounded-full mr-4"><i class="fas fa-sync-alt text-secondary"></i></div>
          <div><h4 class="font-medium">Password Recovery</h4><p class="text-gray-600 text-sm">Add SMTP later for reset links.</p></div>
        </div>
        <div class="flex items-start">
          <div class="bg-yellow-100 p-2 rounded-full mr-4"><i class="fas fa-shield-alt text-accent"></i></div>
          <div><h4 class="font-medium">Account Protection</h4><p class="text-gray-600 text-sm">Session protection and access control by role.</p></div>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 rounded-xl p-6 shadow-md">
      <h3 class="text-xl font-semibold mb-4 text-dark">Profile Management</h3>
      <div class="space-y-4">
        <div class="flex items-start">
          <div class="bg-purple-100 p-2 rounded-full mr-4"><i class="fas fa-user-edit text-purple-600"></i></div>
          <div><h4 class="font-medium">Personal Information</h4><p class="text-gray-600 text-sm">Update contact details and preferences.</p></div>
        </div>
        <div class="flex items-start">
          <div class="bg-red-100 p-2 rounded-full mr-4"><i class="fas fa-credit-card text-red-500"></i></div>
          <div><h4 class="font-medium">Payment Methods</h4><p class="text-gray-600 text-sm">Add later via payment gateway.</p></div>
        </div>
        <div class="flex items-start">
          <div class="bg-indigo-100 p-2 rounded-full mr-4"><i class="fas fa-bell text-indigo-600"></i></div>
          <div><h4 class="font-medium">Notification Preferences</h4><p class="text-gray-600 text-sm">Email/SMS updates (future).</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Actual working form -->
<div id="reg-form" class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow mt-10">
  <h3 class="text-2xl font-semibold mb-4">Create your account</h3>
  <?php if ($msg = flash('error')): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded mb-3"><?= htmlspecialchars($msg); ?></div>
  <?php elseif ($msg = flash('success')): ?>
    <div class="bg-emerald-50 text-emerald-700 p-3 rounded mb-3"><?= htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <form method="post" class="space-y-4">
    <div class="grid md:grid-cols-3 gap-4">
      <label class="flex items-center space-x-2"><input type="radio" name="role" value="customer" checked> <span>Customer</span></label>
      <label class="flex items-center space-x-2"><input type="radio" name="role" value="driver"> <span>Driver</span></label>
      <label class="flex items-center space-x-2 text-slate-400"><input type="radio" disabled> <span>Admin (by request)</span></label>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div><label class="text-sm">Full Name</label><input type="text" name="name" value="<?= old('name'); ?>" required class="w-full border rounded px-3 py-2"></div>
      <div><label class="text-sm">Email</label><input type="email" name="email" value="<?= old('email'); ?>" required class="w-full border rounded px-3 py-2"></div>
      <div><label class="text-sm">Phone</label><input type="text" name="phone" value="<?= old('phone'); ?>" class="w-full border rounded px-3 py-2"></div>
      <div class="customer-only"><label class="text-sm">Company (Customers)</label><input type="text" name="company" value="<?= old('company'); ?>" class="w-full border rounded px-3 py-2"></div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div><label class="text-sm">Password</label><input type="password" name="password" required class="w-full border rounded px-3 py-2"></div>
      <div><label class="text-sm">Confirm Password</label><input type="password" name="password2" required class="w-full border rounded px-3 py-2"></div>
    </div>

    <div class="driver-fields hidden">
      <h4 class="font-semibold mt-4">Driver Details</h4>
      <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-sm">License Number</label><input type="text" name="license_number" class="w-full border rounded px-3 py-2"></div>
        <div><label class="text-sm">Capacity (lbs)</label><input type="number" name="capacity_lbs" class="w-full border rounded px-3 py-2"></div>
        <div><label class="text-sm">Vehicle Make</label><input type="text" name="vehicle_make" class="w-full border rounded px-3 py-2"></div>
        <div><label class="text-sm">Vehicle Model</label><input type="text" name="vehicle_model" class="w-full border rounded px-3 py-2"></div>
        <div><label class="text-sm">Plate</label><input type="text" name="vehicle_plate" class="w-full border rounded px-3 py-2"></div>
      </div>
    </div>

    <button class="w-full bg-primary text-white py-2 rounded hover:bg-blue-700">Create Account</button>
  </form>
</div>

<script>
  // Card buttons set role in form
  document.querySelectorAll('.select-role').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const role = btn.dataset.role;
      const radio = document.querySelector('input[name="role"][value="'+role+'"]');
      if (radio) radio.checked = true;
      // show/hide driver fields
      toggleDriverFields();
    });
  });
  // Toggle driver fields
  function toggleDriverFields() {
    const selected = document.querySelector('input[name="role"]:checked')?.value;
    document.querySelector('.driver-fields')?.classList.toggle('hidden', selected !== 'driver');
  }
  document.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', toggleDriverFields));
  toggleDriverFields();
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
