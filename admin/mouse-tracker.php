<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$additionalstyles = '
<style>
.file-tree {
    padding-left: 0;
    list-style: none;
}
.file-tree li {
    padding: 4px 0;
}
.file-tree .directory {
    font-weight: bold;
    cursor: pointer;
    color: #444;
}
.file-tree .file {
    cursor: pointer;
    color: #666;
}
.file-tree .file:hover {
    color: #007bff;
}
.heatmap-container {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}
.metadata-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
.metadata-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
}
</style>
';

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    // Handle any form submissions if needed
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    function getDirectoryTree($dir) {
        $result = [];
        $path = dirname($_SERVER['DOCUMENT_ROOT']) . '/tracking_logs/' . $dir;
        
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $fullPath = $path . '/' . $file;
                if (is_dir($fullPath)) {
                    $result[] = [
                        'type' => 'directory',
                        'name' => $file,
                        'path' => $dir . '/' . $file,
                        'children' => getDirectoryTree($dir . '/' . $file)
                    ];
                } else if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $result[] = [
                        'type' => 'file',
                        'name' => $file,
                        'path' => $dir . '/' . $file,
                        'size' => filesize($fullPath),
                        'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
                    ];
                }
            }
        }
        return $result;
    }

    switch ($_GET['action']) {
        case 'get_tree':
            echo json_encode(getDirectoryTree(''));
            break;
            
        case 'get_session_data':
            if (isset($_GET['file'])) {
                $file = dirname($_SERVER['DOCUMENT_ROOT']) . '/tracking_logs/' . $_GET['file'];
                
                // Debug information
                $debug = [
                    'requested_file' => $_GET['file'],
                    'full_path' => $file,
                    'exists' => file_exists($file),
                    'readable' => is_readable($file),
                    'file_size' => file_exists($file) ? filesize($file) : 0,
                    'directory_exists' => is_dir(dirname($file))
                ];
                
                if (file_exists($file) && is_readable($file) && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $content = file_get_contents($file);
                    if ($content === false) {
                        http_response_code(500);
                        echo json_encode([
                            'error' => 'Failed to read file',
                            'debug' => $debug
                        ]);
                    } else {
                        // Verify it's valid JSON before sending
                        $json = json_decode($content);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            echo $content;
                        } else {
                            http_response_code(500);
                            echo json_encode([
                                'error' => 'Invalid JSON in file',
                                'json_error' => json_last_error_msg(),
                                'debug' => $debug
                            ]);
                        }
                    }
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'error' => 'File not found or not accessible',
                        'debug' => $debug
                    ]);
                }
            }
            break;
    }
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '    
<div class=" main-content mt-5 pt-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Mouse Tracking Data Viewer</h2>
        <a href="/admin" class="btn btn-sm btn-outline-secondary">Back to Admin</a>
    </div>
';

echo '
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Session Data</h5>
                        </div>
                        <div class="card-body">
                            <div id="fileTree" class="file-tree"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Visualization</h5>
                        </div>
                        <div class="card-body">
                            <div id="metadata"></div>
                            <div id="heatmapContainer" class="heatmap-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

?>

<script>
class TrackingViewer {
    constructor() {
        this.treeContainer = document.getElementById("fileTree");
        this.metadataContainer = document.getElementById("metadata");
        this.heatmapContainer = document.getElementById("heatmapContainer");
        this.animationSpeed = 50;
        this.dotSize = 'medium';
        this.showScreenshot = true;
        this.screenshotLoaded = false;
        this.initialize();
    }

    initialize() {
        // Load initial file tree
        this.loadFileTree();
    }

    async loadFileTree() {
        try {
            const response = await fetch("?action=get_tree");
            const data = await response.json();
            if (this.treeContainer) {
                this.treeContainer.innerHTML = this.renderTree(data);
            } else {
                console.error("File tree container not found");
            }
        } catch (error) {
            console.error("Error loading file tree:", error);
            if (this.treeContainer) {
                this.treeContainer.innerHTML = "<div class='text-danger'>Error loading file tree</div>";
            }
        }
    }

