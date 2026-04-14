<?php
require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'includes/helpers.php'; 

$db = Database::getInstance();

$welcomeName = clean($_GET['name'] ?? 'Welcome Name');

// ---- Global progress (assignment_items) ----
$totalItems = (int) $db->fetchColumn("SELECT COUNT(*) FROM assignment_items");
$completedItems = (int) $db->fetchColumn("SELECT COUNT(*) FROM assignment_items WHERE status = 'completed'");
$pendingItems = max(0, $totalItems - $completedItems); // pending + partial

$completionRate = $totalItems > 0 ? (($completedItems / $totalItems) * 100.0) : 0.0;
$completionRateDisplay = number_format($completionRate, 1);

// ---- Area totals ----
$totalActiveAreas = (int) $db->count('installation_areas', "status = 'active'");

// ---- City comparison + most/least installed ----
$cityRows = $db->fetchAll("
    SELECT
        ia.city AS city,
        SUM(CASE WHEN ai.status = 'completed' THEN 1 ELSE 0 END) AS completed_items,
        COUNT(*) AS total_items
    FROM assignment_items ai
    JOIN assignments a ON a.id = ai.assignment_id
    JOIN installation_areas ia ON ia.id = a.area_id
    WHERE ia.status = 'active'
      AND ia.city IS NOT NULL
      AND ia.city <> ''
    GROUP BY ia.city
    ORDER BY total_items DESC
");

$cityStats = [];
foreach ($cityRows as $row) {
    $total = (int) ($row['total_items'] ?? 0);
    $completed = (int) ($row['completed_items'] ?? 0);
    $rate = $total > 0 ? (($completed / $total) * 100.0) : 0.0;
    $cityStats[] = [
        'city' => clean((string) ($row['city'] ?? '-')),
        'completed_items' => $completed,
        'total_items' => $total,
        'rate' => $rate,
        'rate_display' => number_format($rate, 2),
    ];
}

$bars = array_slice($cityStats, 0, 5);

// Most/Least installed location: by completed_items
$mostCity = null;
$leastCity = null;
if (!empty($cityStats)) {
    $sortedByCompletedDesc = $cityStats;
    usort($sortedByCompletedDesc, fn($a, $b) => $b['completed_items'] <=> $a['completed_items']);
    $mostCity = $sortedByCompletedDesc[0];

    $sortedByCompletedAsc = $cityStats;
    usort($sortedByCompletedAsc, fn($a, $b) => $a['completed_items'] <=> $b['completed_items']);
    $leastCity = $sortedByCompletedAsc[0];
}

// ---- Detailed area breakdown (Area Progress tab table) ----
$cityBreakdownRows = $db->fetchAll("
    SELECT
        ia.city AS city,
        COUNT(*) AS total_items,
        SUM(CASE WHEN ai.status = 'completed' THEN 1 ELSE 0 END) AS completed_items,
        MAX(ai.updated_at) AS last_update
    FROM assignment_items ai
    JOIN assignments a ON a.id = ai.assignment_id
    JOIN installation_areas ia ON ia.id = a.area_id
    WHERE ia.status = 'active'
      AND ia.city IS NOT NULL
      AND ia.city <> ''
    GROUP BY ia.city
    ORDER BY total_items DESC
");

$cityBreakdown = [];
foreach ($cityBreakdownRows as $row) {
    $total = (int) ($row['total_items'] ?? 0);
    $completed = (int) ($row['completed_items'] ?? 0);
    $pending = max(0, $total - $completed);

    $rate = $total > 0 ? (($completed / $total) * 100.0) : 0.0;
    $rateDisplay = number_format($rate, 0);

    $lastUpdate = $row['last_update'] ?? null;
    $lastUpdateDisplay = $lastUpdate
        ? date('M j, Y g:i A', strtotime((string) $lastUpdate))
        : '-';

    $cityBreakdown[] = [
        'city' => clean((string) ($row['city'] ?? '-')),
        'total' => $total,
        'completed' => $completed,
        'pending' => $pending,
        'rate' => $rate,
        'rate_display' => $rateDisplay,
        'last_update_display' => $lastUpdateDisplay,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Page - <?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/client.css">
</head>

<body class="client-body">
    <header class="client-topbar">
        <div class="client-topbar-left">
            <div class="client-welcome"><?php echo $welcomeName; ?></div>
            <div class="client-topbar-sub">BRANDBOSS Tracking Progress</div>
        </div>

        <div class="client-topbar-right dropdown">
            <button class="client-topbar-dd dropdown-toggle" 
                    type="button" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                <i class="bi bi-person-circle"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="change-password.php">
                        <i class="bi bi-key me-2"></i> Change Password
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-danger" 
                    href="logout.php" 
                    onclick="return confirm('Are you sure you want to logout?')">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <main class="client-container">
        <section class="client-stats-grid" aria-label="Overall stats">
            <div class="client-card client-card-total">
                <div class="client-card-content">
                    <div>
                        <div id="total-items-label" class="client-card-label">Total Items</div>
                        <div class="client-card-value"><?php echo number_format($totalItems); ?></div>
                        <div id="total-items-label" class="client-card-sub">All items</div>
                    </div>
                    <div class="client-card-icon">
                        <i class="bi bi-boxes"></i>
                    </div>
                </div>
            </div>

            <div class="client-card client-card-completed">
                <div class="client-card-content">
                    <div>
                        <div class="client-card-label">Completed</div>
                        <div class="client-card-value client-text-completed"><?php echo number_format($completedItems); ?></div>
                        <div class="client-card-sub">Successfully finished</div>
                    </div>
                    <div class="client-card-icon client-card-icon-completed">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
            </div>

            <div class="client-card client-card-pending">
                <div class="client-card-content">
                    <div>
                        <div class="client-card-label">Pending</div>
                        <div class="client-card-value client-text-pending"><?php echo number_format($pendingItems); ?></div>
                        <div class="client-card-sub">Currently in progress</div>
                    </div>
                    <div class="client-card-icon client-card-icon-pending">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>

            <div class="client-card client-card-completion-rate">
                <div class="client-card-content">
                    <div>
                        <div id="completion-rate-label" class="client-card-label client-text-white">Completion Rate</div>
                        <div class="client-card-value client-text-white"><?php echo $completionRateDisplay; ?>%</div>
                        <div id="completion-rate-label" class="client-card-sub client-text-white">Total completion rate</div>
                    </div>
                    <div class="client-card-icon client-card-icon-rate">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </section>

        <div class="client-tabs" aria-label="Page tabs">
            <button id="tabOverview" class="client-tab client-tab-active" type="button">
                Overview
            </button>
            <button id="tabAreaProgress" class="client-tab" type="button">
                Area Progress
            </button>
        </div>

        <div id="overviewSection" class="client-tab-section client-tab-section-active" aria-label="Overview content">
            <section class="client-area-grid mb-4" aria-label="Area cards">
                <div class="client-area-card client-area-card-total">
                    <div class="client-area-card-content">
                        <div>
                            <div id="total-area-label" class="client-card-label client-text-white">Total Area</div>
                            <div class="client-area-value"><?php echo number_format($totalActiveAreas); ?></div>
                            <div id="total-area-sub" class="client-card-sub client-text-white">Total Active Areas</div>
                        </div>
                        <div class="client-area-icon">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                </div>

                <div class="client-area-card client-area-card-most">
                    <div class="client-area-card-content">
                        <div>
                            <div class="most-installed-label">Most Installed Area</div>
                            <div class="client-area-name most-installed-name"><?php echo $mostCity ? $mostCity['city'] : '-'; ?></div>
                            <div class="most-installed-sub">Most installed location</div>
                        </div>
                        <div class="client-area-pin">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                </div>

                <div class="client-area-card client-area-card-least">
                    <div class="client-area-card-content">
                        <div>
                            <div class="client-card-label least-installed-label">Least Installed Area</div>
                            <div class="client-area-name least-installed-name"><?php echo $leastCity ? $leastCity['city'] : '-'; ?></div>
                            <div class="client-card-sub least-installed-sub">Least installed location</div>
                        </div>
                        <div class="client-area-pin">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                </div>
            </section>

            <section class="client-bottom-grid" aria-label="Completion overview">
                <div class="client-donut-card">
                  <div class="client-overall-completion-label">Overall Completion Rate</div>
                    <div class="client-donut-wrap">
                        <div class="client-donut" style="--rate: <?php echo $completionRate; ?>%">
                            <div class="client-donut-inner">
                                <div class="client-donut-percent"><?php echo $completionRateDisplay; ?>%</div>
                                <div class="client-donut-label">Complete</div>
                            </div>
                        </div>
                    </div>

                    <div class="client-mini-stats">
                        <div class="client-mini client-mini-completed">
                            <div class="client-mini-value"><?php echo number_format($completedItems); ?></div>
                            <div class="client-mini-label">Completed</div>
                        </div>
                        <div class="client-mini client-mini-pending">
                            <div class="client-mini-value"><?php echo number_format($pendingItems); ?></div>
                            <div class="client-mini-label">Pending</div>
                        </div>
                    </div>
                </div>

                <div class="client-area-compare-card">
                    <div class="client-area-compare-header">Area Progress Comparison</div>

                    <div class="client-area-bars">
                        <?php if (!empty($bars)): ?>
                            <?php foreach ($bars as $b): ?>
                                <div class="client-area-row">
                                    <div class="client-area-row-name"><?php echo $b['city']; ?></div>
                                    <div class="client-area-row-bar" style="--p: <?php echo $b['rate']; ?>%;"></div>
                                    <div class="client-area-row-rate"><?php echo $b['rate_display']; ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted text-center py-4">No area progress data available.</div>
                        <?php endif; ?>
                    </div>

                    <div class="client-area-legend">
                        <div class="client-legend-item">
                            <span class="client-legend-swatch client-legend-completed"></span>
                            Completed   
                        </div>
                        <div class="client-legend-item">
                            <span class="client-legend-swatch client-legend-pending"></span>  
                            Pending    
                        </div>   
                    </div>     
                </div> 
            </section>      
        </div>    
            
        <div id="areaProgressSection" class="client-tab-section" aria-label="Area progress content">
            <div class="client-area-progress-full">   
                <div class="client-area-table-card">   
                    <div class="client-area-table-header">   
                        <div class="client-area-table-title">Detailed Area Breakdown</div>
                                  
                        <div class="client-area-search">        
                            <i class="bi bi-search client-area-search-icon"></i>
                            <input          
                                id="citySearch" 
                                class="client-area-search-input"         
                                type="text"         
                                placeholder="Search Area"           
                                autocomplete="off"              
                            >                   
                        </div>                          
                    </div>              

                    <div class="client-area-table-wrapper">         
                        <table class="client-area-table">           
                            <thead>             
                                <tr>                                                        
                                    <th class="col-area">Area</th>  
                                    <th class="col-total">Total</th>
                                    <th class="col-completed">Completed</th>
                                    <th class="col-pending">Pending</th>
                                    <th class="col-progress">Progress</th>
                                    <th class="col-last">Last Update</th>
                                </tr>
                            </thead>
                            <tbody id="cityTableBody">
                                <?php if (!empty($cityBreakdown)): ?>
                                    <?php foreach ($cityBreakdown as $row): ?>
                                        <tr data-city="<?php echo htmlspecialchars($row['city'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <td class="col-area">
                                                <span class="client-area-geo">
                                                    <i class="bi bi-geo-alt-fill"></i>
                                                </span>
                                                <span class="client-area-name"><?php echo $row['city']; ?></span>
                                            </td>
                                            <td id="client-area-total" class="col-total text-center"><?php echo number_format($row['total']); ?></td>
                                            <td class="col-completed text-center">
                                                <span class="client-area-pill client-area-pill-completed"><?php echo number_format($row['completed']); ?></span>
                                            </td>
                                            <td class="col-pending text-center">
                                                <span class="client-area-pill client-area-pill-pending"><?php echo number_format($row['pending']); ?></span>
                                            </td>
                                            <td class="col-progress">
                                                <div class="client-area-progresscell">
                                                    <div class="client-area-progresstrack" aria-label="Progress">
                                                        <div class="client-area-progressfill" style="width: <?php echo number_format($row['rate'], 1); ?>%;"></div>
                                                    </div>
                                                    <div class="client-area-progresspercent"><?php echo $row['rate_display']; ?>%</div>
                                                </div>
                                            </td>
                                            <td class="col-last client-area-last-update"><?php echo $row['last_update_display']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-muted text-center py-4">No area progress data available.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="client-area-pagination">
                        <button id="areaPrevBtn" type="button" class="client-area-page-btn" disabled>
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </button>
                        <div id="areaPageInfo" class="client-area-page-info">Page 1 of 1</div>
                        <button id="areaNextBtn" type="button" class="client-area-page-btn">
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <script>
                        (function () {
                            var pageSize = 5;
                            var tbody = document.getElementById('cityTableBody');
                            if (!tbody) return;

                            var rows = Array.pro
                            totype.slice.call(tbody.querySelectorAll('tr'));
                            var search = document.getElementById('citySearch');
                            var pageInfo = document.getElementById('areaPageInfo');
                            var prevBtn = document.getElementById('areaPrevBtn');
                            var nextBtn = document.getElementById('areaNextBtn');

                            var currentPage = 1;


                            function setDisabled(btn, disabled) {
                                if (!btn) return;
                                btn.disabled = !!disabled;
                            }
 
                            function render() {
                                var term = (search && search.value ? search.value : '').toLowerCase().trim();

                                var filtered = rows.filter(function (r) {
                                    var city = (r.getAttribute('data-city') || '').toLowerCase();
                                    return city.indexOf(term) !== -1;
                                });

                                var totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
                                if (currentPage > totalPages) currentPage = totalPages;
                                if (currentPage < 1) currentPage = 1;

                                rows.forEach(function (r) { r.style.display = 'none'; });

                                var start = (currentPage - 1) * pageSize;
                                var end = start + pageSize;
                                filtered.slice(start, end).forEach(function (r) {
                                    r.style.display = 'table-row';
                                });

                                if (pageInfo) pageInfo.textContent = 'Page ' + currentPage + ' of ' + totalPages;
                                setDisabled(prevBtn, currentPage <= 1);
                                setDisabled(nextBtn, currentPage >= totalPages);
                            }

                            if (search) {
                                search.addEventListener('input', function () {
                                    currentPage = 1;
                                    render();
                                });
                            }

                            if (prevBtn) {
                                prevBtn.addEventListener('click', function () {
                                    if (currentPage > 1) {
                                        currentPage--;
                                        render();
                                    }
                                });
                            }

                            if (nextBtn) {
                                nextBtn.addEventListener('click', function () {
                                    currentPage++;
                                    render();
                                });
                            }

                            render();
                        })();
                    </script>
                </div>
            </div>
        </div>
    </main>
    <script>
        (function () {
            var tabOverview = document.getElementById('tabOverview');
            var tabAreaProgress = document.getElementById('tabAreaProgress');
            var overviewSection = document.getElementById('overviewSection');
            var areaProgressSection = document.getElementById('areaProgressSection');

            if (!tabOverview || !tabAreaProgress || !overviewSection || !areaProgressSection) return;

            function showOverview() {
                tabOverview.classList.add('client-tab-active');
                tabAreaProgress.classList.remove('client-tab-active');
                overviewSection.classList.add('client-tab-section-active');
                areaProgressSection.classList.remove('client-tab-section-active');
            }

            function showAreaProgress() {
                tabAreaProgress.classList.add('client-tab-active');
                tabOverview.classList.remove('client-tab-active');
                areaProgressSection.classList.add('client-tab-section-active');
                overviewSection.classList.remove('client-tab-section-active');
            }

            tabOverview.addEventListener('click', function () {
                showOverview();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            tabAreaProgress.addEventListener('click', function () {
                showAreaProgress();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            showOverview();
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

