<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php'; // This was missing!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  set_old($_POST);
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    flash('error', 'Invalid credentials');
    header('Location: login.php');
    exit;
  }

  $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
  $upd->execute([$user['id']]);

  login_user($user);
  clear_old();
  redirect_to_dashboard($user['role']);
}

require_once __DIR__ . '/partials/header.php';
?>
<div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow">
  <h2 class="text-2xl font-semibold mb-4">Login</h2>
  <?php if ($msg = flash('error')): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded mb-3"><?= htmlspecialchars($msg); ?></div>
  <?php endif; ?>
  <form method="post" class="space-y-4">
    <div>
      <label class="text-sm text-slate-700">Email</label>
      <input type="email" name="email" value="<?= old('email'); ?>" required class="w-full border rounded px-3 py-2">
    </div>
    <div>
      <label class="text-sm text-slate-700">Password</label>
      <input type="password" name="password" required class="w-full border rounded px-3 py-2">
    </div>
    <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Sign In</button>
  </form>
  <p class="text-sm text-slate-600 mt-3">No account? <a class="text-blue-600" href="register.php">Register</a></p>
  
  <!-- Demo credentials for easy testing -->
  <div class="mt-4 p-3 bg-gray-50 rounded text-xs">
    <strong>Demo Accounts:</strong><br>
    Admin: admin@logitrack.com / secret<br>
    Customer: customer@acme.com / secret<br>
    Driver: driver@demo.com / secret
  </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>