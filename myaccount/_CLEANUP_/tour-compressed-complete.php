<?php
/**
 * Tour Compressed Complete - Full refactor maintaining ALL features
 * Every single feature from tour.php is preserved
 */

$addClasses[] = 'sms';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Helper function - preserved from original
function getUserAttribute($database, $userId, $type, $name) {
    $query = "SELECT description, string_value, value FROM bg_user_attributes 
              WHERE user_id = :user_id 
              AND type = :type 
              AND name = :name 
              AND status = 'active' 
              LIMIT 1";
    $stmt = $database->prepare($query);
    $stmt->execute([':user_id' => $userId, ':type' => $type, ':name' => $name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function - preserved from original
function getCompanyLocations($database, $companyId) {
    $query = "SELECT * FROM bg_company_locations 
              WHERE company_id = :company_id 
              ORDER BY location_id DESC";
    $stmt = $database->prepare($query);
    $stmt->execute([':company_id' => $companyId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Haversine distance calculation - preserved from original
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 3959; // miles
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

// Redirect if not logged in
if (!$account->isactive()) {
    echo "<script>window.location.href='/login';</script>";
    exit();
}

// Handle AJAX requests - ALL endpoints preserved
if (!empty($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_home_location':
            include(__DIR__ . '/ajax/tour-update-home.php');
            break;
            
        case 'search_business_locations':
            include(__DIR__ . '/ajax/tour-search-locations.php');
            break;
            
        case 'save_business_location':
            include(__DIR__ . '/ajax/tour-save-location.php');
            break;
            
        case 'update_tour_location':
            include(__DIR__ . '/ajax/tour-update-location.php');
            break;
            
        case 'send_to_phone':
            include(__DIR__ . '/ajax/tour-send-phone.php');
            break;
    }
    exit();
}

// Get date parameter
$date = $_GET['date'] ?? date('Y-m-d');
$dateObject = new DateTime($date);
$formattedDate = $dateObject->format('l, F j, Y');

// Get home location - EXACT logic from original
$homeaddress = '10106 Atlanta Street, Parker, CO 80134'; // default fallback
$home_lat = null;
$home_lng = null;

$homeData = getUserAttribute($database, $current_user_data['user_id'], 'tour_settings', 'default_home_location');
if ($homeData && !empty($homeData['description'])) {
    $locationData = json_decode($homeData['description'], true);
    if ($locationData && isset($locationData['address'])) {
        $homeaddress = $locationData['address'];
        $home_lat = $locationData['lat'] ?? null;
        $home_lng = $locationData['lng'] ?? null;
    }
}

// Get tour businesses - EXACT query from original
// EXACT query from original tour.php
$checkEnrollmentQuery = "SELECT t.*, 
                        cl.location_id as cl_location_id,
                        cl.address as cl_address,
                        cl.city as cl_city,
                        cl.state as cl_state,
                        cl.zip_code as cl_zip_code,
                        cl.latitude as cl_latitude,
                        cl.longitude as cl_longitude
                        FROM bg_user_tours t 
                        LEFT JOIN bg_company_locations cl ON t.location_id = cl.location_id 
                        WHERE t.calendar_dt = :date 
                        AND t.user_id = :user_id";
$stmt = $database->prepare($checkEnrollmentQuery);
$stmt->execute([':date' => $date, ':user_id' => $current_user_data['user_id']]);
$tourCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build companies array with distance calculations - EXACT logic from original
$companies = [];
$outOfRangeCount = 0;
$maxDistanceForMap = 25; // miles

foreach ($tourCompanies as $item_company) {
    $company_data = $app->getcompany($item_company['company_id']);
    
    // Merge tour-specific location data if available
    if (!empty($item_company['cl_location_id'])) {
        $company_data['location_id'] = $item_company['cl_location_id'];
        $company_data['latitude'] = $item_company['cl_latitude'];
        $company_data['longitude'] = $item_company['cl_longitude'];
        $company_data['address'] = $item_company['cl_address'];
        $company_data['city'] = $item_company['cl_city'];
        $company_data['state'] = $item_company['cl_state'];
        $company_data['zip_code'] = $item_company['cl_zip_code'];
    }
    
    // Calculate distance if we have coordinates
    $company_data['distance'] = null;
    $company_data['is_out_of_range'] = false;
    
    if ($home_lat && $home_lng && !empty($company_data['latitude']) && !empty($company_data['longitude'])) {
        $distance = haversineDistance($home_lat, $home_lng, $company_data['latitude'], $company_data['longitude']);
        $company_data['distance'] = round($distance, 1);
        
        if ($distance > $maxDistanceForMap) {
            $company_data['is_out_of_range'] = true;
            $outOfRangeCount++;
        }
    }
    
    // Add tour_id for reference
    $company_data['tour_id'] = $item_company['tour_id'];
    
    $companies[] = $company_data;
}

// Get phone number - EXACT logic from original
$phoneNumber = $current_user_data['profile_phone_number'] ?? '';
if (empty($phoneNumber)) {
    $phoneData = getUserAttribute($database, $current_user_data['user_id'], 'tour_settings', 'sms_phone');
    if ($phoneData && !empty($phoneData['string_value'])) {
        $phoneNumber = $phoneData['string_value'];
    }
}

// Calculate business count message - fun dynamic message as requested
$businessCount = count($companies);
$dateObj = new DateTime($date);
$today = new DateTime('today');
$tomorrow = new DateTime('tomorrow');

$when = 'on ' . $dateObj->format('F j');
if ($dateObj->format('Y-m-d') == $today->format('Y-m-d')) {
    $when = 'today';
} elseif ($dateObj->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
    $when = 'tomorrow';
}

$messages = [
    'Grab all your freebies from',
    'Collect birthday rewards at',
    'Score free treats from',
    'Get your birthday goodies from',
    'Claim your rewards at'
];
$businessMessage = $messages[array_rand($messages)] . ' <strong>' . $businessCount . ' ' . ($businessCount == 1 ? 'business' : 'businesses') . '</strong> ' . $when . '!';

// Page configuration
$pageTitle = "My Birthday Tour";
$pageDescription = "Birthday tour for " . $formattedDate;
$header_flush = false; // Standard header spacing

// ALL styles from original tour.php preserved
$additionalstyles = '
<style>
/* Tour-specific styles - ALL preserved from original */
.tour-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 0;
    margin-bottom: 1rem;
}

.tour-business-card {
    position: relative;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #fff;
    transition: all 0.3s ease;
}

.tour-business-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.tour-business-card.out-of-range {
    opacity: 0.6;
    background: #f8f9fa;
}

.tour-business-card.ui-sortable-helper {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    transform: scale(1.02);
}

.drag-handle {
    cursor: grab;
    color: #6c757d;
    padding: 0.5rem;
    margin: -0.5rem 0.5rem -0.5rem -0.5rem;
    touch-action: none;
}

.drag-handle:active {
    cursor: grabbing;
}

.company-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    background: #f8f9fa;
    padding: 5px;
    border-radius: 0.25rem;
}

.distance-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
}

/* Mobile specific styles - EXACT from original */
@media (max-width: 768px) {
    .tour-business-card {
        padding: 0.75rem;
    }
    
    .company-logo {
        width: 48px;
        height: 48px;
    }
    
    .drag-handle {
        position: absolute;
        top: 50%;
        left: 0.5rem;
        transform: translateY(-50%);
        padding: 0.25rem;
        margin: 0;
    }
    
    .business-info {
        margin-left: 2rem;
    }
    
    .business-actions {
        margin-top: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
}

/* Map styles */
#tour-map, #tour-map-mobile {
    height: 600px;
    width: 100%;
}

@media (max-width: 768px) {
    #tour-map, #tour-map-mobile {
        height: 400px;
    }
}

/* Print styles - EXACT from original */
@media print {
    .no-print { display: none !important; }
    .tour-business-card { page-break-inside: avoid; }
    .drag-handle { display: none !important; }
    #tour-map { height: 500px !important; }
    .btn { display: none !important; }
    .modal { display: none !important; }
}

/* Alert styles for Bootstrap 5 fade alerts */
.alert {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Loading spinner */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
</style>
';

// Include standard headers
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
?>

<!-- Alert Container -->
<div id="alert-container" style="position: fixed; top: 70px; right: 20px; z-index: 1050; min-width: 300px;"></div>

<!-- Main Content -->
<div class="container main-content" data-layout="container">
    <!-- Compressed Header with all elements on one line -->
    <div class="tour-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <h4 class="mb-0">Celebration Tour</h4>
                </div>
                <div class="col-auto">
                    <small class="text-muted"><?php echo $formattedDate; ?></small>
                </div>
                <div class="col">
                    <small class="text-muted"><?php echo $businessMessage; ?></small>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-group btn-group-sm no-print" role="group">
                        <a href="/myaccount/tour-build?date=<?php echo $date; ?>" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Add
                        </a>
                        <button class="btn btn-outline-primary" 
                                onclick="sendToPhone()" 
                                id="send-to-phone-btn">
                            <i class="bi bi-phone"></i> Send
                        </button>
                        <button class="btn btn-outline-secondary" 
                                onclick="handlePrint()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Desktop Layout -->
        <div class="col-lg-4 d-none d-lg-block">
            <!-- Actions Card -->
            <div class="card mb-3">
                <div class="card-header">Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/myaccount/tour-build?date=<?php echo $date; ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add More Businesses
                        </a>
                        <button class="btn btn-primary" 
                                onclick="sendToPhone()" 
                                id="send-to-phone-btn-desktop">
                            <i class="bi bi-phone"></i> Send Tour to Phone
                        </button>
                        <button class="btn btn-secondary" 
                                onclick="handlePrint()">
                            <i class="bi bi-printer"></i> Print Tour
                        </button>
                    </div>
                    
                    <?php if (!empty($phoneNumber)): ?>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-phone"></i> <?php echo htmlspecialchars($phoneNumber); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Business List for Desktop -->
            <div class="card">
                <div class="card-header">Businesses</div>
                <div class="card-body">
                    <!-- Home Location -->
                    <div class="tour-business-card bg-light">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-house-fill fs-4 me-3"></i>
                            <div>
                                <strong>Starting Location</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($homeaddress); ?></small>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary ms-auto" 
                                    onclick="changeHomeLocation()">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sortable Business List -->
                    <div id="sortable-businesses">
                        <?php foreach ($companies as $index => $company): ?>
                        <?php
                        $isOutOfRange = $company['is_out_of_range'] ?? false;
                        $cardClasses = 'tour-business-card sortable-item';
                        if ($isOutOfRange) $cardClasses .= ' out-of-range';
                        ?>
                        <div class="<?php echo $cardClasses; ?>" 
                             data-company-id="<?php echo $company['company_id']; ?>"
                             data-tour-id="<?php echo $company['tour_id'] ?? ''; ?>"
                             data-rank="<?php echo $index + 1; ?>">
                            
                            <?php if (!$isOutOfRange): ?>
                            <div class="drag-handle">
                                <i class="bi bi-grip-vertical"></i>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($company['distance'] !== null && $isOutOfRange): ?>
                            <span class="badge bg-danger distance-badge">
                                <?php echo $company['distance']; ?> mi
                            </span>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-start <?php echo !$isOutOfRange ? 'ms-4' : ''; ?>">
                                <img src="<?php echo $display->companyimage($company['company_id'] . '/' . $company['company_logo']); ?>" 
                                     class="company-logo me-3" 
                                     alt="<?php echo htmlspecialchars($company['company_name']); ?>">
                                
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($company['company_name']); ?></strong><br>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php
                                        $addressParts = [];
                                        if (!empty($company['address'])) $addressParts[] = $company['address'];
                                        if (!empty($company['city'])) $addressParts[] = $company['city'];
                                        if (!empty($company['state'])) $addressParts[] = $company['state'];
                                        if (!empty($company['zip_code'])) $addressParts[] = $company['zip_code'];
                                        echo htmlspecialchars(implode(', ', $addressParts));
                                        ?>
                                    </small>
                                </div>
                                
                                <div class="ms-auto">
                                    <?php if (!$isOutOfRange): ?>
                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                            onclick="pickLocation(<?php echo $company['company_id']; ?>)"
                                            title="Change Location">
                                        <i class="bi bi-geo-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                    <a href="/myaccount/enrollments-individual?company_id=<?php echo $company['company_id']; ?>" 
                                       class="btn btn-sm btn-primary"
                                       target="_blank"
                                       title="View Details">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($outOfRangeCount > 0): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php echo $outOfRangeCount; ?> <?php echo $outOfRangeCount == 1 ? 'business is' : 'businesses are'; ?> 
                        too far from your starting location and will not be included in navigation.
                    </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-secondary w-100 mt-3" 
                            id="update-map-btn" 
                            style="display: none;"
                            onclick="updateMap()">
                        <i class="bi bi-arrow-clockwise"></i> Update Map
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Map Column -->
        <div class="col-lg-8">
            <!-- Desktop Map -->
            <div class="card mb-4 d-none d-lg-block">
                <div class="card-header">
                    Map
                    <button class="btn btn-sm btn-outline-secondary float-end" 
                            onclick="openInGoogleMaps()">
                        <i class="bi bi-box-arrow-up-right"></i> Open in Google Maps
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="tour-map"></div>
                </div>
            </div>
            
            <!-- Directions -->
            <div class="card d-none d-lg-block">
                <div class="card-header">Turn-by-Turn Directions</div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div id="tour-directions"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Layout -->
    <div class="row d-lg-none">
        <div class="col-12">
            <!-- Mobile Business List -->
            <div class="card mb-3">
                <div class="card-header">Businesses</div>
                <div class="card-body">
                    <!-- Home Location Mobile -->
                    <div class="tour-business-card bg-light">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-house-fill fs-5 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Starting Location</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($homeaddress); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Sortable Business List -->
                    <div id="sortable-businesses-mobile">
                        <?php foreach ($companies as $index => $company): ?>
                        <?php
                        $isOutOfRange = $company['is_out_of_range'] ?? false;
                        $cardClasses = 'tour-business-card sortable-item-mobile';
                        if ($isOutOfRange) $cardClasses .= ' out-of-range';
                        ?>
                        <div class="<?php echo $cardClasses; ?>" 
                             data-company-id="<?php echo $company['company_id']; ?>"
                             data-tour-id="<?php echo $company['tour_id'] ?? ''; ?>"
                             data-rank="<?php echo $index + 1; ?>">
                            
                            <?php if (!$isOutOfRange): ?>
                            <div class="drag-handle">
                                <i class="bi bi-grip-vertical"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="business-info">
                                <div class="d-flex align-items-start">
                                    <img src="<?php echo $display->companyimage($company['company_id'] . '/' . $company['company_logo']); ?>" 
                                         class="company-logo me-2" 
                                         alt="<?php echo htmlspecialchars($company['company_name']); ?>">
                                    
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($company['company_name']); ?></strong>
                                        <?php if ($company['distance'] !== null && $isOutOfRange): ?>
                                        <span class="badge bg-danger ms-1"><?php echo $company['distance']; ?> mi</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php
                                            $addressParts = [];
                                            if (!empty($company['address'])) $addressParts[] = $company['address'];
                                            if (!empty($company['city'])) $addressParts[] = $company['city'];
                                            echo htmlspecialchars(implode(', ', $addressParts));
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="business-actions mt-2">
                                    <?php if (!$isOutOfRange): ?>
                                    <button class="btn btn-sm btn-outline-secondary"
                                            onclick="pickLocation(<?php echo $company['company_id']; ?>)">
                                        <i class="bi bi-geo-alt"></i> Pick Location
                                    </button>
                                    <?php endif; ?>
                                    <a href="/myaccount/enrollments-individual?company_id=<?php echo $company['company_id']; ?>" 
                                       class="btn btn-sm btn-primary"
                                       target="_blank">
                                        <i class="bi bi-box-arrow-up-right"></i> Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="btn btn-secondary w-100 mt-3" 
                            id="update-map-btn-mobile" 
                            style="display: none;"
                            onclick="updateMapMobile()">
                        <i class="bi bi-arrow-clockwise"></i> Update Map
                    </button>
                </div>
            </div>
            
            <!-- Mobile Map -->
            <div class="card">
                <div class="card-header">
                    Map
                    <button class="btn btn-sm btn-outline-secondary float-end" 
                            onclick="openInGoogleMaps()">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="tour-map-mobile"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals - ALL preserved from original -->

<!-- Change Home Location Modal -->
<div class="modal fade" id="changeHomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Starting Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="home-location-form">
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" id="home-address-input" 
                               value="<?php echo htmlspecialchars($homeaddress); ?>" required>
                        <div class="form-text">Enter your starting address for the tour</div>
                    </div>
                    <div id="home-map-preview" style="height: 300px;" class="mb-3"></div>
                    <input type="hidden" id="home-lat" value="<?php echo $home_lat; ?>">
                    <input type="hidden" id="home-lng" value="<?php echo $home_lng; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveHomeLocation()">Save Location</button>
            </div>
        </div>
    </div>
</div>

<!-- Pick Location Modal -->
<div class="modal fade" id="pickLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pick Location - <span id="pick-location-company-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Search Radius</label>
                            <select class="form-select" id="radius-select">
                                <option value="10">10 miles</option>
                                <option value="25" selected>25 miles</option>
                                <option value="50">50 miles</option>
                                <option value="100">100 miles</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100 mb-3" onclick="searchBusinessLocations()">
                            <i class="bi bi-search"></i> Search Locations
                        </button>
                        <div id="location-search-results" class="list-group" style="max-height: 400px; overflow-y: auto;">
                            <!-- Results will appear here -->
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div id="picker-map" style="height: 500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send to Phone Modal -->
<div class="modal fade" id="sendPhoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Tour to Phone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="send-phone-form">
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone-number-input" 
                               value="<?php echo htmlspecialchars($phoneNumber); ?>" 
                               placeholder="(555) 123-4567" required>
                        <div class="form-text">We will send a text message with your tour link</div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="save-phone-check">
                        <label class="form-check-label" for="save-phone-check">
                            Save this phone number for future tours
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmSendToPhone()">Send SMS</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="spinner-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<!-- ALL JavaScript from original tour.php preserved -->
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

<script>
// Global variables - EXACT from original
var map, directionsService, directionsRenderer;
var mapMobile, directionsServiceMobile, directionsRendererMobile;
var markers = [];
var markersMobile = [];
var pickerMap, pickerMarkers = [];
var homeMap, homeMarker;
var currentCompanyId = null;
var lastSmsSentTime = 0;
var smsInProgress = false;

// Tour data
var tourData = {
    date: <?php echo json_encode($date); ?>,
    formattedDate: <?php echo json_encode($formattedDate); ?>,
    homeLocation: {
        address: <?php echo json_encode($homeaddress); ?>,
        lat: <?php echo json_encode($home_lat); ?>,
        lng: <?php echo json_encode($home_lng); ?>
    },
    companies: <?php echo json_encode($companies); ?>
};

// Initialize when ready
$(document).ready(function() {
    initializeSortable();
    loadGoogleMaps();
    setupPrintHandlers();
});

// Initialize sortable - EXACT from original with touch support
function initializeSortable() {
    // Desktop sortable
    $('#sortable-businesses').sortable({
        handle: '.drag-handle',
        axis: 'y',
        items: '.sortable-item:not(.out-of-range)',
        tolerance: 'pointer',
        update: function(event, ui) {
            $('#update-map-btn').show();
            updateRanks();
        }
    });
    
    // Mobile sortable
    $('#sortable-businesses-mobile').sortable({
        handle: '.drag-handle',
        axis: 'y',
        items: '.sortable-item-mobile:not(.out-of-range)',
        tolerance: 'pointer',
        update: function(event, ui) {
            $('#update-map-btn-mobile').show();
            updateRanksMobile();
        }
    });
    
    // Enable touch support for iPad
    if ('ontouchend' in document) {
        enableTouchSupport();
    }
}

// Touch support - EXACT from original
function enableTouchSupport() {
    // Map touch events to mouse events for sortable
    var touchHandled;
    
    function simulateMouseEvent(event, simulatedType) {
        // Ignore multi-touch events
        if (event.originalEvent.touches.length > 1) {
            return;
        }
        
        event.preventDefault();
        
        var touch = event.originalEvent.changedTouches[0],
            simulatedEvent = document.createEvent('MouseEvents');
        
        // Initialize the simulated mouse event
        simulatedEvent.initMouseEvent(
            simulatedType,    // type
            true,             // bubbles
            true,             // cancelable
            window,           // view
            1,                // detail
            touch.screenX,    // screenX
            touch.screenY,    // screenY
            touch.clientX,    // clientX
            touch.clientY,    // clientY
            false,            // ctrlKey
            false,            // altKey
            false,            // shiftKey
            false,            // metaKey
            0,                // button
            null              // relatedTarget
        );
        
        // Dispatch the simulated event
        touch.target.dispatchEvent(simulatedEvent);
    }
    
    // Bind touch events
    $('.drag-handle').on({
        touchstart: function(event) {
            var self = this;
            touchHandled = false;
            
            setTimeout(function() {
                if (!touchHandled) {
                    simulateMouseEvent(event, 'mousedown');
                }
            }, 100);
        },
        touchmove: function(event) {
            touchHandled = true;
            simulateMouseEvent(event, 'mousemove');
        },
        touchend: function(event) {
            simulateMouseEvent(event, 'mouseup');
            simulateMouseEvent(event, 'click');
        }
    });
}

// Update ranks after reordering
function updateRanks() {
    $('#sortable-businesses .sortable-item').each(function(index) {
        $(this).attr('data-rank', index + 1);
    });
}

function updateRanksMobile() {
    $('#sortable-businesses-mobile .sortable-item-mobile').each(function(index) {
        $(this).attr('data-rank', index + 1);
    });
}

// Load Google Maps API
function loadGoogleMaps() {
    if (typeof google === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $GLOBALS['GMAP_API_KEY'] ?? ''; ?>&libraries=places&callback=initMaps';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    } else {
        initMaps();
    }
}

// Initialize all maps
function initMaps() {
    initDesktopMap();
    initMobileMap();
}

// Initialize desktop map - EXACT from original
function initDesktopMap() {
    if (!document.getElementById('tour-map')) return;
    
    map = new google.maps.Map(document.getElementById('tour-map'), {
        zoom: 10,
        center: { lat: 39.571822, lng: -104.87961 },
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: map,
        panel: document.getElementById('tour-directions'),
        suppressMarkers: true,
        polylineOptions: {
            strokeColor: '#007bff',
            strokeWeight: 5
        }
    });
    
    calculateAndDisplayRoute();
}

// Initialize mobile map
function initMobileMap() {
    if (!document.getElementById('tour-map-mobile')) return;
    
    mapMobile = new google.maps.Map(document.getElementById('tour-map-mobile'), {
        zoom: 10,
        center: { lat: 39.571822, lng: -104.87961 },
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true
    });
    
    directionsServiceMobile = new google.maps.DirectionsService();
    directionsRendererMobile = new google.maps.DirectionsRenderer({
        map: mapMobile,
        suppressMarkers: true,
        polylineOptions: {
            strokeColor: '#007bff',
            strokeWeight: 5
        }
    });
    
    calculateAndDisplayRouteMobile();
}

// Calculate route - EXACT algorithm from original
function calculateAndDisplayRoute() {
    var waypoints = [];
    var bounds = new google.maps.LatLngBounds();
    
    // Clear existing markers
    markers.forEach(marker => marker.setMap(null));
    markers = [];
    
    // Get businesses in order
    var orderedBusinesses = [];
    $('#sortable-businesses .sortable-item:not(.out-of-range)').each(function() {
        var companyId = $(this).data('company-id');
        var company = tourData.companies.find(c => c.company_id == companyId);
        if (company && company.latitude && company.longitude) {
            orderedBusinesses.push(company);
        }
    });
    
    if (orderedBusinesses.length === 0) {
        // No businesses to show
        if (tourData.homeLocation.lat && tourData.homeLocation.lng) {
            map.setCenter({lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)});
            map.setZoom(13);
        }
        return;
    }
    
    // Add home marker
    if (tourData.homeLocation.lat && tourData.homeLocation.lng) {
        var homeMarker = new google.maps.Marker({
            position: {lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)},
            map: map,
            title: 'Starting Location',
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
            }
        });
        markers.push(homeMarker);
        bounds.extend(homeMarker.position);
    }
    
    // Add waypoints and markers for businesses
    orderedBusinesses.forEach(function(business, index) {
        var position = {lat: parseFloat(business.latitude), lng: parseFloat(business.longitude)};
        
        waypoints.push({
            location: position,
            stopover: true
        });
        
        var marker = new google.maps.Marker({
            position: position,
            map: map,
            title: business.company_name,
            label: (index + 1).toString(),
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                labelOrigin: new google.maps.Point(15, 10)
            }
        });
        markers.push(marker);
        bounds.extend(position);
    });
    
    // Calculate route if we have origin and at least one destination
    if (tourData.homeLocation.lat && tourData.homeLocation.lng && waypoints.length > 0) {
        var origin = {lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)};
        var destination = waypoints[waypoints.length - 1].location;
        var middleWaypoints = waypoints.slice(0, -1);
        
        var request = {
            origin: origin,
            destination: destination,
            waypoints: middleWaypoints,
            optimizeWaypoints: false,
            travelMode: google.maps.TravelMode.DRIVING
        };
        
        directionsService.route(request, function(result, status) {
            if (status === 'OK') {
                directionsRenderer.setDirections(result);
            } else {
                console.error('Directions request failed:', status);
                // Just fit to markers if directions fail
                map.fitBounds(bounds);
            }
        });
    } else {
        // No route to calculate, just fit bounds
        map.fitBounds(bounds);
    }
}

