<?php
/**
 * Map View - Plot GPS coordinates
 */
$pageTitle = 'Location Map';

require_once '../../includes/header.php';

$db = Database::getInstance();

// Get coordinates from query string or installation
$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);
$installationId = intval($_GET['id'] ?? 0);

if ($installationId) {
    $installation = $db->fetch(
        "SELECT ir.*, ia.area_name, ia.city, u.full_name as installer_name
         FROM installation_reports ir
         JOIN assignments a ON ir.assignment_id = a.id
         JOIN installation_areas ia ON a.area_id = ia.id
         JOIN users u ON ir.installer_id = u.id
         WHERE ir.id = ?",
        [$installationId]
    );
    
    if ($installation) {
        $lat = $installation['latitude'];
        $lng = $installation['longitude'];
    }
}

if (!$lat || !$lng) {
    redirect($_SERVER['HTTP_REFERER'] ?? APP_URL, 'Invalid coordinates', 'danger');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-geo-alt me-2"></i>Location Map
    </h1>
    <div class="con">
        <button class="btn btn-danger px-4" onclick="closeFullMap()">
            Close
        </button>          
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>      
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary">
        <div class="d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-map me-2"></i>
                <?php if (isset($installation)): ?>
                <?php echo clean($installation['area_name']); ?> - <?php echo clean($installation['city']); ?>
                <?php else: ?>
                GPS Location
                <?php endif; ?>
            </span>
            <span class="badge bg-light text-dark">
                <?php echo number_format($lat, 6); ?>°, <?php echo number_format($lng, 6); ?>°
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="map" class="map-container" style="height: 500px;"></div>
    </div>
    <?php if (isset($installation)): ?>
    <div class="card-footer">
        <div class="row text-center">
            <div class="col-md-3">
                <small class="text-muted">Report Code</small>
                <div class="fw-bold"><?php echo clean($installation['report_code']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Installation Date</small>
                <div class="fw-bold"><?php echo formatDate($installation['installation_date']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Installer</small>
                <div class="fw-bold"><?php echo clean($installation['installer_name']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Status</small>
                <div><?php echo statusBadge($installation['status']); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
    let popupContent = '<strong>Location</strong><br>';
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
