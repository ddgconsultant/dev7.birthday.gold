<?php
// mail-goldie.php - Goldie Managed Inbox
$addClasses[] = 'mail';
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Goldie Managed Inbox";

// Retrieve any messages from previous page
$transferpagedata = $system->startpostpage();

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$uid = !empty($_REQUEST['uid']) ? $qik->decodeId($_REQUEST['uid']) : $current_user_data['user_id'];

// Initialize variables
$days_back = intval($_GET['days'] ?? 7);
$days_back = min(max($days_back, 7), 30); // Between 7 and 30 days

// Add v7 theme CSS and custom styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
/* Animation keyframes - Cannot be replaced with Bootstrap */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes shadow {
    0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.3; }
    50% { transform: translateX(-50%) scale(0.9); opacity: 0.2; }
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Avatar animations - Custom animations cannot be replaced */
.floating-icon {
    animation: float 6s ease-in-out infinite;
}

.shadow-icon {
    opacity: 0.3;
    animation: shadow 6s ease-in-out infinite;
}

/* Responsive avatar sizing */
@media (max-width: 768px) {
    .floating-icon, .shadow-icon {
        width: 80px !important;
    }
    
    .content-header-dark .position-relative {
        height: 96px !important;
    }
}

/* Custom animations and transitions that cannot be replaced */
.summary-card {
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s forwards;
}

.summary-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
}

.progress-bar-fill {
    transition: width 0.3s ease;
}

.loading-spinner {
    animation: spin 1s linear infinite;
}

/* Status badges - Custom color combinations */
.status-cached {
    background: #d4edda;
    color: #155724;
}

.status-fresh {
    background: #cce5ff;
    color: #004085;
}

/* Offer item border handling */
.offer-item:last-child {
    border-bottom: none !important;
}
</style>';

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section with Goldie Avatar -->
<div class="content-header-dark">
    <div class="container">
        <div class="row justify-content-center position-relative">
            <div class="col-auto d-flex align-items-center">
                <div class="text-end me-3 position-relative" style="width: 100px; height: 120px;">
                    <img src="/public/images/logo/goldie-avatar_200.png" alt="Goldie" class="floating-icon" style="position: absolute; top: 0; left: 0; z-index: 2; width: 100px;">
                    <img src="/public/images/logo/goldie-shadow_200.png" alt="" class="shadow-icon" style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); z-index: 1; width: 100px;">
                </div>
                <div>
                    <h1 class="mb-2">Goldie Managed Inbox</h1>
                    <p class="lead mb-0">AI-powered insights into your birthday rewards</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- Admin/Dev Notice -->
    <?php if ($account->isadmin() || $mode === 'dev'): ?>
    <div class="alert alert-info mb-3">
        <i class="bi bi-tools me-2"></i>
        <strong>Admin/Dev Mode</strong>
    </div>
    <?php endif; ?>
    
    <!-- Date range selector -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3">
                <label class="form-label mb-0">Show summaries for:</label>
                <select class="form-select" style="width: auto;" id="daysSelector">
                    <option value="7" <?php echo $days_back == 7 ? 'selected' : ''; ?>>Last 7 days</option>
                    <option value="14" <?php echo $days_back == 14 ? 'selected' : ''; ?>>Last 14 days</option>
                    <option value="30" <?php echo $days_back == 30 ? 'selected' : ''; ?>>Last 30 days</option>
                </select>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" id="refreshBtn" onclick="forceRefresh()" style="font-size: 0.875rem;">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh Summaries
            </button>
        </div>
    </div>

    <!-- Progress Container (hidden initially) -->
    <div id="progressContainer" class="bg-light rounded p-4 my-4 text-center" style="display: none;">
        <div class="fs-5 text-primary mb-3" id="progressMessage">
            <i class="bi bi-hourglass-split me-2"></i>
            <span id="progressText">Initializing Goldie...</span>
            <span class="loading-spinner d-inline-block ms-2 border border-3 border-light border-top-primary rounded-circle" style="width: 20px; height: 20px;"></span>
        </div>
        <div class="bg-gray rounded overflow-hidden my-3" style="height: 10px;">
            <div class="progress-bar-fill bg-primary h-100" id="progressBar" style="width: 0%;"></div>
        </div>
        <small class="text-muted" id="progressDetail"></small>
    </div>

    <!-- Summaries Container -->
    <div id="summariesContainer">
        <!-- Summaries will be loaded here via AJAX -->
    </div>

    <!-- Empty State (shown when no messages) -->
    <div id="emptyState" class="text-center py-5 text-muted" style="display: none;">
        <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
        <h3 class="mt-3">No messages found</h3>
        <p>You don't have any birthday reward messages in the selected time period.</p>
        <a href="/myaccount/mail-box" class="btn btn-primary" style="border-radius: 25px;">
            Go to Regular Inbox
        </a>
    </div>
