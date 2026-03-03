<?php
/**
 * Inspection Map View
 */
$pageTitle = 'Location Map';

require_once '../../includes/header.php';

$db = Database::getInstance();

// Get coordinates from query string
$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);

if (!$lat || !$lng) {
    redirect('index.php', 'Invalid coordinates', 'danger');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-geo-alt me-2"></i>Inspection Location
    </h1>
    <a href="javascript:history.back()" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-map me-2"></i>GPS Location</span>
            <span class="badge bg-light text-dark">
                <?php echo number_format($lat, 6); ?>°, <?php echo number_format($lng, 6); ?>°
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="map" class="map-container" style="height: 500px;"></div>
    </div>
</div>

<?php 
$extraScripts = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lat = {$lat};
    const lng = {$lng};
    
    // Initialize map
    const map = L.map('map').setView([lat, lng], 16);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add marker
    const marker = L.marker([lat, lng]).addTo(map);
    
    // Popup content
    let popupContent = '<strong>Inspection Location</strong><br>';
    popupContent += 'Lat: ' + lat.toFixed(6) + '<br>';
    popupContent += 'Lng: ' + lng.toFixed(6);
    
    marker.bindPopup(popupContent).openPopup();
    
    // Add circle for accuracy
    L.circle([lat, lng], {
        color: '#dc3545',
        fillColor: '#dc3545',
        fillOpacity: 0.1,
        radius: 50
    }).addTo(map);
});
</script>
SCRIPT;

require_once '../../includes/footer.php'; 
?>