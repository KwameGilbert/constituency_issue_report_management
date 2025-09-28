<section class="py-12 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-2xl font-semibold mb-6">Our Projects Map</h2>
        <div id="map" class="h-96 rounded-lg z-[0]" style="height: 384px;"></div>
    </div>
</section>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([6.2125, -2.4897], 12); // Updated coordinates for Sefwi
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);
// Later: fetch markers via AJAX endpoint
</script>