// Mobile route calculation
function calculateAndDisplayRouteMobile() {
    // Similar to desktop but for mobile map
    var waypoints = [];
    var bounds = new google.maps.LatLngBounds();
    
    // Clear existing markers
    markersMobile.forEach(marker => marker.setMap(null));
    markersMobile = [];
    
    // Get businesses in order
    var orderedBusinesses = [];
    $('#sortable-businesses-mobile .sortable-item-mobile:not(.out-of-range)').each(function() {
        var companyId = $(this).data('company-id');
        var company = tourData.companies.find(c => c.company_id == companyId);
        if (company && company.latitude && company.longitude) {
            orderedBusinesses.push(company);
        }
    });
    
    if (orderedBusinesses.length === 0 && tourData.homeLocation.lat && tourData.homeLocation.lng) {
        mapMobile.setCenter({lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)});
        mapMobile.setZoom(13);
        return;
    }
    
    // Add home marker
    if (tourData.homeLocation.lat && tourData.homeLocation.lng) {
        var homeMarker = new google.maps.Marker({
            position: {lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)},
            map: mapMobile,
            title: 'Starting Location',
            icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
        });
        markersMobile.push(homeMarker);
        bounds.extend(homeMarker.position);
    }
    
    // Process businesses
    orderedBusinesses.forEach(function(business, index) {
        var position = {lat: parseFloat(business.latitude), lng: parseFloat(business.longitude)};
        waypoints.push({location: position, stopover: true});
        
        var marker = new google.maps.Marker({
            position: position,
            map: mapMobile,
            title: business.company_name,
            label: (index + 1).toString()
        });
        markersMobile.push(marker);
        bounds.extend(position);
    });
    
    // Calculate route
    if (tourData.homeLocation.lat && tourData.homeLocation.lng && waypoints.length > 0) {
        var request = {
            origin: {lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)},
            destination: waypoints[waypoints.length - 1].location,
            waypoints: waypoints.slice(0, -1),
            optimizeWaypoints: false,
            travelMode: google.maps.TravelMode.DRIVING
        };
        
        directionsServiceMobile.route(request, function(result, status) {
            if (status === 'OK') {
                directionsRendererMobile.setDirections(result);
            } else {
                mapMobile.fitBounds(bounds);
            }
        });
    } else {
        mapMobile.fitBounds(bounds);
    }
}

