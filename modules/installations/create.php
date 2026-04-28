<?php
/**
 * Create Installation Report
 * User 2 submits installation with before/after photos and GPS
 */
$pageTitle = 'Submit Installation Report';
$breadcrumbs = [
    ['title' => 'Installations', 'url' => 'index.php'],
    ['title' => 'New Report']
];

require_once '../../includes/header.php';

$auth->requireRole('user_2');

$db = Database::getInstance();
$userId = $auth->userId();
$errors = [];

// Get assignment ID from query or form
$assignmentId = intval($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);

// Get user's pending/in-progress assignments
$myAssignments = $db->fetchAll(
    "SELECT a.*, ia.area_name, ia.city, ia.address
     FROM assignments a
     JOIN installation_areas ia ON a.area_id = ia.id
     WHERE a.assigned_to = ? AND a.status IN ('pending', 'in_progress')
     ORDER BY a.due_date ASC",
    [$userId]
);

// Get all store type
$storeType = $db->fetchAll("SELECT * FROM installation_store_type ORDER BY name_type ASC");

// If assignment selected, get its items
$assignmentItems = [];
$selectedAssignment = null;

if ($assignmentId) {
    $selectedAssignment = $db->fetch(
        "SELECT a.*, ia.area_name, ia.city, ia.address, ia.latitude, ia.longitude
         FROM assignments a
         JOIN installation_areas ia ON a.area_id = ia.id
         WHERE a.id = ? AND a.assigned_to = ? AND a.status IN ('pending', 'in_progress')",
        [$assignmentId, $userId]
    );
    
    if ($selectedAssignment) {
        $assignmentItems = $db->fetchAll(
            "SELECT ai.*, i.item_code, i.item_name, i.unit
             FROM assignment_items ai
             JOIN inventory_items i ON ai.item_id = i.id
             WHERE ai.assignment_id = ? AND ai.status != 'completed'",
            [$assignmentId]
        );
    }
}

$location = null;
$municipalityId = null;
$barangays = [];

if ($selectedAssignment) {

    $municipality = $db->fetch(
        "SELECT b.municipality_id, b.municipality_name, c.province_name, d.region_name
         FROM table_municipality b
         JOIN table_province c ON b.province_id = c.province_id
         JOIN table_region d ON c.region_id = d.region_id
         WHERE LOWER(b.municipality_name) = LOWER(?)",
        [trim($selectedAssignment['area_name'])]
    );

    if ($municipality) {
        $location = [
            'province_name' => $municipality['province_name'],
            'region_name' => $municipality['region_name']
        ];

        $municipalityId = $municipality['municipality_id'];

        $barangays = $db->fetchAll(
            "SELECT barangay_name
             FROM table_barangay
             WHERE municipality_id = ?
             ORDER BY barangay_name ASC",
            [$municipalityId]
        );
    }
}


