<?php
// partials/header.php (UI merged)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
$user = current_user();
function navLink($label, $href, $active = false) {
  return '<a href="'.$href.'" class="text-dark hover:text-primary px-3 py-2 text-sm font-medium'.($active?' text-primary':'').'">'.$label.'</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>LogiTrack</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#3b82f6',
            secondary: '#10b981',
            accent: '#f59e0b',
            dark: '#1e293b',
            light: '#f8fafc'
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #555; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .section { animation: fadeIn 0.5s ease-out forwards; }
    .map-placeholder { background: linear-gradient(135deg, #e0f2fe 25%, #f0f9ff 25%, #f0f9ff 50%, #e0f2fe 50%, #e0f2fe 75%, #f0f9ff 75%, #f0f9ff 100%); background-size: 40px 40px; }
  </style>
</head>
<body class="bg-gray-50 font-sans">

<!-- Navigation -->
<nav class="bg-white shadow-lg sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16">
      <div class="flex items-center">
        <div class="flex-shrink-0 flex items-center">
          <i class="fas fa-truck-moving text-primary text-2xl mr-2"></i>
          <a href="<?= base_path('/'); ?>" class="text-xl font-bold text-dark">LogiTrack</a>
        </div>
        <div class="hidden md:ml-10 md:flex md:space-x-8">
          <?= navLink('Registration', base_path('/register.php')); ?>
          <?php if ($user && $user['role']==='customer'): ?>
            <?= navLink('Booking', base_path('/customer/new_shipment.php')); ?>
          <?php else: ?>
            <?= navLink('Booking', base_path('/login.php')); ?>
          <?php endif; ?>
          <?php if ($user && $user['role']==='admin'): ?>
            <?= navLink('Admin', base_path('/admin/index.php')); ?>
          <?php else: ?>
            <?= navLink('Admin', base_path('/login.php')); ?>
          <?php endif; ?>
          <?php if ($user && $user['role']==='driver'): ?>
            <?= navLink('Driver', base_path('/driver/index.php')); ?>
          <?php else: ?>
            <?= navLink('Driver', base_path('/login.php')); ?>
          <?php endif; ?>
          <?php if ($user && $user['role']==='customer'): ?>
            <?= navLink('Customer', base_path('/customer/index.php')); ?>
            <?= navLink('Tracking', base_path('/customer/index.php')); ?>
          <?php else: ?>
            <?= navLink('Customer', base_path('/login.php')); ?>
            <?= navLink('Tracking', base_path('/login.php')); ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="hidden md:flex items-center space-x-4">
        <?php if ($user): ?>
          <span class="text-sm text-slate-600"><?= htmlspecialchars($user['name']); ?> (<?= $user['role']; ?>)</span>
          <a href="<?= base_path('/logout.php'); ?>" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Logout</a>
        <?php else: ?>
          <a href="<?= base_path('/login.php'); ?>" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Login</a>
          <a href="<?= base_path('/register.php'); ?>" class="border border-primary text-primary px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-50">Register</a>
        <?php endif; ?>
      </div>
      <div class="-mr-2 flex items-center md:hidden">
        <button type="button" id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
          <span class="sr-only">Open main menu</span>
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg">
    <div class="pt-2 pb-3 space-y-1">
      <a href="<?= base_path('/register.php'); ?>" class="block px-3 py-2 text-base font-medium text-dark hover:text-primary hover:bg-blue-50">Registration</a>
      <a href="<?= $user && $user['role']==='customer' ? base_path('/customer/new_shipment.php') : base_path('/login.php'); ?>" class="block px-3 py-2 text-base font-medium text-dark hover:text-primary hover:bg-blue-50">Booking</a>
      <a href="<?= $user && $user['role']==='admin' ? base_path('/admin/index.php') : base_path('/login.php'); ?>" class="block px-3 py-2 text-base font-medium text-dark hover:text-primary hover:bg-blue-50">Admin</a>
      <a href="<?= $user && $user['role']==='driver' ? base_path('/driver/index.php') : base_path('/login.php'); ?>" class="block px-3 py-2 text-base font-medium text-dark hover:text-primary hover:bg-blue-50">Driver</a>
      <a href="<?= $user && $user['role']==='customer' ? base_path('/customer/index.php') : base_path('/login.php'); ?>" class="block px-3 py-2 text-base font-medium text-dark hover:text-primary hover:bg-blue-50">Customer</a>
      <a href="<?= $user && $user['role']==='customer' ? base_path('/customer/index.php') : base_path('/login.php'); ?>" class="block px-3 py-2 text-base font-medium text-dark hover:text-primary hover:bg-blue-50">Tracking</a>
      <div class="px-3 py-2">
        <?php if ($user): ?>
          <a href="<?= base_path('/logout.php'); ?>" class="w-full bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 block text-center">Logout</a>
        <?php else: ?>
          <a href="<?= base_path('/login.php'); ?>" class="w-full bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 block text-center">Login</a>
          <a href="<?= base_path('/register.php'); ?>" class="w-full border border-primary text-primary px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-50 block text-center mt-2">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mobile-menu-button');
    const menu = document.getElementById('mobile-menu');
    if (btn && menu) btn.addEventListener('click', () => menu.classList.toggle('hidden'));
  });
</script>