</div>

<script>
// Debug mode based on PHP $mode variable
const DEBUG_MODE = <?php echo $mode === 'dev' ? 'true' : 'false'; ?>;
const LOG_PREFIX = '[Goldie Mail]';

// Debug logger
function debugLog(message, data = null) {
    if (DEBUG_MODE) {
        const timestamp = new Date().toISOString();
        if (data) {
            console.log(`${LOG_PREFIX} ${timestamp} - ${message}`, data);
        } else {
            console.log(`${LOG_PREFIX} ${timestamp} - ${message}`);
        }
    }
}

// Performance tracking
const performanceTracker = {
    startTime: null,
    lastHeartbeat: null,
    heartbeatInterval: null,
    
    start() {
        this.startTime = Date.now();
        this.lastHeartbeat = Date.now();
        debugLog('Performance tracking started');
        
        // Heartbeat every 5 seconds
        this.heartbeatInterval = setInterval(() => {
            const elapsed = ((Date.now() - this.startTime) / 1000).toFixed(1);
            debugLog(`Heartbeat - Total elapsed: ${elapsed}s`, {
                currentTime: new Date().toISOString(),
                memoryUsage: performance.memory ? performance.memory.usedJSHeapSize / 1048576 : 'N/A'
            });
        }, 5000);
    },
    
    stop() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            const totalTime = ((Date.now() - this.startTime) / 1000).toFixed(1);
            debugLog(`Performance tracking stopped - Total time: ${totalTime}s`);
        }
    },
    
    logStep(step) {
        const stepTime = ((Date.now() - this.lastHeartbeat) / 1000).toFixed(1);
        const totalTime = ((Date.now() - this.startTime) / 1000).toFixed(1);
        debugLog(`Step: ${step} - Step time: ${stepTime}s, Total time: ${totalTime}s`);
        this.lastHeartbeat = Date.now();
    }
};

let currentRequest = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM Content Loaded - Initializing Goldie Mail');
    loadSummaries();
    
    // Days selector change handler
    document.getElementById('daysSelector').addEventListener('change', function() {
        const days = this.value;
        debugLog('Days selector changed', { days });
        const currentParams = new URLSearchParams(window.location.search);
        currentParams.set('days', days);
        window.history.pushState({}, '', '?' + currentParams.toString());
        loadSummaries();
    });
});

function updateProgress(message, detail = '', percent = 0) {
    document.getElementById('progressText').textContent = message;
    document.getElementById('progressDetail').textContent = detail;
    document.getElementById('progressBar').style.width = percent + '%';
}

function showProgress() {
    document.getElementById('progressContainer').style.display = 'block';
    document.getElementById('summariesContainer').innerHTML = '';
    document.getElementById('emptyState').style.display = 'none';
}

function hideProgress() {
    document.getElementById('progressContainer').style.display = 'none';
}

async function loadSummaries(forceRefresh = false) {
    debugLog('loadSummaries called', { forceRefresh, days: document.getElementById('daysSelector').value });
    performanceTracker.start();
    
    // Cancel any pending request
    if (currentRequest) {
        debugLog('Cancelling previous request');
        currentRequest.abort();
    }
    
    showProgress();
    const days = document.getElementById('daysSelector').value;
    
    try {
        // Create abort controller for this request
        const controller = new AbortController();
        currentRequest = controller;
        
        performanceTracker.logStep('Starting fetch request');
        updateProgress('Connecting to Goldie...', 'Preparing to analyze your messages', 10);
        
        const requestBody = {
            days: days,
            forceRefresh: forceRefresh
        };
        
        debugLog('Sending request to mail-goldie-process.php', requestBody);
        
        const response = await fetch('/myaccount/ajax/mail-goldie-process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody),
            signal: controller.signal
        });
        
        debugLog('Response received', { 
            status: response.status, 
            statusText: response.statusText,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }
        
        if (!response.body) {
            throw new Error('Response body is null');
        }
        
        performanceTracker.logStep('Starting to read response stream');
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let eventCount = 0;
        
        while (true) {
            const { done, value } = await reader.read();
            
            if (done) {
                debugLog('Stream reading complete', { eventCount });
                break;
            }
            
            const chunk = decoder.decode(value, { stream: true });
            buffer += chunk;
            
            debugLog('Received chunk', { size: chunk.length, bufferSize: buffer.length });
            
            // Process complete lines
            const lines = buffer.split('\n');
            buffer = lines.pop() || ''; // Keep incomplete line in buffer
            
            for (const line of lines) {
                if (line.trim() === '') continue;
                
                if (line.startsWith('data: ')) {
                    try {
                        const jsonData = line.substring(6);
                        const data = JSON.parse(jsonData);
                        eventCount++;
                        debugLog(`Event #${eventCount} received`, data);
                        handleProgressUpdate(data);
                        performanceTracker.logStep(`Processed event: ${data.type}`);
                    } catch (parseError) {
                        debugLog('Error parsing SSE data', { line, error: parseError.message });
                    }
                } else {
                    debugLog('Non-data line received', { line });
                }
            }
        }
        
        performanceTracker.stop();
        
    } catch (error) {
        performanceTracker.stop();
        
        if (error.name !== 'AbortError') {
            debugLog('Error in loadSummaries', { 
                errorName: error.name,
                errorMessage: error.message,
                errorStack: error.stack 
            });
            console.error('Error loading summaries:', error);
            hideProgress();
            document.getElementById('summariesContainer').innerHTML = 
                `<div class="alert alert-danger">
                    <strong>Error:</strong> ${error.message}<br>
                    <small>Check console for details.</small>
                </div>`;
        } else {
            debugLog('Request aborted');
        }
    }
}