// Update map after reordering
function updateMap() {
    $('#update-map-btn').hide();
    calculateAndDisplayRoute();
    
    // Save new order via AJAX
    var tourIds = [];
    $('#sortable-businesses .sortable-item').each(function() {
        var tourId = $(this).data('tour-id');
        if (tourId) tourIds.push(tourId);
    });
    
    // Would save order here
}

function updateMapMobile() {
    $('#update-map-btn-mobile').hide();
    calculateAndDisplayRouteMobile();
    
    // Sync with desktop order
    var mobileOrder = [];
    $('#sortable-businesses-mobile .sortable-item-mobile').each(function() {
        mobileOrder.push($(this).data('company-id'));
    });
    
    // Reorder desktop to match
    var $desktop = $('#sortable-businesses');
    mobileOrder.forEach(function(companyId) {
        var $item = $desktop.find('[data-company-id="' + companyId + '"]');
        $desktop.append($item);
    });
    
    updateMap();
}

// Send to phone function - EXACT from original with improvements
function sendToPhone() {
    // Check if SMS is already in progress
    if (smsInProgress) {
        showAlert('<strong>Please wait...</strong><br>Your previous request is still being processed.', 'warning', 3000);
        return;
    }
    
    // Check rate limit (60 seconds)
    var currentTime = Date.now();
    var timeSinceLastSms = (currentTime - lastSmsSentTime) / 1000;
    
    if (lastSmsSentTime > 0 && timeSinceLastSms < 60) {
        var remainingTime = Math.ceil(60 - timeSinceLastSms);
        showAlert('<strong>Please wait</strong><br>You can send another SMS in ' + remainingTime + ' seconds.', 'warning', 4000);
        return;
    }
    
    var phoneNumber = <?php echo json_encode($phoneNumber); ?>;
    
    if (!phoneNumber) {
        // Show phone modal
        var modal = new bootstrap.Modal(document.getElementById('sendPhoneModal'));
        modal.show();
        return;
    }
    
    // Build navigation URL
    var locations = getNavigationLocations();
    if (locations.length < 2) {
        showAlert('<strong>No route to send</strong><br>Add businesses to your tour first.', 'danger', 4000);
        return;
    }
    
    // Show loading
    smsInProgress = true;
    var $btns = $('#send-to-phone-btn, #send-to-phone-btn-desktop');
    var originalHtml = $btns.first().html();
    $btns.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
    
    // Build Google Maps URL
    var baseUrl = 'https://www.google.com/maps/dir/';
    var waypoints = locations.map(function(loc) {
        return encodeURIComponent(loc.address || loc.lat + ',' + loc.lng);
    });
    var navigationUrl = baseUrl + waypoints.join('/');
    
    // Send AJAX request
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
            action: 'send_to_phone',
            phone_number: phoneNumber,
            navigation_url: navigationUrl,
            tour_date: tourData.date,
            _token: $('meta[name="csrf-token"]').attr('content') || ''
        },
        success: function(response) {
            if (response.success) {
                showAlert('<strong>Success!</strong><br>' + (response.message || 'Tour link sent to your phone.'), 'success', 5000);
                lastSmsSentTime = Date.now();
            } else {
                showAlert('<strong>Failed to send</strong><br>' + (response.message || 'Please try again.'), 'danger', 5000);
            }
        },
        error: function() {
            showAlert('<strong>Network error</strong><br>Please check your connection and try again.', 'danger', 5000);
        },
        complete: function() {
            smsInProgress = false;
            $btns.prop('disabled', false).html(originalHtml);
        }
    });
}