    renderTree(items, level = 0) {
        if (!items.length) return "";
        
        const padding = level * 20;
        return `<ul class="file-tree" style="padding-left: ${padding}px">
            ${items.map(item => `
                <li>
                    ${item.type === "directory" 
                        ? `<div class="directory">${item.name}/</div>
                           ${this.renderTree(item.children, level + 1)}`
                        : `<div class="file" onclick="window.viewer.loadSession('${item.path}')">${item.name}</div>`
                    }
                </li>
            `).join("")}
        </ul>`;
    }

    async checkScreenshot(path) {
        try {
            const response = await fetch(`/admin/mouse-tracker_getscreenshot.php?path=${encodeURIComponent(path)}&debug=1`);
            const data = await response.json();
            console.log('Screenshot debug info:', data);
            return data.exists && data.readable;
        } catch (e) {
            console.error('Error checking screenshot:', e);
            return false;
        }
    }

    async loadScreenshot() {
        if (!this.currentPath || !this.container) return;
        
        this.screenshotLoaded = false;
        const img = new Image();
        
        try {
            await new Promise((resolve, reject) => {
                img.onload = () => {
                    this.screenshotLoaded = true;
                    if (this.showScreenshot) {
                        this.container.style.backgroundImage = `url("/admin/mouse-tracker_getscreenshot.php?path=${encodeURIComponent(this.currentPath)}")`;
                        this.container.style.backgroundSize = '100% 100%';
                    }
                    resolve();
                };
                img.onerror = (e) => {
                    console.error('Failed to load screenshot:', e);
                    this.container.style.backgroundColor = '#fff';
                    reject(e);
                };
                img.src = `/admin/mouse-tracker_getscreenshot.php?path=${encodeURIComponent(this.currentPath)}`;
            });
        } catch (e) {
            console.error('Error loading screenshot:', e);
        }
    }

    async loadSession(path) {
        try {
            console.log("Loading session:", path);
            const response = await fetch(`?action=get_session_data&file=${encodeURIComponent(path)}`);
            const text = await response.text();
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const dataBatches = JSON.parse(text);
            if (!Array.isArray(dataBatches) || dataBatches.length === 0) {
                throw new Error("Invalid data format - expected non-empty array");
            }

            const firstBatch = dataBatches[0];
            const allPoints = dataBatches.flatMap(batch => batch.points || []);
            const combinedData = {
                sessionId: firstBatch.sessionId,
                metadata: firstBatch.metadata,
                points: allPoints
            };

            this.currentPath = path;
            this.renderVisualization(combinedData);
            await this.loadScreenshot();

        } catch (error) {
            console.error("Error loading session:", error);
            if (this.metadataContainer) {
                this.metadataContainer.innerHTML = `<div class="alert alert-danger">
                    Error loading session data: ${error.message}
                </div>`;
            }
            if (this.heatmapContainer) {
                this.heatmapContainer.innerHTML = "";
            }
        }
    }