function handleProgressUpdate(data) {
    debugLog(`Handling ${data.type} update`, data);
    
    switch (data.type) {
        case 'heartbeat':
            debugLog('Heartbeat received', { timestamp: data.timestamp });
            break;
            
        case 'debug':
            debugLog('Debug info from server', data);
            break;
            
        case 'progress':
            updateProgress(data.message, data.detail || '', data.percent || 0);
            break;
            
        case 'summary':
            debugLog('Adding summary card', { date: data.summary.date });
            addSummaryCard(data.summary);
            break;
            
        case 'complete':
            debugLog('Processing complete', { totalSummaries: data.totalSummaries });
            hideProgress();
            if (data.totalSummaries === 0) {
                document.getElementById('emptyState').style.display = 'block';
            }
            break;
            
        case 'error':
            debugLog('Error received', { message: data.message });
            hideProgress();
            document.getElementById('summariesContainer').innerHTML = 
                '<div class="alert alert-danger">' + data.message + '</div>';
            break;
            
        default:
            debugLog('Unknown event type', data);
    }
}

function addSummaryCard(summary) {
    const container = document.getElementById('summariesContainer');
    
    // Build offers HTML
    let offersHtml = '';
    if (summary.offers && summary.offers.length > 0) {
        offersHtml = '<ul class="list-unstyled mt-3">';
        summary.offers.forEach(offer => {
            offersHtml += `
                <li class="offer-item py-3 border-bottom">
                    <div class="fw-semibold text-dark mb-1">${offer.company}</div>
                    <div class="text-muted small">${offer.offer}</div>
                    ${offer.action ? '<div class="text-primary small fst-italic">' + offer.action + '</div>' : ''}
                </li>
            `;
        });
        offersHtml += '</ul>';
    }
    
    // Build company badges
    let companiesHtml = '';
    if (summary.companies && summary.companies.length > 0) {
        companiesHtml = '<div class="d-flex flex-wrap gap-2 my-3">';
        summary.companies.forEach(company => {
            companiesHtml += `<span class="d-inline-flex align-items-center gap-1 px-3 py-1 bg-light rounded-pill small">${company}</span>`;
        });
        companiesHtml += '</div>';
    }
    
    const cardHtml = `
        <div class="summary-card bg-white rounded p-4 mb-3 shadow-sm" style="animation-delay: ${container.children.length * 0.1}s">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fs-5 fw-semibold text-dark">${summary.displayDate}</div>
                    <div class="d-flex gap-3 small text-muted mt-1">
                        <span><i class="bi bi-envelope me-1"></i>${summary.messageCount} messages</span>
                        ${summary.companyCount ? '<span><i class="bi bi-building me-1"></i>' + summary.companyCount + ' companies</span>' : ''}
                        <span class="d-inline-block px-3 py-1 rounded-pill small fw-semibold text-uppercase ${summary.cached ? 'status-cached' : 'status-fresh'}">
                            ${summary.cached ? 'Cached' : 'Fresh'}
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="/myaccount/mail-box?date=${summary.date}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" style="font-size: 0.875rem;">
                        <i class="bi bi-envelope-open"></i>
                        View Messages
                    </a>
                </div>
            </div>
            
            ${companiesHtml}
            
            <div class="text-black lh-base my-3">
                ${summary.summary}
            </div>
            
            ${offersHtml}
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', cardHtml);
}

function forceRefresh() {
    if (confirm('This will regenerate all summaries using AI. This may take a moment. Continue?')) {
        loadSummaries(true);
    }
}
</script>

<?php
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>