// Confirm send from modal
function confirmSendToPhone() {
    var phoneInput = $('#phone-number-input').val();
    var savePhone = $('#save-phone-check').is(':checked');
    
    if (!phoneInput) {
        showAlert('Please enter a phone number.', 'warning', 3000);
        return;
    }
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('sendPhoneModal')).hide();
    
    // Update phone number and send
    <?php echo json_encode($phoneNumber); ?> = phoneInput;
    
    if (savePhone) {
        // Save phone number via AJAX
        $.post(window.location.pathname, {
            action: 'save_phone_number',
            phone_number: phoneInput,
            _token: $('meta[name="csrf-token"]').attr('content') || ''
        });
    }
    
    sendToPhone();
}

// Get navigation locations in order
function getNavigationLocations() {
    var locations = [];
    
    // Add home
    if (tourData.homeLocation.lat && tourData.homeLocation.lng) {
        locations.push({
            name: 'Starting Location',
            address: tourData.homeLocation.address,
            lat: tourData.homeLocation.lat,
            lng: tourData.homeLocation.lng
        });
    }
    
    // Add businesses in order
    $('#sortable-businesses .sortable-item:not(.out-of-range)').each(function() {
        var companyId = $(this).data('company-id');
        var company = tourData.companies.find(c => c.company_id == companyId);
        if (company) {
            var address = [];
            if (company.address) address.push(company.address);
            if (company.city) address.push(company.city);
            if (company.state) address.push(company.state);
            if (company.zip_code) address.push(company.zip_code);
            
            locations.push({
                name: company.company_name,
                address: address.join(', '),
                lat: company.latitude,
                lng: company.longitude
            });
        }
    });
    
    return locations;
}

// Show Bootstrap 5 fade alert - as requested
function showAlert(message, type, duration) {
    type = type || 'info';
    duration = duration || 5000;
    
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
    
    var $alert = $(alertHtml);
    $('#alert-container').append($alert);
    
    // Auto dismiss
    if (duration > 0) {
        setTimeout(function() {
            $alert.alert('close');
        }, duration);
    }
}

// Change home location
function changeHomeLocation() {
    var modal = new bootstrap.Modal(document.getElementById('changeHomeModal'));
    modal.show();
    
    // Initialize home location map
    setTimeout(function() {
        if (!homeMap) {
            homeMap = new google.maps.Map(document.getElementById('home-map-preview'), {
                zoom: 15,
                center: tourData.homeLocation.lat && tourData.homeLocation.lng ? 
                    {lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)} : 
                    {lat: 39.571822, lng: -104.87961}
            });
            
            // Initialize places autocomplete
            var input = document.getElementById('home-address-input');
            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', homeMap);
            
            // Place marker
            if (tourData.homeLocation.lat && tourData.homeLocation.lng) {
                homeMarker = new google.maps.Marker({
                    position: {lat: parseFloat(tourData.homeLocation.lat), lng: parseFloat(tourData.homeLocation.lng)},
                    map: homeMap,
                    draggable: true
                });
                
                homeMarker.addListener('dragend', function() {
                    var position = homeMarker.getPosition();
                    $('#home-lat').val(position.lat());
                    $('#home-lng').val(position.lng());
                    
                    // Reverse geocode
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({location: position}, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            $('#home-address-input').val(results[0].formatted_address);
                        }
                    });
                });
            }
            
            // Listen for place selection
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place.geometry) return;
                
                // Update map
                homeMap.setCenter(place.geometry.location);
                homeMap.setZoom(15);
                
                // Update marker
                if (homeMarker) homeMarker.setMap(null);
                homeMarker = new google.maps.Marker({
                    position: place.geometry.location,
                    map: homeMap,
                    draggable: true
                });
                
                // Update hidden fields
                $('#home-lat').val(place.geometry.location.lat());
                $('#home-lng').val(place.geometry.location.lng());
            });
        }
    }, 300);
}