    renderVisualization(data) {
        if (!this.metadataContainer || !this.heatmapContainer) {
            console.error("Required containers not found");
            return;
        }

        // Display metadata and controls
        this.metadataContainer.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>Session Info</h6>
                            <p class="mb-1">Session ID: ${data.sessionId}</p>
                            <p class="mb-1">Page: ${data.metadata.url}</p>
                            <p class="mb-1">Screen: ${data.metadata.screenWidth}x${data.metadata.screenHeight}</p>
                            <p class="mb-1">Viewport: ${data.metadata.viewportWidth}x${data.metadata.viewportHeight}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>Stats</h6>
                            <p class="mb-1">Total Points: ${data.points.length}</p>
                            <p class="mb-1">First Action: ${new Date(data.points[0]?.timestamp).toLocaleString()}</p>
                            <p class="mb-1">Last Action: ${new Date(data.points[data.points.length-1]?.timestamp).toLocaleString()}</p>
                            <p class="mb-1">Duration: ${Math.round((data.points[data.points.length-1]?.timestamp - data.points[0]?.timestamp) / 1000)}s</p>
                        </div>
                    </div>
                </div>
            </div>

                   <div class="card mt-3">
                <div class="card-header bg-light">
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="mb-0 me-5">Movement Heatmap</h6>
                        <div class="d-flex gap-2 ms-5">
                            <button class="btn btn-primary btn-sm" onclick="window.viewer.playAnimation()">Play</button>
                            <button class="btn btn-secondary btn-sm" onclick="window.viewer.resetAnimation()">Reset</button>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-3">
                            <label class="mb-0">Speed:</label>
                            <i class="bi bi-speedometer" style="font-size: 0.8rem"></i>
                    <input type="range" class="form-range" style="width: 120px" min="0" max="100" value="${100 - ((this.animationSpeed - 10) * 100 / 190)}"
                                onchange="window.viewer.setSpeed(200 - (this.value * 190 / 100))">
                                      <i class="bi bi-speedometer2" style="font-size: 0.8rem"></i>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-5">
                            <label class="mb-0">Size:</label>
                            <select class="form-select form-select-sm" style="width: 100px" onchange="window.viewer.setDotSize(this.value)">
                                <option value="small">Small</option>
                                <option value="medium" selected>Medium</option>
                                <option value="large">Large</option>
                            </select>
                        </div>
                        <div class="form-check mb-0 ms-auto">
                            <input class="form-check-input" type="checkbox" id="screenshotToggle" 
                                ${this.showScreenshot ? 'checked' : ''} 
                                onchange="window.viewer.toggleScreenshot(this.checked)">
                            <label class="form-check-label" for="screenshotToggle">Show Screenshot</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="heatmapInner" class="border rounded"></div>
                </div>
            </div>
        `;

        const heatmapInner = document.getElementById('heatmapInner');
        const container = document.createElement('div');
        container.style.width = `${data.metadata.viewportWidth}px`;
        container.style.height = `${data.metadata.viewportHeight}px`;
        container.style.position = 'relative';
        container.style.transform = 'scale(0.8)';
        container.style.transformOrigin = 'top left';

        this.points = data.points;
        this.currentPoint = 0;
        this.container = container;
        this.isPlaying = false;

        heatmapInner.innerHTML = '';
        heatmapInner.appendChild(container);
        this.renderAllPoints();
    }

    getDotSize(type) {
        const sizes = {
            small: { move: 8, click: 12 },
            medium: { move: 15, click: 20 },
            large: { move: 25, click: 30 }
        };
        return sizes[this.dotSize][type === 'click' ? 'click' : 'move'];
    }

    renderPoint(point) {
        if (!point.x || !point.y || !this.container) return;
        
        const dotSize = this.getDotSize(point.type);
        const dot = document.createElement('div');
        dot.style.position = 'absolute';
        dot.style.left = `${point.x}px`;
        dot.style.top = `${point.y}px`;
        dot.style.width = `${dotSize}px`;
        dot.style.height = `${dotSize}px`;
        dot.style.borderRadius = '50%';
        dot.style.backgroundColor = point.type === 'click' ? 
            'rgba(255, 0, 0, 0.8)' : 'rgba(0, 128, 255, 0.4)';
        dot.style.transform = 'translate(-50%, -50%)';
        
        if (point.targetElement) {
            dot.title = `${point.type}: ${point.targetElement}`;
        }
        
        this.container.appendChild(dot);
    }

    renderAllPoints() {
        if (!this.container || !this.points) return;
        this.container.innerHTML = '';
        this.points.forEach(point => this.renderPoint(point));
    }

    async playAnimation() {
        if (this.isPlaying || !this.container || !this.points) return;
        this.isPlaying = true;
        this.container.innerHTML = '';
        
        for (let i = 0; i < this.points.length && this.isPlaying; i++) {
            await new Promise(resolve => setTimeout(resolve, this.animationSpeed));
            this.renderPoint(this.points[i]);
        }
        
        this.isPlaying = false;
    }

    resetAnimation() {
        this.isPlaying = false;
        this.renderAllPoints();
    }

    setSpeed(value) {
        this.animationSpeed = parseInt(value);
    }

    setDotSize(size) {
        this.dotSize = size;
        this.renderAllPoints();
    }

    toggleScreenshot(show) {
        this.showScreenshot = show;
        if (!this.container) return;
        
        if (show && this.screenshotLoaded) {
            this.container.style.backgroundImage = `url("/admin/mouse-tracker_getscreenshot.php?path=${encodeURIComponent(this.currentPath)}")`;
            this.container.style.backgroundSize = '100% 100%';
        } else {
            this.container.style.backgroundImage = 'none';
            this.container.style.backgroundColor = '#fff';
        }
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.viewer = new TrackingViewer();
});

</script>

<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();