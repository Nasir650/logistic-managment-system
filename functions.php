<?php
// functions.php
require_once __DIR__ . '/config.php';

function old(string $key): string {
  return htmlspecialchars($_SESSION['old'][$key] ?? '');
}
function set_old(array $input): void {
  $_SESSION['old'] = $input;
}
function clear_old(): void {
  unset($_SESSION['old']);
}

function flash(string $key, ?string $val = null): ?string {
  if ($val !== null) {
    $_SESSION['flash'][$key] = $val;
    return null;
  }
  $v = $_SESSION['flash'][$key] ?? null;
  unset($_SESSION['flash'][$key]);
  return $v;
}

function generate_order_number(PDO $pdo): string {
  $year = date('Y');
  do {
    $num = 'ORD-' . $year . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("SELECT id FROM shipments WHERE order_number = ?");
    $stmt->execute([$num]);
    $exists = $stmt->fetch();
  } while ($exists);
  return $num;
}

function redirect_to_dashboard(string $role): void {
  switch ($role) {
    case 'admin':
      header('Location: ' . base_path('/admin/index.php')); break;
    case 'driver':
      header('Location: ' . base_path('/driver/index.php')); break;
    case 'customer':
    default:
      header('Location: ' . base_path('/customer/index.php')); break;
  }
  exit;
}

function price_estimate(int $weight_lbs, string $container_size, bool $hazmat = false): array {
  $base = 150.00;
  switch ($container_size) {
    case 'Small': $base = 150; break;
    case 'Medium': $base = 250; break;
    case 'Large': $base = 400; break;
    case 'Extra Large': $base = 600; break;
    default: $base = 200; break;
  }
  $weight_surcharge = max(0, floor($weight_lbs / 500) * 25);
  $hazmat_fee = $hazmat ? 50.0 : 0.0;
  $total = $base + $weight_surcharge + $hazmat_fee;
  return [
    'base' => $base,
    'distance' => 0.0, // not computed here
    'surcharge' => $weight_surcharge + $hazmat_fee,
    'total' => $total
  ];
}