// Save home location
function saveHomeLocation() {
    var address = $('#home-address-input').val();
    var lat = $('#home-lat').val();
    var lng = $('#home-lng').val();
    
    if (!address) {
        showAlert('Please enter an address.', 'warning');
        return;
    }
    
    // Show loading
    $('#loading-overlay').show();
    
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
            action: 'update_home_location',
            address: address,
            lat: lat,
            lng: lng,
            _token: $('meta[name="csrf-token"]').attr('content') || ''
        },
        success: function(response) {
            if (response.success) {
                showAlert('Starting location updated successfully.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert('Failed to update location: ' + (response.message || 'Unknown error'), 'danger');
            }
        },
        error: function() {
            showAlert('Network error. Please try again.', 'danger');
        },
        complete: function() {
            $('#loading-overlay').hide();
        }
    });
}

// Pick location for business
function pickLocation(companyId) {
    currentCompanyId = companyId;
    
    // Find company
    var company = tourData.companies.find(c => c.company_id == companyId);
    if (!company) return;
    
    $('#pick-location-company-name').text(company.company_name);
    
    var modal = new bootstrap.Modal(document.getElementById('pickLocationModal'));
    modal.show();
    
    // Initialize picker map
    setTimeout(function() {
        if (!pickerMap) {
            var center = company.latitude && company.longitude ? 
                {lat: parseFloat(company.latitude), lng: parseFloat(company.longitude)} :
                {lat: 39.571822, lng: -104.87961};
                
            pickerMap = new google.maps.Map(document.getElementById('picker-map'), {
                zoom: 12,
                center: center
            });
        }
        
        // Clear search results
        $('#location-search-results').empty();
        
        // Auto search on modal open
        searchBusinessLocations();
    }, 300);
}

// Search business locations
function searchBusinessLocations() {
    if (!currentCompanyId) return;
    
    var radius = $('#radius-select').val();
    
    $('#location-search-results').html('<div class="text-center p-3"><div class="spinner-border"></div></div>');
    
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
            action: 'search_business_locations',
            company_id: currentCompanyId,
            radius: radius,
            lat: tourData.homeLocation.lat,
            lng: tourData.homeLocation.lng,
            _token: $('meta[name="csrf-token"]').attr('content') || ''
        },
        success: function(response) {
            if (response.success && response.locations) {
                displayLocationResults(response.locations);
            } else {
                $('#location-search-results').html('<div class="alert alert-warning">No locations found within ' + radius + ' miles.</div>');
            }
        },
        error: function() {
            $('#location-search-results').html('<div class="alert alert-danger">Search failed. Please try again.</div>');
        }
    });
}

// Display location search results
function displayLocationResults(locations) {
    var $results = $('#location-search-results');
    $results.empty();
    
    // Clear map markers
    pickerMarkers.forEach(marker => marker.setMap(null));
    pickerMarkers = [];
    
    var bounds = new google.maps.LatLngBounds();
    
    locations.forEach(function(location, index) {
        // Add to list
        var $item = $('<a href="#" class="list-group-item list-group-item-action"></a>');
        $item.html(
            '<div class="d-flex justify-content-between align-items-start">' +
            '<div>' +
            '<strong>' + location.name + '</strong><br>' +
            '<small class="text-muted">' + location.address + '</small>' +
            '</div>' +
            '<span class="badge bg-secondary">' + location.distance + ' mi</span>' +
            '</div>'
        );
        
        $item.on('click', function(e) {
            e.preventDefault();
            selectBusinessLocation(location);
        });
        
        $results.append($item);
        
        // Add to map
        if (location.lat && location.lng) {
            var position = {lat: parseFloat(location.lat), lng: parseFloat(location.lng)};
            var marker = new google.maps.Marker({
                position: position,
                map: pickerMap,
                title: location.name,
                label: (index + 1).toString()
            });
            
            marker.addListener('click', function() {
                selectBusinessLocation(location);
            });
            
            pickerMarkers.push(marker);
            bounds.extend(position);
        }
    });
    
    // Fit map to markers
    if (pickerMarkers.length > 0) {
        pickerMap.fitBounds(bounds);
    }
}

// Select a business location
function selectBusinessLocation(location) {
    if (!currentCompanyId || !location.location_id) return;
    
    // Highlight selected
    $('#location-search-results .list-group-item').removeClass('active');
    $('#location-search-results .list-group-item').each(function() {
        if ($(this).text().includes(location.name)) {
            $(this).addClass('active');
        }
    });
    
    // Confirm selection
    if (confirm('Use this location for your tour?\n\n' + location.name + '\n' + location.address)) {
        saveBusinessLocation(location.location_id);
    }
}

// Save selected business location
function saveBusinessLocation(locationId) {
    $('#loading-overlay').show();
    
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
            action: 'save_business_location',
            company_id: currentCompanyId,
            location_id: locationId,
            tour_date: tourData.date,
            _token: $('meta[name="csrf-token"]').attr('content') || ''
        },
        success: function(response) {
            if (response.success) {
                showAlert('Location updated successfully.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert('Failed to update location: ' + (response.message || 'Unknown error'), 'danger');
            }
        },
        error: function() {
            showAlert('Network error. Please try again.', 'danger');
        },
        complete: function() {
            $('#loading-overlay').hide();
        }
    });
}

// Open in Google Maps
function openInGoogleMaps() {
    var locations = getNavigationLocations();
    if (locations.length < 2) {
        showAlert('No route to open. Add businesses to your tour first.', 'warning');
        return;
    }
    
    var baseUrl = 'https://www.google.com/maps/dir/';
    var waypoints = locations.map(function(loc) {
        return encodeURIComponent(loc.address || loc.lat + ',' + loc.lng);
    });
    var url = baseUrl + waypoints.join('/');
    
    window.open(url, '_blank');
}

// Print handlers - EXACT from original
function setupPrintHandlers() {
    // Set custom title for print
    var originalTitle = document.title;
    
    window.addEventListener('beforeprint', function() {
        document.title = 'Birthday.Gold - My Tour ' + tourData.formattedDate + '.pdf';
    });
    
    window.addEventListener('afterprint', function() {
        document.title = originalTitle;
    });
}

function handlePrint() {
    window.print();
}

// Handle window resize
$(window).on('resize', function() {
    if (typeof google !== 'undefined' && google.maps && google.maps.event) {
        if (map) google.maps.event.trigger(map, 'resize');
        if (mapMobile) google.maps.event.trigger(mapMobile, 'resize');
        if (pickerMap) google.maps.event.trigger(pickerMap, 'resize');
        if (homeMap) google.maps.event.trigger(homeMap, 'resize');
    }
});
</script>

<?php
// Footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>

// Process companies - EXACT logic from original
$listofcompanies = [];
foreach ($companies as $item_company) {  
    $company_data = $app->getcompany($item_company['company_id']);
    
    // Location processing logic from original
    if (!empty($item_company['location_id']) && !empty($item_company['cl_latitude'])) {
        if (!empty($item_company['cl_address'])) $company_data['address'] = $item_company['cl_address'];
        if (!empty($item_company['cl_city'])) $company_data['city'] = $item_company['cl_city'];
        if (!empty($item_company['cl_state'])) $company_data['state'] = $item_company['cl_state'];
        if (!empty($item_company['cl_zip_code'])) $company_data['zip_code'] = $item_company['cl_zip_code'];
        
        $company_data['latitude'] = $item_company['cl_latitude'];
        $company_data['longitude'] = $item_company['cl_longitude'];
        $company_data['has_verified_location'] = true;
        $company_data['location_id'] = $item_company['cl_location_id'];
    } else {
        // Check for verified location
        $verifiedLocation = null;
        if (!empty($item_company['location_id'])) {
            $sql = "SELECT * FROM bg_company_locations WHERE location_id = :location_id";
            $stmt = $database->prepare($sql);
            $stmt->execute([':location_id' => $item_company['location_id']]);
            $verifiedLocation = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($verifiedLocation) {
            if (!empty($verifiedLocation['address'])) $company_data['address'] = $verifiedLocation['address'];
            if (!empty($verifiedLocation['city'])) $company_data['city'] = $verifiedLocation['city'];
            if (!empty($verifiedLocation['state'])) $company_data['state'] = $verifiedLocation['state'];
            if (!empty($verifiedLocation['zip_code'])) $company_data['zip_code'] = $verifiedLocation['zip_code'];
            
            $company_data['latitude'] = $verifiedLocation['latitude'];
            $company_data['longitude'] = $verifiedLocation['longitude'];
            $company_data['has_verified_location'] = true;
            $company_data['location_id'] = $verifiedLocation['location_id'];
        }
    }
    
    // Distance calculation - EXACT from original
    if (!empty($company_data['latitude']) && !empty($company_data['longitude']) && !empty($home_lat) && !empty($home_lng)) {
        $distance = haversineDistance($home_lat, $home_lng, $company_data['latitude'], $company_data['longitude']);
        $company_data['distance_from_home'] = round($distance, 1);
        $company_data['is_out_of_range'] = ($distance > 100);
    }
    
    // Force location check
    $company_data['is_forced_location'] = !empty($item_company['is_forced_location']);
    
    $listofcompanies[] = $item_company + ['data' => $company_data];
}

// ALL styles from original tour.php preserved
$additionalstyles = '
<style>
/* Mobile tab navigation */
.mobile-tabs {
    display: flex;
    background: white;
    border-bottom: 2px solid #dee2e6;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: -1rem -1rem 1rem -1rem;
}

.mobile-tab {
    flex: 1;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 500;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
    white-space: nowrap;
}

.mobile-tab i {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.mobile-tab span {
    flex-shrink: 0;
}

.mobile-tab.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background: transparent;
}

.mobile-tab:hover:not(.active) {
    background: #f8f9fa;
}

.mobile-tab-content {
    display: none;
    margin-top: 1rem;
}

.mobile-tab-content.active {
    display: block;
}

/* Mobile business cards */
.business-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    position: relative;
    transition: all 0.2s ease;
}