function old($key) {
    return htmlspecialchars($_POST[$key] ?? '');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedAssignment) {
    $StoreType = $_POST['store_type'] ?? '';
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $mnl_latitude = floatval($_POST['mnl_latitude'] ?? 0);
    $mnl_longitude = floatval($_POST['mnl_longitude'] ?? 0);
    $installationDate = $_POST['installation_date'] ?? date('Y-m-d');
    $overallRemarks = clean($_POST['overall_remarks'] ?? '');

    $itemData = $_POST['item_data'] ?? [];

    // Store Details Data
    $storeData = [
        'agency_store_code' => clean($_POST['agency_store_code'] ?? ''),
        'date_of_visit' => $_POST['date_of_visit'] ?? date('Y-m-d'),
        'store_status' => $_POST['store_status'] ?? '',
        'reschedule_date' => !empty($_POST['reschedule_date']) ? $_POST['reschedule_date'] : null,
        'status_remarks' => clean($_POST['status_remarks'] ?? ''),
        'store_name_before' => clean($_POST['store_name_before'] ?? ''),
        'store_name_after' => clean($_POST['store_name_after'] ?? ''),
        'owner_name' => clean($_POST['owner_name'] ?? ''),
        'contact_number' => clean($_POST['contact_number'] ?? ''),
        'area_length' => !empty($_POST['area_length']) ? floatval($_POST['area_length']) : null,
        'area_width' => !empty($_POST['area_width']) ? floatval($_POST['area_width']) : null,
        'additional_area_sqm' => !empty($_POST['additional_area_sqm']) ? floatval($_POST['additional_area_sqm']) : null,
    ];

    // Address Data
    $addressData = [
        'house_no' => clean($_POST['house_no'] ?? ''),
        'block' => clean($_POST['block'] ?? ''),
        'lot' => clean($_POST['lot'] ?? ''),
        'street_name' => clean($_POST['street_name'] ?? ''),
        'purok' => clean($_POST['purok'] ?? ''),
        'sitio' => clean($_POST['sitio'] ?? ''),
        'zone' => clean($_POST['zone'] ?? ''),
        'phase' => clean($_POST['phase'] ?? ''),
        'road' => clean($_POST['road'] ?? ''),
        'barangay' => clean($_POST['barangay'] ?? ''),
        'city' => clean($_POST['city'] ?? ''),
        'province' => clean($_POST['province'] ?? ''),
    ];

    // Validation
    if ($latitude == 0 || $longitude == 0) {
        $errors['gps'] = 'GPS location is required. Please enable location services.';
    }

    if (empty($itemData)) {
        $errors['items'] = 'Please provide installation data for at least one item';
    }

    // Validate store details
    if (empty($storeData['store_status'])) {
        $errors['store'] = 'Store status is required';
    }
    
    if (empty($StoreType)) {
        $errors['store'] = 'Store type is required';
    }

    if (empty($storeData['owner_name'])) {
        $errors['store'] = 'Owner name is required';
    }

    if (empty($storeData['contact_number'])) {
        $errors['store'] = 'Contact number is required';
    }

    // Validate address
    if (empty($addressData['barangay']) || empty($addressData['city']) || empty($addressData['province'])) {
        $errors['store'] = 'Barangay, City, and Province are required';
    }
    
    // Validate each item's data
    foreach ($itemData as $aiId => $data) {
        $qty = intval($data['quantity'] ?? 0);
        if ($qty <= 0) {
            $errors['items'] = 'Quantity must be greater than 0 for all items';
            break;
        }

        // Check files - now supporting multiple photos
        $beforePhotosKey = 'item_before_' . $aiId;
        $afterPhotosKey = 'item_after_' . $aiId;

        if (empty($_FILES[$beforePhotosKey]['name'][0]) || empty($_FILES[$afterPhotosKey]['name'][0])) {
            $errors['photos'] = 'Before and after photos are required for all items';
            break;
        }
    }

    if ($errors === []) {
        try {
            $db->beginTransaction();
            // Create installation report
            $reportCode = generateCode('INS-');
            $reportId = $db->insert('installation_reports', [
                'report_code' => $reportCode,
                'assignment_id' => $assignmentId,
                'installer_id' => $userId,
                'store_type' => $StoreType,
                'installation_date' => $installationDate,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'mnl_latitude' => $mnl_latitude,
                'mnl_longitude' => $mnl_longitude,
                'location_address' => $selectedAssignment['address'],
                'overall_remarks' => $overallRemarks,
                'status' => 'submitted'
            ]);

            // Insert store details
            $storeData['report_id'] = $reportId;
            $db->insert('installation_store_details', $storeData);

            // Insert detailed address
            $addressData['report_id'] = $reportId;
            $db->insert('installation_detailed_addresses', $addressData);

            // Process overall installation photos (before & after & store)
            $overallPhotoTypes = [
                'overall_before_photos' => 'before', 
                'overall_after_photos' => 'after', 
                'overall_store_photos' => 'StoreImage'
            ];

            foreach ($overallPhotoTypes as $fileKey => $photoType) {
                if (isset($_FILES[$fileKey]) && !empty($_FILES[$fileKey]['name'][0])) {
                    $fileCount = count($_FILES[$fileKey]['name']);

                    for ($i = 0; $i < $fileCount; $i++) {
                        // Prepare individual file array for uploadImage function
                        $file = [
                            'name' => $_FILES[$fileKey]['name'][$i],
                            'type' => $_FILES[$fileKey]['type'][$i],
                            'tmp_name' => $_FILES[$fileKey]['tmp_name'][$i],
                            'error' => $_FILES[$fileKey]['error'][$i],
                            'size' => $_FILES[$fileKey]['size'][$i]
                        ];

                        // Upload with GPS watermark
                        $photoPath = $photoType === 'before' ? BEFORE_PHOTO_PATH : ($photoType === 'after' ? AFTER_PHOTO_PATH : STORE_PHOTO_PATH);
                        $photoResult = uploadImage($file, $photoPath, $latitude, $longitude);

                        if (!$photoResult['success']) {
                            throw new Exception("Failed to upload overall {$photoType} photo: " . $photoResult['message']);
                        }

                        // Insert into installation_report_photos table
                        try {
                            $insertResult = $db->insert('installation_report_photos', [
                                'report_id' => $reportId,
                                'photo_type' => $photoType,
                                'photo_filename' => $photoResult['filename'],
                                'display_order' => $i
                            ]);
                            
                            if (!$insertResult) {
                                error_log("Failed to insert {$photoType} photo for report {$reportId}: Insert returned false");
                                throw new Exception("Failed to save {$photoType} photo to database");
                            }
                        } catch (Exception $e) {
                            error_log("Error inserting {$photoType} photo: " . $e->getMessage());
                            throw $e;
                        }
                    }
                }
            }

            // Process each item
            foreach ($itemData as $aiId => $data) {
                $qty = intval($data['quantity']);
                $remarks = clean($data['remarks'] ?? '');

                // Process multiple before/after photos for this item
                $beforePhotosKey = 'item_before_' . $aiId;
                $afterPhotosKey = 'item_after_' . $aiId;

                $firstBeforePhoto = null;
                $firstAfterPhoto = null;

                // Temporary storage for uploaded filenames
                $beforePhotoFilenames = [];
                $afterPhotoFilenames = [];

                // Upload before photos with GPS watermark
                if (isset($_FILES[$beforePhotosKey]) && !empty($_FILES[$beforePhotosKey]['name'][0])) {
                    $fileCount = count($_FILES[$beforePhotosKey]['name']);

                    for ($i = 0; $i < $fileCount; $i++) {
                        $file = [
                            'name' => $_FILES[$beforePhotosKey]['name'][$i],
                            'type' => $_FILES[$beforePhotosKey]['type'][$i],
                            'tmp_name' => $_FILES[$beforePhotosKey]['tmp_name'][$i],
                            'error' => $_FILES[$beforePhotosKey]['error'][$i],
                            'size' => $_FILES[$beforePhotosKey]['size'][$i]
                        ];

                        $beforeResult = uploadImage($file, BEFORE_PHOTO_PATH, $latitude, $longitude);

                        if (!$beforeResult['success']) {
                            throw new Exception("Failed to upload before photo for item {$aiId}: " . $beforeResult['message']);
                        }

                        $beforePhotoFilenames[] = $beforeResult['filename'];

                        // Keep first photo for backward compatibility
                        if ($i === 0) {
                            $firstBeforePhoto = $beforeResult['filename'];
                        }
                    }
                }

                // Upload after photos with GPS watermark
                if (isset($_FILES[$afterPhotosKey]) && !empty($_FILES[$afterPhotosKey]['name'][0])) {
                    $fileCount = count($_FILES[$afterPhotosKey]['name']);

                    for ($i = 0; $i < $fileCount; $i++) {
                        $file = [
                            'name' => $_FILES[$afterPhotosKey]['name'][$i],
                            'type' => $_FILES[$afterPhotosKey]['type'][$i],
                            'tmp_name' => $_FILES[$afterPhotosKey]['tmp_name'][$i],
                            'error' => $_FILES[$afterPhotosKey]['error'][$i],
                            'size' => $_FILES[$afterPhotosKey]['size'][$i]
                        ];

                        $afterResult = uploadImage($file, AFTER_PHOTO_PATH, $latitude, $longitude);

                        if (!$afterResult['success']) {
                            throw new Exception("Failed to upload after photo for item {$aiId}: " . $afterResult['message']);
                        }

                        $afterPhotoFilenames[] = $afterResult['filename'];

                        // Keep first photo for backward compatibility
                        if ($i === 0) {
                            $firstAfterPhoto = $afterResult['filename'];
                        }
                    }
                }

                // Insert report item (using first photos for backward compatibility)
                $reportItemId = $db->insert('installation_report_items', [
                    'report_id' => $reportId,
                    'assignment_item_id' => $aiId,
                    'quantity_installed' => $qty,
                    'before_photo' => $firstBeforePhoto,
                    'after_photo' => $firstAfterPhoto,
                    'remarks' => $remarks
                ]);

                // Insert all before photos into installation_item_photos table
                foreach ($beforePhotoFilenames as $i => $filename) {
                    $db->insert('installation_item_photos', [
                        'report_item_id' => $reportItemId,
                        'photo_type' => 'before',
                        'photo_filename' => $filename,
                        'display_order' => $i
                    ]);
                }

                // Insert all after photos into installation_item_photos table
                foreach ($afterPhotoFilenames as $i => $filename) {
                    $db->insert('installation_item_photos', [
                        'report_item_id' => $reportItemId,
                        'photo_type' => 'after',
                        'photo_filename' => $filename,
                        'display_order' => $i
                    ]);
                }
                
                // Update assignment item
                $db->query(
                    "UPDATE assignment_items SET quantity_installed = quantity_installed + ?, 
                     status = CASE WHEN quantity_installed + ? >= quantity_assigned THEN 'completed' ELSE 'partial' END
                     WHERE id = ?",
                    [$qty, $qty, $aiId]
                );
                
                // Get item_id for inventory update
                $assignItem = $db->fetch("SELECT item_id FROM assignment_items WHERE id = ?", [$aiId]);
                
                // Update inventory - move from reserved to installed
                $db->query(
                    "UPDATE inventory_items SET quantity_reserved = quantity_reserved - ?, quantity_installed = quantity_installed + ? WHERE id = ?",
                    [$qty, $qty, $assignItem['item_id']]
                );
                
                // Log transaction
                $db->insert('inventory_transactions', [
                    'item_id' => $assignItem['item_id'],
                    'transaction_type' => 'stock_out',
                    'quantity' => $qty,
                    'reference_type' => 'installation',
                    'reference_id' => $reportId,
                    'notes' => "Installed via report {$reportCode}",
                    'created_by' => $userId
                ]);
            }
            
            // Update assignment status
            $db->update('assignments', ['status' => 'in_progress'], 'id = ?', [$assignmentId]);
            
            // Check if all items completed
            $remainingItems = $db->count('assignment_items', "assignment_id = ? AND status != 'completed'", [$assignmentId]);
            if ($remainingItems == 0) {
                $db->update('assignments', ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')], 'id = ?', [$assignmentId]);
            }
            
            // Create inspection schedules (6 months)
            for ($month = 1; $month <= INSPECTION_MONTHS; $month++) {
                $scheduledDate = date('Y-m-d', strtotime("+{$month} months", strtotime($installationDate)));
                $db->insert('inspection_schedules', [
                    'installation_report_id' => $reportId,
                    'month_number' => $month,
                    'scheduled_date' => $scheduledDate,
                    'status' => 'pending'
                ]);
            }
            
            // Notify User 1 (Manager)
            $managers = $db->fetchAll("SELECT id FROM users WHERE role = 'user_1' AND status = 'active'");
            foreach ($managers as $manager) {
                createNotification(
                    $manager['id'],
                    'New Installation Report',
                    "Installation report {$reportCode} has been submitted for review.",
                    'info',
                    APP_URL . "/modules/installations/view.php?id={$reportId}"
                );
            }
            
            // Log activity
            $auth->logActivity($userId, 'submitted_installation', 'installations', 'installation_reports', $reportId);
            
            $db->commit();
            redirect('index.php', 'Installation report submitted successfully!', 'success');
            
        } catch (Throwable $e) {
            try {
                if ($db->getConnection()->inTransaction()) {
                    $db->rollback();
                }
            } catch (Throwable $rollbackError) {
                // ignore rollback errors; we'll log the original exception below
            }

            error_log('Installation submit failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $errors['general'] = 'Failed to submit report: ' . $e->getMessage();
        }
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">
        <i class="bi bi-camera me-2"></i>Submit Installation Report
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?php echo $errors['general']; ?></div>
<?php endif; ?>

<?php if (empty($myAssignments)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    You don't have any pending assignments. Please wait for an assignment to be made.
</div>
<?php elseif (!$selectedAssignment): ?>
<!-- Assignment Selection -->
<div class="card">
    <div class="card-header bg-primary">
        <span class="d-flex align-text-center">
        <span class="bi bi-clipboard-check me-2"></span>Select Assignment
    </div>
    <div class="card-body bg-danger-subtle">
        <p class="text-muted">Choose an assignment to submit installation report:</p>
        <div class="row g-3">
            <?php foreach ($myAssignments as $assign): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-2">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo clean($assign['assignment_code']); ?></h5>
                        <p class="mb-1"><i class="bi bi-geo-alt text-danger me-1"></i><?php echo clean($assign['area_name']); ?></p>
                        <p class="text-muted small mb-2"><?php echo clean($assign['city']); ?></p>
                        <p class="mb-1">
                            <span class="badge bg-<?php echo strtotime($assign['due_date']) < time() ? 'danger' : 'info'; ?>">
                                Due: <?php echo formatDate($assign['due_date']); ?>
                            </span>
                        </p>
                        <?php echo priorityBadge($assign['priority']); ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="?assignment_id=<?php echo $assign['id']; ?>" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right me-1"></i>Select
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Installation Form -->
<form method="POST" action="" enctype="multipart/form-data" id="installationForm">
    <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
    <input type="hidden" name="latitude" id="latitude" value="">
    <input type="hidden" name="longitude" id="longitude" value="">
    <div id="gps-error" class="alert alert-danger py-2" style="display: none;"></div>
    <div class="row">
        <!-- Assignment Info & GPS -->
        <div class="col-lg-4 mb-4">
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class= "d-flex align-text-center">
                    <span class="bi bi-info-circle me-2"></span>Assignment Details
</span>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Code:</strong> <?php echo clean($selectedAssignment['assignment_code']); ?></p>
                    <p class="mb-2"><strong>Area:</strong> <?php echo clean($selectedAssignment['area_name']); ?></p>
                    <p class="mb-2"><strong>City:</strong> <?php echo clean($selectedAssignment['city']); ?></p>
                    <p class="mb-0"><strong>Address:</strong> <?php echo clean($selectedAssignment['address']) ?: '-'; ?></p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-geo-alt me-2"></span>GPS Location
</span>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['gps'])): ?>
                        <div class="alert alert-danger py-2"><?php echo $errors['gps']; ?></div>
                    <?php endif; ?>
                    
                    <div id="gps-status" class="text-center py-3">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="mb-0">Getting your location...</p>
                    </div>
                    
                    <div id="gps-result" style="display: none;">
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <small class="text-muted">Latitude</small>
                                <h5 id="lat-display" class="mb-0">-</h5>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Longitude</small>
                                <h5 id="lng-display" class="mb-0">-</h5>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="refreshGps">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Location
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-geo-alt me-2"></span>Manual Coordinates (Optional)
                </div>
                <div class="card-body">
                    <?php if (isset($errors['mnl_gps'])): ?>
                        <div class="alert alert-danger py-2"><?php echo $errors['mnl_gps']; ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="mnl_latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="mnl_latitude" name="mnl_latitude" value="<?= old('mnl_latitude'); ?>" placeholder="Enter latitude">
                    </div>
                    <div class="mb-3">
                        <label for="mnl_longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="mnl_longitude" name="mnl_longitude"  value="<?= old('mnl_longitude'); ?>" placeholder="Enter longitude">
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-calendar me-2"></span>Report Details
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="installation_date" class="form-label">Installation Date</label>
                        <input type="date" class="form-control" id="installation_date" name="installation_date"
                               value="<?php echo date('Y-m-d'); ?> <?= old('installation_date'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-0">
                        <label for="overall_remarks" class="form-label">Overall Remarks</label>
                        <textarea class="form-control" id="overall_remarks" name="overall_remarks" rows="3"><?= old('overall_remarks') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Details & Address -->
        <div class="col-lg-8 mb-4">
            <!-- Store Information -->
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-shop me-2"></span>Store Information
                    </span> 
                </div>
                <div class="card-body">
                    <?php if (isset($errors['store'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['store']; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="agency_store_code" class="form-label">Agency Store Code</label>
                            <input type="text" class="form-control" id="agency_store_code" name="agency_store_code"
                                    value="<?= old('agency_store_code'); ?>"  placeholder="e.g., AG-MKT-001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_of_visit" class="form-label">Date of Visit <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_visit" name="date_of_visit"
                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="store_status" class="form-label">Store Status Upon Visit <span class="text-danger">*</span></label>
                            <select class="form-select" id="store_status" name="store_status" required>
                                <option value="">-- Select Status --</option>
                                <option value="agree_to_dressup" <?= old('store_status') == 'agree_to_dressup' ? 'selected' : '' ?>>Agree to Dress-Up</option>
                                <option value="store_closed" <?= old('store_status') == 'store_closed' ? 'selected' : '' ?>>Store Closed</option>
                                <option value="refused_to_dressup" <?= old('store_status') == 'refused_to_dressup' ? 'selected' : '' ?>>Refused to Dress-Up</option>
                                <option value="reschedule" <?= old('store_status') == 'reschedule' ? 'selected' : '' ?>>Reschedule</option>
                                <option value="others" <?= old('store_status') == 'others' ? 'selected' : '' ?>>Others</option>
                            </select>
                        </div>
                    
                        <div class="col-md-6 mb-3">
                            <label for="store_type" class="form-label">Store Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="store_type" name="store_type" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($storeType as $type): ?>
                                    <option value="<?= $type['name_type'] ?>" <?= old('store_type') == $type['name_type'] ? 'selected' : '' ?>>
                                        <?= $type['name_type'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-camera me-1"></i>Upload Store Image
                                </label>
                                <div class="multi-photo-upload" data-type="overall_store">
                                    <input type="file" class="d-none" id="overall_store_input"
                                        name="overall_store_photos[]" accept="image/*" capture="environment" multiple>
                                    <div class="upload-placeholder" onclick="document.getElementById('overall_store_input').click()">
                                        <i class="bi bi-cloud-upload display-6 text-muted"></i>
                                        <p class="mb-1">Click to upload or take photos</p>
                                        <small class="text-muted">You can select multiple photos at once</small>
                                    </div>
                                    <div class="photo-preview-grid" id="overall_store_preview"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="reschedule_section" style="display: none;">
                        <div class="col-md-12 mb-3">
                            <label for="reschedule_date" class="form-label">Reschedule Date & Time</label>
                            <input type="datetime-local" class="form-control" id="reschedule_date" value="<?= old('reschedule_date') ?>" name="reschedule_date">
                        </div>
                    </div>

                    <div class="row" id="status_remarks_section" style="display: none;">
                        <div class="col-md-12 mb-3">
                            <label for="status_remarks" class="form-label">Status Remarks <small class="text-muted">(Max 500 characters)</small></label>
                            <textarea class="form-control" id="status_remarks" name="status_remarks"
                                      rows="3" maxlength="500" placeholder="Provide additional details..."><?= old('status_remarks') ?></textarea>
                            <small class="text-muted"><span id="char_count">0</span>/500 characters</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="store_name_before" class="form-label">Store Name Before</label>
                            <input type="text" class="form-control" id="store_name_before" name="store_name_before"
                                   value="<?= old('store_name_before') ?>" placeholder="Original store name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="store_name_after" class="form-label">Store Name After (POS Name in Signage)</label>
                            <input type="text" class="form-control" id="store_name_after" name="store_name_after"
                                   value="<?= old('store_name_after') ?>" placeholder="New store name on signage">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="owner_name" class="form-label">Owner's Complete Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="owner_name" name="owner_name"
                                   value="<?= old('owner_name') ?>" placeholder="e.g., Juan Dela Cruz" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number"
                                   value="<?= old('contact_number') ?>" placeholder="0000-000-0000" pattern="[0-9]{4}-[0-9]{3}-[0-9]{4}" required>
                            <small class="text-muted">Format: 0912-345-6789</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store Address -->
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-geo-alt me-2"></span>Store Address (Complete Address)
                                </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="house_no" class="form-label">House No.</label>
                            <input type="text" class="form-control" id="house_no" name="house_no" value="<?= old('house_no') ?>" placeholder="e.g., 123">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="block" class="form-label">Block</label>
                            <input type="text" class="form-control" id="block" name="block" value="<?= old('block') ?>" placeholder="e.g., B10">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="lot" class="form-label">Lot</label>
                            <input type="text" class="form-control" id="lot" name="lot" value="<?= old('lot') ?>" placeholder="e.g., L5">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="street_name" class="form-label">Street Name</label>
                            <input type="text" class="form-control" id="street_name" name="street_name" value="<?= old('street_name') ?>" placeholder="e.g., Mabini St.">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="purok" class="form-label">Purok</label>
                            <input type="text" class="form-control" id="purok" name="purok" value="<?= old('purok') ?>" placeholder="e.g., Purok 3">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="sitio" class="form-label">Sitio</label>
                            <input type="text" class="form-control" id="sitio" name="sitio" value="<?= old('sitio') ?>" placeholder="e.g., Sitio Luntian">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="zone" class="form-label">Zone</label>
                            <input type="text" class="form-control" id="zone" name="zone" value="<?= old('zone') ?>" placeholder="e.g., Zone 1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="phase" class="form-label">Phase</label>
                            <input type="text" class="form-control" id="phase" name="phase" value="<?= old('phase') ?>" placeholder="e.g., Phase 2">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="road" class="form-label">Road</label>
                            <input type="text" class="form-control" id="road" name="road" value="<?= old('road') ?>" placeholder="e.g., National Highway">
                        </div>
                    </div>

                    <div class="row">

                            <!-- Barangay (manual) -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Barangay</label>
                        <select class="form-select" id="barangay" name="barangay" required>
                            <option value="">Select Barangay</option>
                            <?php foreach ($barangays as $brgy): ?>
                                <option value="<?= clean($brgy['barangay_name']); ?>">
                                    <?= clean($brgy['barangay_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                            <!-- City (already selected) -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" value="<?php echo clean($selectedAssignment['area_name']); ?>" id="city" name="city" readonly>
                            </div>

                            <!-- Province (auto-filled) -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Province</label>
                                <input type="text" class="form-control" id="province" name="province" value="<?php echo clean($location['province_name'] ?? ''); ?>" readonly>
                            </div>

                            <!-- Region (auto-filled) -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Region</label>
                                <input type="text"
                                    class="form-control" id="region" name="region" value="<?php echo clean($location['region_name'] ?? ''); ?>" readonly>
                            </div>
                        </div>  
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-rulers me-2"></span>Store Area Measurement
                            </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="area_unit" class="form-label">Units (eg. Meter, Inch)</label>
                            <select class="form-control" id="area_unit" name="area_unit">
                                <option value="">Select unit</option>
                                <option value="meter">Meters</option>
                                <option value="cm">Centimeters</option>
                                <option value="mm">Millimeters</option>
                                <option value="inch">Inches</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="area_length" class="form-label">Area Length</label>
                            <input type="number" step="0.01" class="form-control" id="area_length" name="area_length"
                                   value="<?= old('area_length') ?>" placeholder="e.g., 10.5" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="area_width" class="form-label">Area Width</label>
                            <input type="number" step="0.01" class="form-control" id="area_width" name="area_width"
                                   value="<?= old('area_width') ?>" placeholder="e.g., 8.0" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="additional_area_sqm" class="form-label">Additional Area (sqm)</label>
                            <input type="number" step="0.01" class="form-control" id="additional_area_sqm" name="additional_area_sqm"
                                   value="<?= old('additional_area_sqm') ?>" placeholder="For irregular shapes" min="0">
                            <small class="text-muted">Optional: for L-shaped or irregular areas</small>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total Store Area:</strong>
                            <span id="computed_sqm" class="fs-4 fw-bold">0.00 sqm</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Installation Photos -->
            <div class="card mb-3">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-images me-2"></span>Overall Installation Photos
                            </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Upload general photos of the store before and after installation (optional, multiple photos allowed)
                    </p>

                    <!-- Before Photos -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-camera me-1"></i>Before Installation (Store Photos)
                        </label>
                        <div class="multi-photo-upload" data-type="overall_before">
                            <input type="file" class="d-none" id="overall_before_input"
                                   name="overall_before_photos[]" accept="image/*" capture="environment" multiple>
                            <div class="upload-placeholder" onclick="document.getElementById('overall_before_input').click()">
                                <i class="bi bi-cloud-upload display-6 text-muted"></i>
                                <p class="mb-1">Click to upload or take photos</p>
                                <small class="text-muted">You can select multiple photos at once</small>
                            </div>
                            <div class="photo-preview-grid" id="overall_before_preview"></div>
                        </div>
                    </div>

                    <!-- After Photos -->
                    <div class="mb-0">
                        <label class="form-label fw-bold">
                            <i class="bi bi-camera-fill me-1"></i>After Installation (Store Photos)
                        </label>
                        <div class="multi-photo-upload" data-type="overall_after">
                            <input type="file" class="d-none" id="overall_after_input"
                                   name="overall_after_photos[]" accept="image/*" capture="environment" multiple>
                            <div class="upload-placeholder" onclick="document.getElementById('overall_after_input').click()">
                                <i class="bi bi-cloud-upload display-6 text-muted"></i>
                                <p class="mb-1">Click to upload or take photos</p>
                                <small class="text-muted">You can select multiple photos at once</small>
                            </div>
                            <div class="photo-preview-grid" id="overall_after_preview"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Spacer for alignment --><div class="col-lg-4 mb-4"></div>

        <!-- Items to Install -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary">
                    <span class="d-flex align-text-center">
                    <span class="bi bi-box-seam me-2"></span>Items to Install
                            </span>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['items'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['items']; ?></div>
                    <?php endif; ?>
                    <?php if (isset($errors['photos'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['photos']; ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($assignmentItems)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle display-4 text-success"></i>
                        <p class="mt-2">All items have been installed for this assignment.</p>
                    </div>
                    <?php else: ?>
                    
                    <div class="accordion" id="itemsAccordion">
                        <?php foreach ($assignmentItems as $index => $item): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#item-<?php echo $item['id']; ?>">
                                    <span class="badge bg-primary me-2"><?php echo $item['item_code']; ?></span>
                                    <?php echo clean($item['item_name']); ?>
                                    <span class="badge bg-secondary ms-auto me-2">
                                        <?php echo $item['quantity_assigned'] - $item['quantity_installed']; ?> remaining
                                    </span>
                                </button>
                            </h2>
                            <div id="item-<?php echo $item['id']; ?>" 
                                 class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                 data-bs-parent="#itemsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Quantity Installed <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" 
                                                   name="item_data[<?php echo $item['id']; ?>][quantity]"
                                                   min="1" max="<?php echo $item['quantity_assigned'] - $item['quantity_installed']; ?>"
                                                   value="<?php echo $item['quantity_assigned'] - $item['quantity_installed']; ?>" required>
                                            <small class="text-muted">Max: <?php echo $item['quantity_assigned'] - $item['quantity_installed']; ?> <?php echo $item['unit']; ?></small>
                                        </div>
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">Remarks</label>
                                            <input type="text" class="form-control" 
                                                   name="item_data[<?php echo $item['id']; ?>][remarks]"
                                                   placeholder="Optional notes for this item">
                                        </div>
                                        <!-- Item Before Photos (Multiple) -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bi bi-camera me-1"></i>Before Photos <span class="text-danger">*</span>
                                            </label>
                                            <div class="multi-photo-upload" data-type="item_before_<?php echo $item['id']; ?>">
                                                <input type="file" class="d-none item-photo-input"
                                                       id="before_<?php echo $item['id']; ?>"
                                                       name="item_before_<?php echo $item['id']; ?>[]"
                                                       accept="image/*" capture="environment" multiple required>
                                                <div class="upload-placeholder" onclick="document.getElementById('before_<?php echo $item['id']; ?>').click()">
                                                    <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                                    <p class="mb-0">Click to upload or take photos</p>
                                                    <small class="text-muted">Multiple photos allowed</small>
                                                </div>
                                                <div class="photo-preview-grid" id="preview_before_<?php echo $item['id']; ?>"></div>
                                            </div>
                                        </div>

                                        <!-- Item After Photos (Multiple) -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bi bi-camera-fill me-1"></i>After Photos <span class="text-danger">*</span>
                                            </label>
                                            <div class="multi-photo-upload" data-type="item_after_<?php echo $item['id']; ?>">
                                                <input type="file" class="d-none item-photo-input"
                                                       id="after_<?php echo $item['id']; ?>"
                                                       name="item_after_<?php echo $item['id']; ?>[]"
                                                       accept="image/*" capture="environment" multiple required>
                                                <div class="upload-placeholder" onclick="document.getElementById('after_<?php echo $item['id']; ?>').click()">
                                                    <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                                    <p class="mb-0">Click to upload or take photos</p>
                                                    <small class="text-muted">Multiple photos allowed</small>
                                                </div>
                                                <div class="photo-preview-grid" id="preview_after_<?php echo $item['id']; ?>"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="?assignment_id=" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Change Assignment
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            <i class="bi bi-check-lg me-1"></i>Submit Report
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$extraScripts = <<<'SCRIPT'
<script>
// Store Status Change Handler
document.getElementById('store_status')?.addEventListener('change', function() {
    const rescheduleSection = document.getElementById('reschedule_section');
    const remarksSection = document.getElementById('status_remarks_section');

    // Show/hide reschedule date field
    if (this.value === 'reschedule') {
        rescheduleSection.style.display = 'block';
        document.getElementById('reschedule_date').required = true;
    } else {
        rescheduleSection.style.display = 'none';
        document.getElementById('reschedule_date').required = false;
    }

    // Show/hide remarks field for certain statuses
    if (['store_closed', 'refused_to_dressup', 'reschedule', 'others'].includes(this.value)) {
        remarksSection.style.display = 'block';
    } else {
        remarksSection.style.display = 'none';
    }
});

// Character counter for status remarks
document.getElementById('status_remarks')?.addEventListener('input', function() {
    document.getElementById('char_count').textContent = this.value.length;
});

// Contact number formatting
document.getElementById('contact_number')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits

    if (value.length > 4 && value.length <= 7) {
        value = value.slice(0, 4) + '-' + value.slice(4);
    } else if (value.length > 7) {
        value = value.slice(0, 4) + '-' + value.slice(4, 7) + '-' + value.slice(7, 11);
    }

    e.target.value = value;
});
// Conversion factors TO meters
const unitToMeter = {
    meter: 1,
    cm: 0.01,
    mm: 0.001,
    inch: 0.0254
};

// Toggle readonly inputs based on unit selection
function toggleInputs() {
    const unit = document.getElementById('area_unit')?.value;

    const inputs = [
        document.getElementById('area_length'),
        document.getElementById('area_width'),
        document.getElementById('additional_area_sqm')
    ];

    inputs.forEach(input => {
        if (!input) return;

        if (unit) {
            input.removeAttribute('readonly');
            input.classList.remove('bg-light'); // remove gray
        } else {
            input.setAttribute('readonly', true);
            input.classList.add('bg-light'); // Bootstrap gray
            input.value = '';
        }
    });

    computeAreaSQM();
}

// SQM Auto-computation with dynamic unit conversion
function computeAreaSQM() {
    const unit = document.getElementById('area_unit')?.value;

    const length = parseFloat(document.getElementById('area_length')?.value) || 0;
    const width = parseFloat(document.getElementById('area_width')?.value) || 0;
    const additional = parseFloat(document.getElementById('additional_area_sqm')?.value) || 0;

    const display = document.getElementById('computed_sqm');

    if (!unit) {
        if (display) {
            display.textContent = '0.00 sqm';
            display.classList.add('text-muted');
            display.classList.remove('text-primary');
        }
        return;
    }

    const factor = unitToMeter[unit] || 1;

    const lengthInMeters = length * factor;
    const widthInMeters = width * factor;

    const total = (lengthInMeters * widthInMeters) + additional;

    if (display) {
        display.textContent = total.toFixed(2) + ' sqm';

        if (total > 0) {
            display.classList.remove('text-muted');
            display.classList.add('text-primary');
        } else {
            display.classList.add('text-muted');
            display.classList.remove('text-primary');
        }
    }
}

// Event listeners
['area_length', 'area_width', 'additional_area_sqm']
.forEach(id => {
    document.getElementById(id)?.addEventListener('input', computeAreaSQM);
});

document.getElementById('area_unit')?.addEventListener('change', toggleInputs);

window.addEventListener('DOMContentLoaded', toggleInputs);

// ===========================================================================
// Multiple Photo Upload Handler
// ===========================================================================

// Initialize all photo upload inputs
function initPhotoUploads() {
    // Overall photos
    setupPhotoUpload('overall_before_input', 'overall_before_preview');
    setupPhotoUpload('overall_after_input', 'overall_after_preview');
    setupPhotoUpload('overall_store_input', 'overall_store_preview');

    // Item photos - dynamically handled
    document.querySelectorAll('.item-photo-input').forEach(input => {
        const previewId = input.id.replace(/^(before|after)_/, 'preview_$1_');
        setupPhotoUpload(input.id, previewId);
    });
}

function setupPhotoUpload(inputId, previewContainerId) {
    const input = document.getElementById(inputId);
    const previewContainer = document.getElementById(previewContainerId);

    if (!input || !previewContainer) return;

    input.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;

        // Clear preview container
        previewContainer.innerHTML = '';

        // Hide upload placeholder
        const placeholder = input.closest('.multi-photo-upload').querySelector('.upload-placeholder');
        if (placeholder) placeholder.style.display = 'none';

        // Show preview grid
        previewContainer.style.display = 'grid';

        // Preview each file
        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const photoItem = document.createElement('div');
                photoItem.className = 'photo-preview-item';
                photoItem.innerHTML = `
                    <img src="${e.target.result}" alt="Photo ${index + 1}">
                    <button type="button" class="btn-remove-photo" onclick="removePhoto(this, '${inputId}', ${index})" title="Remove">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                    <div class="photo-number">${index + 1}</div>
                `;
                previewContainer.appendChild(photoItem);
            };
            reader.readAsDataURL(file);
        });

        // Show add more button
        const addMoreBtn = document.createElement('div');
        addMoreBtn.className = 'photo-add-more';
        addMoreBtn.innerHTML = `
            <i class="bi bi-plus-circle"></i>
            <span>Add More</span>
        `;
        addMoreBtn.onclick = () => input.click();
        previewContainer.appendChild(addMoreBtn);
    });
}

function removePhoto(button, inputId, photoIndex) {
    const input = document.getElementById(inputId);
    const previewContainer = button.closest('.photo-preview-grid');

    // Create a new FileList without the removed file
    const dt = new DataTransfer();
    const files = Array.from(input.files);

    files.forEach((file, index) => {
        if (index !== photoIndex) {
            dt.items.add(file);
        }
    });

    input.files = dt.files;

    // Trigger change event to refresh preview
    input.dispatchEvent(new Event('change', { bubbles: true }));

    // If no files left, show placeholder again
    if (input.files.length === 0) {
        previewContainer.innerHTML = '';
        previewContainer.style.display = 'none';
        const placeholder = input.closest('.multi-photo-upload').querySelector('.upload-placeholder');
        if (placeholder) placeholder.style.display = 'block';
    }
}

// Get GPS Location
let lastCoords = null;
let watchId = null;

const MIN_DISTANCE_METERS = 8;

function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const toRad = deg => deg * Math.PI / 180;

    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat/2) ** 2 +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon/2) ** 2;

    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
}

function updateUI(latitude, longitude) {
    const lat = latitude.toFixed(6);
    const lng = longitude.toFixed(6);

    // hidden (auto)
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;

    // also update visible fields (so user copies exact value)
    document.getElementById('lat-display').textContent = lat;
    document.getElementById('lng-display').textContent = lng;
}

function getLocation() {
    const status = document.getElementById('gps-status');
    const result = document.getElementById('gps-result');
    const error = document.getElementById('gps-error');
    const submitBtn = document.getElementById('submitBtn');

    status.style.display = 'block';
    result.style.display = 'none';
    error.style.display = 'none';

    if (!navigator.geolocation) {
        error.textContent = "Geolocation not supported.";
        error.style.display = 'block';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            const { latitude, longitude } = pos.coords;

            lastCoords = { latitude, longitude };
            updateUI(latitude, longitude);

            status.style.display = 'none';
            result.style.display = 'block';
            submitBtn.disabled = false;
        },
        err => {
            error.textContent = err.message;
            error.style.display = 'block';
        },
        {
            enableHighAccuracy: false, // fast
            maximumAge: 60000,         // allow cached (1 min)
            timeout: 5000
        }
    );

    watchId = navigator.geolocation.watchPosition(
        pos => {
            const { latitude, longitude, accuracy } = pos.coords;

            if (accuracy > 30) return;

            if (lastCoords) {
                const distance = getDistance(
                    lastCoords.latitude,
                    lastCoords.longitude,
                    latitude,
                    longitude
                );

                if (distance < MIN_DISTANCE_METERS) return;
            }

            lastCoords = { latitude, longitude };
            updateUI(latitude, longitude);
        },
        () => {},
        {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 10000
        }
    );
}

// Refresh GPS
document.getElementById('refreshGps')?.addEventListener('click', getLocation);

// Preview Image
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const container = input.closest('.photo-upload');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            container.classList.add('has-image');
            container.querySelector('i').style.display = 'none';
            container.querySelector('span').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Form Validation
document.getElementById('installationForm')?.addEventListener('submit', function(e) {
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;
    
    if (!lat || !lng) {
        e.preventDefault();
        alert('GPS location is required. Please enable location services and try again.');
        return false;
    }
    
    App.showLoading();
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    getLocation();
    initPhotoUploads();
});
</script>
SCRIPT;
?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
