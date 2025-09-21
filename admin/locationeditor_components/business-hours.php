<?php
if (!isset($componentmode) || $componentmode != 'include') {
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php';
}

// Location data should be available from parent page
if (!isset($location) || !is_array($location) || !isset($location_id)) {
    echo '<div class="alert alert-danger">Location data not available</div>';
    exit;
}

// Parse existing business hours if available
$hours_data = [];
if (!empty($location['business_hours'])) {
    // Try to parse structured hours (JSON) or plain text
    $json_hours = json_decode($location['business_hours'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_hours)) {
        $hours_data = $json_hours;
    }
}

// Default hours structure
$days_of_week = [
    'monday' => 'Monday',
    'tuesday' => 'Tuesday', 
    'wednesday' => 'Wednesday',
    'thursday' => 'Thursday',
    'friday' => 'Friday',
    'saturday' => 'Saturday',
    'sunday' => 'Sunday'
];
?>

<div class="container-fluid px-0">
    <h3 class="mb-4">Business Hours</h3>
    
    <form id="businessHoursForm" method="POST" action="/admin/ajax/save-location-hours.php">
        <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        
        <!-- Quick Actions -->
        <div class="mb-4">
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="setStandardHours()">
                <i class="bi bi-clock me-1"></i>Set Standard Hours (9-5)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="setExtendedHours()">
                <i class="bi bi-moon me-1"></i>Extended Hours (9-9)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyHoursDown()">
                <i class="bi bi-files me-1"></i>Copy Monday to All
            </button>
        </div>
        
        <!-- Hours Grid -->
        <div class="hours-grid">
            <?php foreach ($days_of_week as $day_key => $day_name): ?>
                <?php 
                $day_hours = $hours_data[$day_key] ?? ['open' => '', 'close' => '', 'closed' => false];
                $is_closed = $day_hours['closed'] ?? false;
                ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <strong><?php echo $day_name; ?></strong>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input class="form-check-input closed-toggle" type="checkbox" 
                                           id="closed_<?php echo $day_key; ?>" 
                                           name="hours[<?php echo $day_key; ?>][closed]" 
                                           value="1" <?php echo $is_closed ? 'checked' : ''; ?>
                                           onchange="toggleDayHours('<?php echo $day_key; ?>')">
                                    <label class="form-check-label" for="closed_<?php echo $day_key; ?>">
                                        Closed
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-8" id="hours_<?php echo $day_key; ?>" <?php echo $is_closed ? 'style="display:none;"' : ''; ?>>
                                <div class="row">
                                    <div class="col-5">
                                        <div class="input-group">
                                            <span class="input-group-text">Open</span>
                                            <input type="time" class="form-control" 
                                                   name="hours[<?php echo $day_key; ?>][open]" 
                                                   value="<?php echo htmlspecialchars($day_hours['open'] ?? ''); ?>"
                                                   <?php echo $is_closed ? 'disabled' : ''; ?>>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="input-group">
                                            <span class="input-group-text">Close</span>
                                            <input type="time" class="form-control" 
                                                   name="hours[<?php echo $day_key; ?>][close]" 
                                                   value="<?php echo htmlspecialchars($day_hours['close'] ?? ''); ?>"
                                                   <?php echo $is_closed ? 'disabled' : ''; ?>>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="addSplitShift('<?php echo $day_key; ?>')" 
                                                title="Add split shift">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Split shifts container -->
                                <div id="splits_<?php echo $day_key; ?>" class="mt-2">
                                    <?php if (isset($day_hours['splits']) && is_array($day_hours['splits'])): ?>
                                        <?php foreach ($day_hours['splits'] as $idx => $split): ?>
                                            <div class="row mt-2 split-shift">
                                                <div class="col-5">
                                                    <input type="time" class="form-control" 
                                                           name="hours[<?php echo $day_key; ?>][splits][<?php echo $idx; ?>][open]" 
                                                           value="<?php echo htmlspecialchars($split['open'] ?? ''); ?>">
                                                </div>
                                                <div class="col-5">
                                                    <input type="time" class="form-control" 
                                                           name="hours[<?php echo $day_key; ?>][splits][<?php echo $idx; ?>][close]" 
                                                           value="<?php echo htmlspecialchars($split['close'] ?? ''); ?>">
                                                </div>
                                                <div class="col-2">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="this.closest('.split-shift').remove()">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Special Hours / Holidays -->
        <div class="mt-4">
            <h4>Special Hours & Holidays</h4>
            <div class="mb-3">
                <label class="form-label">Holiday/Special Hours Notes</label>
                <textarea class="form-control" name="special_hours_notes" rows="3" 
                          placeholder="e.g., Closed on Thanksgiving, Christmas Eve closes at 6pm"><?php echo htmlspecialchars($location['special_hours_notes'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <!-- Time Zone -->
        <div class="mb-4">
            <label class="form-label">Time Zone</label>
            <select class="form-select" name="timezone">
                <option value="">Use Company Default</option>
                <option value="America/New_York" <?php echo ($location['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                <option value="America/Chicago" <?php echo ($location['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                <option value="America/Denver" <?php echo ($location['timezone'] ?? '') === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                <option value="America/Phoenix" <?php echo ($location['timezone'] ?? '') === 'America/Phoenix' ? 'selected' : ''; ?>>Arizona Time</option>
                <option value="America/Los_Angeles" <?php echo ($location['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                <option value="America/Anchorage" <?php echo ($location['timezone'] ?? '') === 'America/Anchorage' ? 'selected' : ''; ?>>Alaska Time</option>
                <option value="Pacific/Honolulu" <?php echo ($location['timezone'] ?? '') === 'Pacific/Honolulu' ? 'selected' : ''; ?>>Hawaii Time</option>
            </select>
        </div>
        
        <!-- Display Format -->
        <div class="mb-4">
            <label class="form-label">Display Format</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="hours_format" id="format_12" value="12" checked>
                <label class="form-check-label" for="format_12">
                    12-hour format (9:00 AM - 5:00 PM)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="hours_format" id="format_24" value="24">
                <label class="form-check-label" for="format_24">
                    24-hour format (09:00 - 17:00)
                </label>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Hours displayed are in the location's local time zone
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Save Hours
            </button>
        </div>
    </form>
</div>

<script>
function toggleDayHours(day) {
    const checkbox = document.getElementById('closed_' + day);
    const hoursDiv = document.getElementById('hours_' + day);
    const inputs = hoursDiv.querySelectorAll('input[type="time"]');
    
    if (checkbox.checked) {
        hoursDiv.style.display = 'none';
        inputs.forEach(input => input.disabled = true);
    } else {
        hoursDiv.style.display = '';
        inputs.forEach(input => input.disabled = false);
    }
}

function setStandardHours() {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    days.forEach(day => {
        document.querySelector(`input[name="hours[${day}][open]"]`).value = '09:00';
        document.querySelector(`input[name="hours[${day}][close]"]`).value = '17:00';
        document.getElementById('closed_' + day).checked = false;
        toggleDayHours(day);
    });
    
    // Weekend closed
    ['saturday', 'sunday'].forEach(day => {
        document.getElementById('closed_' + day).checked = true;
        toggleDayHours(day);
    });
}

function setExtendedHours() {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    days.forEach(day => {
        document.querySelector(`input[name="hours[${day}][open]"]`).value = '09:00';
        document.querySelector(`input[name="hours[${day}][close]"]`).value = '21:00';
        document.getElementById('closed_' + day).checked = false;
        toggleDayHours(day);
    });
    
    // Sunday shorter hours
    document.querySelector('input[name="hours[sunday][open]"]').value = '11:00';
    document.querySelector('input[name="hours[sunday][close]"]').value = '18:00';
    document.getElementById('closed_sunday').checked = false;
    toggleDayHours('sunday');
}

function copyHoursDown() {
    const mondayOpen = document.querySelector('input[name="hours[monday][open]"]').value;
    const mondayClose = document.querySelector('input[name="hours[monday][close]"]').value;
    const mondayClosed = document.getElementById('closed_monday').checked;
    
    const days = ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    days.forEach(day => {
        document.querySelector(`input[name="hours[${day}][open]"]`).value = mondayOpen;
        document.querySelector(`input[name="hours[${day}][close]"]`).value = mondayClose;
        document.getElementById('closed_' + day).checked = mondayClosed;
        toggleDayHours(day);
    });
}

let splitCounter = 0;
function addSplitShift(day) {
    const container = document.getElementById('splits_' + day);
    const splitHtml = `
        <div class="row mt-2 split-shift">
            <div class="col-5">
                <input type="time" class="form-control" 
                       name="hours[${day}][splits][${splitCounter}][open]" 
                       placeholder="Open">
            </div>
            <div class="col-5">
                <input type="time" class="form-control" 
                       name="hours[${day}][splits][${splitCounter}][close]" 
                       placeholder="Close">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="this.closest('.split-shift').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', splitHtml);
    splitCounter++;
}

// Handle form submission
document.getElementById('businessHoursForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show mt-3';
            alert.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>Business hours saved successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            this.parentElement.insertBefore(alert, this);
            
            setTimeout(() => alert.remove(), 3000);
        } else {
            throw new Error(data.message || 'Failed to save business hours');
        }
    })
    .catch(error => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alert.innerHTML = `
            <i class="bi bi-exclamation-circle me-2"></i>${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        this.parentElement.insertBefore(alert, this);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>

<style>
.hours-grid .form-check-input:checked ~ .form-check-label {
    color: var(--bs-danger);
}

.split-shift {
    background-color: var(--bs-light);
    padding: 0.5rem;
    border-radius: 0.25rem;
}
</style>