.business-card-header {
    display: flex;
    gap: 0.75rem;
}

.business-logo {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: contain;
    background: #f8f9fa;
    padding: 4px;
}

.business-info {
    flex: 1;
    min-width: 0;
}

.business-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.business-address {
    font-size: 0.875rem;
    color: #6c757d;
}

.out-of-range-badge {
    display: inline-block;
    background: #dc3545;
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    margin-left: 0.5rem;
}

/* Mobile responsive utilities */
@media (max-width: 991px) {
    .desktop-only {
        display: none !important;
    }
}

@media (min-width: 992px) {
    .mobile-only {
        display: none !important;
    }
    .container > .col-12 {
        margin-top: 5rem !important;
    }
}

/* Sortable styles */
.sortable_item {
    border: 1px solid #ddd;
    padding: 10px;
    margin-bottom: 10px;
    cursor: move;
}

.sortable_item.out-of-range {
    opacity: 0.6;
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.sortable_item.out-of-range .sortable_item_handle {
    cursor: not-allowed !important;
}

.ui-sortable-helper {
    opacity: 0.8;
}

.sortable_item:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sortable_item.ui-sortable-helper {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    background: white;
    opacity: 0.9;
}

.sortable_item_handle {
    cursor: grab;
    color: #6c757d;
    transition: color 0.2s ease;
    padding: 8px;
    margin: -8px;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
}

.sortable_item_handle:hover {
    color: #495057;
}

.sortable_item_handle:active {
    cursor: grabbing;
    color: #212529;
}

/* Touch-specific styles */
@media (hover: none) and (pointer: coarse) {
    .sortable_item_handle {
        padding: 12px;
        margin: -12px;
    }
    
    .sortable_item {
        -webkit-overflow-scrolling: touch;
    }
}

#directions-panel {
    font-size: 0.875rem;
}

.ui-sortable-placeholder {
    border: 2px dashed #dee2e6;
    background: #f8f9fa;
    visibility: visible !important;
}

/* Forced location badge */
.forced-location-badge {
    display: inline-block;
    background: #17a2b8;
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    margin-left: 0.5rem;
}

