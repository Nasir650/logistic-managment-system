<?php require_once __DIR__ . '/partials/header.php'; ?>

<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary to-blue-600 text-white py-20 rounded-xl">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between">
      <div class="md:w-1/2">
        <h1 class="text-4xl font-bold mb-4">Streamline Your Logistics Operations</h1>
        <p class="text-xl mb-8">Comprehensive solution for cargo transportation management with real-time tracking and multi-role support.</p>
        <div class="flex space-x-4">
          <a href="<?= base_path('/register.php'); ?>" class="bg-white text-primary px-6 py-3 rounded-lg font-medium hover:bg-gray-100">Get Started</a>
          <a href="<?= base_path('/login.php'); ?>" class="border border-white text-white px-6 py-3 rounded-lg font-medium hover:bg-white hover:text-primary">Login</a>
        </div>
      </div>
      <div class="md:w-1/2 mt-10 md:mt-0">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
          <div class="p-4 bg-gray-100 flex justify-between items-center">
            <div class="flex space-x-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <div class="text-sm text-gray-600">Live Tracking Preview</div>
          </div>
          <div id="home-map" class="h-64 w-full"></div>
          <div class="p-4 bg-gray-50">
            <div class="flex justify-between items-center">
              <div>
                <div class="text-sm text-gray-500">Current Delivery</div>
                <div class="font-medium">Industrial Equipment</div>
              </div>
              <div class="text-primary font-bold">ETA: 2h 15m</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Simple Leaflet map placeholder (no data on home)
  const hm = L.map('home-map').setView([39.5, -98.35], 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap' }).addTo(hm);
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