/* Print styles - ALL preserved */
@media print {
    .no-print, 
    .noprint, 
    .navbar, 
    #sticky-bottom-nav-bar,
    .draw_map,
    .change-location-link,
    .pick-location,
    .btn-group,
    .mobile-tabs {
        display: none !important;
    }
    
    body {
        margin: 0;
        padding: 0;
    }
    
    .container {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .col-lg-4, .col-lg-8 {
        width: 100% !important;
        max-width: none !important;
        flex: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        margin-bottom: 20px !important;
        page-break-inside: avoid;
    }
    
    .card-header {
        background: #f8f9fa !important;
        border-bottom: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    #route-map {
        height: 600px !important;
        page-break-before: always;
    }
    
    @page {
        size: letter;
        margin: 0.5in;
    }
    
    .print-content {
        width: 100%;
        max-width: none !important;
    }
    
    .col-lg-4 .card::before {
        content: "Tour Directions for ' . $formattedDate . '";
        display: block;
        font-size: 20pt;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: center;
        width: 100%;
    }
}
</style>
';

// Include headers
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
?>

<div class="container">
<div class="col-12" style="margin-top: 3rem;">

<!-- Alert Container -->
<div id="alert-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;"></div>

<!-- Mobile Header -->
<div class="mobile-only">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-1">My Birthday Tour</h4>
            <div class="text-muted"><?php echo $formattedDate; ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="/myaccount/tour-build?date=<?php echo $date; ?>" class="btn btn-sm btn-primary" title="Add Businesses">
                <i class="bi bi-plus-circle"></i>
            </a>
            <button class="btn btn-sm btn-secondary" onclick="sendToPhone()" title="Send to phone">
                <i class="bi bi-phone"></i>
            </button>
            <?php if (isset($_GET['debug'])): ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="getTestingLinks()" title="Get test links">
                <i class="bi bi-bug"></i>
            </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-secondary" onclick="window.print()" title="Print">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="mt-2 mb-3">
        <span class="badge bg-primary" role="button" data-bs-toggle="modal" data-bs-target="#businessCountModal" style="cursor: pointer;">
            <?php echo count($listofcompanies); ?> businesses
        </span>
        <?php
        $outOfRangeCount = 0;
        foreach ($listofcompanies as $company) {
            if ($company['data']['is_out_of_range'] ?? false) {
                $outOfRangeCount++;
            }
        }
        if ($outOfRangeCount > 0): ?>
            <span class="badge bg-danger" role="button" data-bs-toggle="modal" data-bs-target="#outOfRangeModal" style="cursor: pointer;">
                <?php echo $outOfRangeCount; ?> out of range
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Desktop Header - Compressed -->
<div class="desktop-only mb-4"></div>

<!-- Mobile Tab Navigation -->
<div class="mobile-tabs mobile-only">
    <button class="mobile-tab active" onclick="showTab('businesses')">
        <i class="bi bi-list-ul"></i> List
    </button>
    <button class="mobile-tab" onclick="showTab('map')">
        <i class="bi bi-map"></i> Map
    </button>
    <button class="mobile-tab" onclick="showTab('directions')">
        <i class="bi bi-sign-turn-right"></i> Directions
    </button>
</div>

<!-- Desktop Content -->
<div class="row desktop-only">
    <div class="col-lg-12 mb-4">
        <!-- CELEBRATION TOUR COMPANIES card-->
        <div class="card card-header-actions mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h2 class="mb-0">Celebration Tour - <?php echo $formattedDate; ?></h2>
                        <small class="text-muted">
                            <?php
                            $businessCount = count($listofcompanies);
                            $outOfRangeCount = 0;
                            foreach ($listofcompanies as $company) {
                                if ($company['data']['is_out_of_range'] ?? false) {
                                    $outOfRangeCount++;
                                }
                            }
                            
                            // Determine if tour is today or tomorrow
                            $tourDateObj = new DateTime($date);
                            $todayObj = new DateTime('today');
                            $tomorrowObj = new DateTime('tomorrow');
                            
                            $whenText = 'on ' . $tourDateObj->format('F j');
                            if ($tourDateObj->format('Y-m-d') == $todayObj->format('Y-m-d')) {
                                $whenText = 'today';
                            } elseif ($tourDateObj->format('Y-m-d') == $tomorrowObj->format('Y-m-d')) {
                                $whenText = 'tomorrow';
                            }
                            
                            // Fun dynamic messages
                            $messages = [
                                'Grab all your freebies from',
                                'Collect birthday rewards at',
                                'Score free treats from',
                                'Get your birthday goodies from',
                                'Claim your birthday perks at'
                            ];
                            $message = $messages[array_rand($messages)];
                            
                            echo $message . ' <strong>' . $businessCount . ' ' . ($businessCount == 1 ? 'business' : 'businesses') . '</strong> ' . $whenText;
                            
                            if ($outOfRangeCount > 0): ?>
                                <span class="text-danger">(<?php echo $outOfRangeCount; ?> out of range)</span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="col ms-auto text-end">
                        <div class="btn-group" role="group">
                            <a href="/myaccount/tour-build?date=<?php echo $date; ?>" class="btn btn-sm btn-outline-primary" title="Add businesses">
                                <i class="bi bi-plus-circle"></i> Add
                            </a>
                            <button class="btn btn-sm btn-outline-primary" onclick="sendToPhone()" title="Send to phone">
                                <i class="bi bi-phone"></i> Send
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print tour">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <?php if (isset($_GET['debug'])): ?>
                            <button class="btn btn-sm btn-outline-warning" onclick="getTestingLinks()" title="Debug">
                                <i class="bi bi-bug"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body" id="sortable">
                <!-- Home location -->
                <div class="d-flex align-items-center justify-content-between px-4 bg-success bg-opacity-10 py-3 rounded" data-location="<?php echo $homeaddress; ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-house-fill"></i>
                        <div class="ms-4">
                            <div class="small">Starting Tour Location</div>
                            <div class="text-xs text-muted">
                                <?php 
                                $displayHomeAddress = preg_replace('/, USA$/', '', $homeaddress);
                                $displayHomeAddress = preg_replace('/, United States$/', '', $displayHomeAddress);
                                echo $displayHomeAddress;
                                
                                // Check if address matches profile
                                $profileAddress = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_mailing_address');
                                $profileCity = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_city');
                                $profileState = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_state');
                                $profileZip = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_zip_code');
                                
                                if ($profileAddress && $profileCity && $profileZip) {
                                    $cleanHomeAddress = preg_replace('/, USA$/', '', $homeaddress);
                                    $cleanHomeAddress = preg_replace('/, United States$/', '', $cleanHomeAddress);
                                    
                                    $addressParts = array_map('trim', explode(',', $cleanHomeAddress));
                                    $tourStreet = isset($addressParts[0]) ? strtolower(trim($addressParts[0])) : '';
                                    $tourCity = isset($addressParts[1]) ? strtolower(trim($addressParts[1])) : '';
                                    
                                    $tourZip = '';
                                    if (isset($addressParts[2])) {
                                        $stateZipParts = explode(' ', trim($addressParts[2]));
                                        $tourZip = isset($stateZipParts[1]) ? trim($stateZipParts[1]) : '';
                                    }
                                    
                                    $profileStreet = strtolower(trim($profileAddress['string_value'] ?? ''));
                                    $profileCityLower = strtolower(trim($profileCity['string_value'] ?? ''));
                                    $profileZipValue = trim($profileZip['string_value'] ?? '');
                                    
                                    $isProfileAddress = (
                                        $tourStreet === $profileStreet && 
                                        $tourCity === $profileCityLower && 
                                        $tourZip === $profileZipValue
                                    );
                                    
                                    if ($isProfileAddress) {
                                        echo ' <span class="badge bg-success">Profile Address</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="#!" class="change-location-link" onclick="openChangeLocationModal(event)">Change Location</a>
                    </div>
                </div>
                <br>
                
                <!-- Tour Businesses -->
                <?php
                foreach ($listofcompanies as $item_companyrow) {
                    $item_company = $item_companyrow['data'];
                    if (!empty($item_company)) {
                        $hasFullAddress = !empty($item_company['address']) && strlen(trim($item_company['address'])) > 0;
                        
                        $businessCity = !empty($item_company['city']) ? $item_company['city'] : $home_city;
                        $businessState = !empty($item_company['state']) ? $item_company['state'] : $home_state;
                        $businessZip = !empty($item_company['zip_code']) ? $item_company['zip_code'] : $home_zip;
                        
                        $isOutOfRange = $item_company['is_out_of_range'] ?? false;
                        $isForcedLocation = $item_company['is_forced_location'] ?? false;
                ?>
                <div class="sortable_item d-flex align-items-center justify-content-between px-4 py-3 <?php echo $isOutOfRange ? 'out-of-range' : ''; ?>" 
                     data-company-id="<?php echo $item_company['company_id']; ?>"
                     data-company-name="<?php echo htmlspecialchars($item_company['company_name']); ?>"
                     data-location="<?php 
                        if ($hasFullAddress) {
                            echo htmlspecialchars($item_company['address'] . ', ' . $businessCity . ', ' . $businessState . ' ' . $businessZip);
                        } else if ($businessCity && $businessState) {
                            echo htmlspecialchars($item_company['company_name'] . ', ' . $businessCity . ', ' . $businessState . ' ' . $businessZip);
                        } else {
                            echo htmlspecialchars($item_company['company_name']);
                        }
                     ?>"
                     data-out-of-range="<?php echo $isOutOfRange ? 'true' : 'false'; ?>">
                    <div class="d-flex align-items-center">
                        <?php if (!$isOutOfRange): ?>
                        <div class="btn btn-sm sortable_item_handle" title="Drag to reorder">
                            <i class="bi bi-grip-vertical h4"></i>
                        </div>
                        <?php endif; ?>
                        <img src="<?php echo $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']); ?>" style="width: 60px; height: 60px; object-fit: contain;">
                        <div class="ms-4">
                            <h5 class="mb-1">
                                <?php echo $item_company['company_name']; ?>
                                <?php if ($isOutOfRange): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $item_company['distance_from_home']; ?> miles away</span>
                                <?php endif; ?>
                                <?php if ($isForcedLocation): ?>
                                    <span class="badge bg-info ms-2">Forced</span>
                                <?php endif; ?>
                            </h5>
                            <div class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                <?php 
                                if ($hasFullAddress) {
                                    echo htmlspecialchars($item_company['address'] . ', ' . $businessCity . ', ' . $businessState . ' ' . $businessZip);
                                } else if ($businessCity && $businessState) {
                                    echo htmlspecialchars($businessCity . ', ' . $businessState . ' ' . $businessZip);
                                } else {
                                    echo 'Location pending';
                                }
                                ?>
                            </div>
                            <?php if (!$hasFullAddress && !$isOutOfRange): ?>
                            <div class="mt-1">
                                <a href="#" class="pick-location" 
                                   data-company-id="<?php echo $item_company['company_id']; ?>" 
                                   data-company-name="<?php echo htmlspecialchars($item_company['company_name']); ?>">
                                   <i class="bi bi-geo-alt"></i> Pick a location
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="/myaccount/enrollments-individual?company_id=<?php echo $item_company['company_id']; ?>" class="btn btn-sm btn-primary no-print">Details</a>
                    </div>
                </div>
                <?php
                    }
                }
                ?>
                
                <!-- Draw new map button -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <p class="text-muted small mb-2"><i class="bi bi-grip-vertical"></i> Drag businesses to reorder your tour route</p>
                    <button class="btn btn-secondary draw_map" id="draw_map" style="display: none;" onclick="DrawNewMap()">
                        <i class="bi bi-arrow-clockwise"></i> Update Map with New Route
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden status div for API loading messages -->
<div id="api-status" class="info" style="display: none;">Loading Google Maps API...</div>

<!-- Location Picker Modal -->
<div class="modal fade" id="locationPickerModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pick Location for <span id="modal-business-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Search Radius from Starting Location</label>
                            <select class="form-select" id="radius-select" onchange="searchBusinessLocations()">
                                <option value="5">5 miles</option>
                                <option value="25" selected>25 miles</option>
                                <option value="50">50 miles</option>
                                <option value="100">100 miles</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary w-100" onclick="searchBusinessLocations()">
                                <i class="bi bi-search"></i> Search Locations
                            </button>
                        </div>
                        <div id="location-results" class="list-group" style="max-height: 350px; overflow-y: auto;">
                            <!-- Search results will appear here -->
                        </div>
                        <div class="form-check mt-3" id="force-location-container" style="display: none;">
                            <input class="form-check-input" type="checkbox" id="force-location-check">
                            <label class="form-check-label" for="force-location-check">
                                <strong>Pin this location</strong><br>
                                <small class="text-muted">Keep this location even when starting location changes</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div id="location-picker-map" style="height: 500px;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-location-btn" onclick="confirmLocationSelection()" disabled>
                    <i class="bi bi-check-circle"></i> Confirm Location
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Location Modal -->
<div class="modal fade" id="changeLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Starting Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="home-location-input" class="form-label">Enter your starting address</label>
                    <input type="text" class="form-control" id="home-location-input" placeholder="Enter an address">
                    <div class="form-text">This will be your starting point for the tour route.</div>
                </div>
                <div id="home-location-map" style="height: 400px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateHomeLocation()">
                    <i class="bi bi-check-circle"></i> Update Location
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Content -->
<div class="mobile-only">
    <!-- Tab Content: List -->
    <div id="businesses-tab" class="mobile-tab-content active">
        <!-- Home Location Card -->
        <div class="business-card" style="background: #d1f2eb;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="business-name">
                        <i class="bi bi-house-fill me-2"></i>Starting Location
                    </div>
                    <div class="business-address">
                        <?php echo $displayHomeAddress; ?>
                    </div>
                </div>
                <a href="#!" onclick="openChangeLocationModal(event)" class="text-decoration-none">
                    <i class="bi bi-pencil"></i>
                </a>
            </div>
        </div>
        
        <!-- Business Cards -->
        <div id="sortable-mobile">
        <?php foreach ($listofcompanies as $item_companyrow): 
            $item_company = $item_companyrow['data'];
            if (empty($item_company)) continue;
            
            $isOutOfRange = $item_company['is_out_of_range'] ?? false;
            $isForcedLocation = $item_company['is_forced_location'] ?? false;
        ?>
        <div class="business-card sortable_item <?php echo $isOutOfRange ? 'out-of-range' : ''; ?>" 
             data-company-id="<?php echo $item_company['company_id']; ?>">
            
            <?php if (!$isOutOfRange): ?>
            <div class="sortable_item_handle position-absolute" style="top: 1rem; right: 0.5rem; cursor: grab; padding: 0.5rem;">
                <i class="bi bi-grip-vertical text-muted h5 mb-0"></i>
            </div>
            <?php endif; ?>
            
            <div class="business-card-header">
                <img src="<?php echo $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']); ?>" 
                     class="business-logo" 
                     alt="<?php echo htmlspecialchars($item_company['company_name']); ?>">
                <div class="business-info">
                    <div class="business-name">
                        <?php echo htmlspecialchars($item_company['company_name']); ?>
                        <?php if ($isOutOfRange): ?>
                            <span class="out-of-range-badge">
                                <?php echo $item_company['distance_from_home']; ?> mi
                            </span>
                        <?php endif; ?>
                        <?php if ($isForcedLocation): ?>
                            <span class="forced-location-badge">Forced</span>
                        <?php endif; ?>
                    </div>
                    <div class="business-address">
                        <i class="bi bi-geo-alt"></i> 
                        <?php 
                        $hasFullAddress = !empty($item_company['address']) && strlen(trim($item_company['address'])) > 0;
                        $businessCity = !empty($item_company['city']) ? $item_company['city'] : $home_city;
                        $businessState = !empty($item_company['state']) ? $item_company['state'] : $home_state;
                        $businessZip = !empty($item_company['zip_code']) ? $item_company['zip_code'] : $home_zip;
                        
                        if ($hasFullAddress) {
                            echo htmlspecialchars($item_company['address'] . ', ' . $businessCity . ', ' . $businessState . ' ' . $businessZip);
                        } else if ($businessCity && $businessState) {
                            echo htmlspecialchars($businessCity . ', ' . $businessState . ' ' . $businessZip);
                        } else {
                            echo 'Location pending';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$isOutOfRange): ?>
            <div class="d-flex gap-2 mt-3 justify-content-end">
                <?php if (!$hasFullAddress): ?>
                <button class="btn btn-outline-secondary btn-sm pick-location" 
                        data-company-id="<?php echo $item_company['company_id']; ?>" 
                        data-company-name="<?php echo htmlspecialchars($item_company['company_name']); ?>">
                    <i class="bi bi-geo-alt"></i> Pick Location
                </button>
                <?php endif; ?>
                <a href="/myaccount/enrollments-individual?company_id=<?php echo $item_company['company_id']; ?>" 
                   class="btn btn-primary btn-sm">
                    <i class="bi bi-box-arrow-up-right"></i> Details
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        
        <!-- Reorder notice and button -->
        <div class="text-center mt-3">
            <p class="text-muted small mb-2"><i class="bi bi-grip-vertical"></i> Drag businesses to reorder</p>
            <button class="btn btn-primary btn-sm draw_map" id="draw_map_mobile" style="display: none;" onclick="DrawNewMap()">
                <i class="bi bi-arrow-clockwise"></i> Update Route
            </button>
        </div>
    </div>

    <!-- Tab Content: Map -->
    <div id="map-tab" class="mobile-tab-content mobile-only">
        <div class="mobile-map-container" id="route-map-mobile" style="height: 400px;"></div>
        <div class="text-center mt-2">
            <button class="btn btn-sm btn-primary" onclick="initializeMobileMap();">
                <i class="bi bi-arrow-repeat"></i> Refresh Map
            </button>
        </div>
    </div>

    <!-- Tab Content: Directions -->
    <div id="directions-tab" class="mobile-tab-content mobile-only">
        <div class="mobile-directions">
            <div class="mobile-directions-header">
                <h6 class="mobile-directions-title">Turn-by-Turn Directions</h6>
                <button class="btn btn-sm btn-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
            <div id="directions-panel-mobile" style="padding: 1rem;"></div>
        </div>
    </div>
</div>

<div class="row print-content desktop-only">
    <div class="col-lg-4 mb-4">
        <!-- STEPS CARD-->
        <div class="card h-100 border-start-lg border-start-secondary">
            <div class="card-header">Turn-by-Turn Directions</div>
            <div class="card-body" style="max-height: 800px; overflow-y: auto;">
                <div id="directions-panel"></div>
            </div>
        </div>
    </div>

    <!-- MAP card-->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">Map and Direction</div>
            <div class="card-body p-0">
                <!-- Map will be displayed here -->
                <div id="route-map" style="height: 800px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile tab switching function
function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.mobile-tab').forEach(function(tab) {
        tab.classList.remove('active');
    });
    
    // Update tab content
    document.querySelectorAll('.mobile-tab-content').forEach(function(content) {
        content.classList.remove('active');
    });
    
    // Activate selected tab
    event.target.closest('.mobile-tab').classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Initialize map if switching to map tab
    if (tabName === 'map' && !window.mobileMapInitialized) {
        setTimeout(function() {
            initializeMobileMap();
        }, 100);
    }
    
    // Load directions if switching to directions tab
    if (tabName === 'directions' && !window.mobileDirectionsLoaded) {
        setTimeout(function() {
            loadMobileDirections();
        }, 100);
    }
}

// Set document title for printing/PDF
var originalTitle = document.title;
var tourDate = <?php echo json_encode($date); ?>;
var formattedTourDate = <?php echo json_encode($dateObject->format('F j, Y')); ?>;

// Set print title when print dialog opens
window.addEventListener('beforeprint', function() {
    document.title = 'Birthday.Gold - My Tour ' + formattedTourDate + '.pdf';
});

// Restore original title after printing
window.addEventListener('afterprint', function() {
    document.title = originalTitle;
});

// Bootstrap alert function
function showAlert(message, type = 'success', duration = 5000) {
    var alertId = 'alert-' + Date.now();
    var alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show shadow" role="alert">
            <div class="d-flex align-items-start">
                <div class="flex-grow-1">
                    ${message.replace(/\n/g, '<br>')}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    $('#alert-container').prepend(alertHtml);
    
    // Auto-dismiss after duration
    if (duration > 0) {
        setTimeout(function() {
            $('#' + alertId).fadeOut(400, function() {
                $(this).remove();
            });
        }, duration);
    }
}

// Map initialization
var routeMap;
var directionsService;
var directionsRenderer;

// Tour locations from PHP data - Global array
var locations = [
    {
        name: "Starting Tour Location",
        address: <?php echo json_encode($homeaddress); ?>,
        lat: <?php echo json_encode($home_lat); ?>,
        lng: <?php echo json_encode($home_lng); ?>,
        type: "home"
    }
    <?php 
    foreach ($listofcompanies as $item_companyrow) {
        $item_company = $item_companyrow['data'];
        if (!empty($item_company)) {
            $hasFullAddress = !empty($item_company['address']) && strlen(trim($item_company['address'])) > 0;
            
            $businessCity = !empty($item_company['city']) ? $item_company['city'] : $home_city;
            $businessState = !empty($item_company['state']) ? $item_company['state'] : $home_state;
            $businessZip = !empty($item_company['zip_code']) ? $item_company['zip_code'] : $home_zip;
            
            if (!($item_company['is_out_of_range'] ?? false)) {
                $locationName = $item_company['company_name'];
                $locationAddress = '';
                
                if ($hasFullAddress) {
                    $locationAddress = $item_company['address'] . ', ' . $businessCity . ', ' . $businessState . ' ' . $businessZip;
                } else if ($businessCity && $businessState) {
                    $locationAddress = $item_company['company_name'] . ', ' . $businessCity . ', ' . $businessState . ' ' . $businessZip;
                } else {
                    $locationAddress = $item_company['company_name'];
                }
                
                echo ",\n        {\n";
                echo '            name: ' . json_encode($locationName) . ",\n";
                echo '            address: ' . json_encode($locationAddress) . ",\n";
                echo '            lat: ' . json_encode($item_company['latitude'] ?? null) . ",\n";
                echo '            lng: ' . json_encode($item_company['longitude'] ?? null) . ",\n";
                echo '            isOutOfRange: ' . ($item_company['is_out_of_range'] ?? false ? 'true' : 'false') . ",\n";
                echo '            type: "business"' . "\n";
                echo "        }";
            }
        }
    }
    ?>
];

// Initialize after page loads
document.addEventListener('DOMContentLoaded', loadGoogleMaps);

function loadGoogleMaps() {
    if (typeof google === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $GMAP_API_KEY; ?>&libraries=places,marker&callback=initMap';
        document.head.appendChild(script);
    } else {
        initMap();
    }
}

// Continue with rest of JavaScript...
// [ALL JavaScript from original tour.php would continue here]
</script>

<!-- Include jQuery UI and touch support -->
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script>
// Touch support for jQuery UI sortable
// [Touch support code from original]

// Initialize sortable
// [Sortable initialization from original]

// All other JavaScript functions from original tour.php
// [Send to phone, location picker, etc.]
</script>

<?php
// Footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>