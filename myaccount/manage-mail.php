<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Manage Mail";
$bodycontentclass='';

// Add v7 theme CSS and custom styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
    .avatar-img {
        width: 60px;
        height: 60px;
        margin-right: 15px;
    }

    .primary-switch:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .primary-switch:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); /* Adjust the shadow color if needed */
    }
    
    /* Clean modern tab navigation - like Material Design */
    .nav-tabs-clean {
        display: flex;
        border-bottom: none;
        padding: 0;
        list-style: none;
    }
    
    /* Container for tabs and gear button */
    .tabs-container {
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 2rem;
    }
    
    .nav-tab-clean {
        position: relative;
        margin-right: 3rem;
    }
    
    .nav-tab-clean a {
        display: block;
        padding: 1rem 1.5rem;
        text-decoration: none;
        color: #666;
        font-weight: 500;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: color 0.2s ease;
        border: none;
        background: none;
    }
    
    .nav-tab-clean a:hover {
        color: #333;
    }
    
    .nav-tab-clean.active a {
        color: #0d6efd;
    }
    
    .nav-tab-clean.active::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #0d6efd;
    }
    
    .tab-badge {
        display: inline-block;
        min-width: 20px;
        padding: 2px 6px;
        margin-left: 8px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        background-color: #dc3545;
        border-radius: 10px;
    }
    
    /* Search bar styles */
    .search-container {
        max-width: 600px;
        margin: 0 auto 2rem;
    }
    
    /* Filter bar styles */
    .filter-bar {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }
    
    /* Mail item styles */
    .mail-item {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: start;
        gap: 1rem;
        transition: background-color 0.2s;
    }
    
    .mail-item:hover {
        background-color: #f8f9fa;
    }
    
    .mail-item.unread {
        background-color: #f0f8ff;
        font-weight: 500;
    }
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-envelope me-3"></i>Manage Mail</h1>
            <p class="lead mb-3">Control your email preferences and view your communication history</p>
            
            <!-- Search bar in header -->
            <div class="search-container">
                <div class="input-group">
                    <input type="text" class="form-control form-control-lg" placeholder="Search mail..." id="mailSearch">
                    <button class="btn btn-primary btn-lg" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

// Initialize mail data arrays
$localcontent = array();
$mail_counts = array('unread' => 0, 'read' => 0, 'extra' => 0, 'all' => 0);

?>

<div class="container my-5">
    <div class="container">
        <!-- Clean tab navigation with settings gear -->
        <div class="d-flex justify-content-between align-items-center tabs-container">
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean active">
                    <a href="#all" data-bs-toggle="tab">
                        <i class="bi bi-envelope me-2"></i>All Mail
                    </a>
                </li>
                <li class="nav-tab-clean">
                    <a href="#unread" data-bs-toggle="tab">
                        <i class="bi bi-envelope-exclamation me-2"></i>Unread
                        <span class="tab-badge" id="unreadCount">0</span>
                    </a>
                </li>
                <li class="nav-tab-clean">
                    <a href="#read" data-bs-toggle="tab">
                        <i class="bi bi-envelope-open me-2"></i>Read
                    </a>
                </li>
                <li class="nav-tab-clean">
                    <a href="#extra" data-bs-toggle="tab">
                        <i class="bi bi-star me-2"></i>Starred
                    </a>
                </li>
            </ul>
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean">
                    <a href="#settings" id="settingsButton">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter by Company</label>
                    <select class="form-select" id="companyFilter">
                        <option value="">All Companies</option>
                        <!-- Companies will be populated dynamically -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter by Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="rewards">Rewards</option>
                        <option value="marketing">Marketing</option>
                        <option value="deals">Deals</option>
                        <option value="updates">Updates</option>
                        <option value="reminders">Reminders</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Sort by</label>
                    <select class="form-select" id="sortBy">
                        <option value="date">Date (Newest First)</option>
                        <option value="importance">Importance</option>
                        <option value="company">Company Name</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-secondary" onclick="resetFilters()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
                    </button>
                </div>
            </div>
        </div>




        <!-- Tab content -->
        <div class="tab-content">
            <!-- All Mail (showing full history) -->
            <div class="tab-pane fade show active" id="all">
                <div class="mail-content">
                    <?php 
                    // Include original mail history
                    include($dir['core_components'] . '/user_notifications.inc'); 
                    ?>
                </div>
            </div>
            
            <!-- Unread Mail -->
            <div class="tab-pane fade" id="unread">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-envelope-exclamation" style="font-size: 3rem;"></i>
                        <p class="mt-3">No unread messages</p>
                        <p class="small">Your unread mail will appear here</p>
                    </div>
                </div>
            </div>
            
            <!-- Read Mail -->
            <div class="tab-pane fade" id="read">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-envelope-open" style="font-size: 3rem;"></i>
                        <p class="mt-3">No read messages</p>
                        <p class="small">Your read mail will appear here</p>
                    </div>
                </div>
            </div>
            
            <!-- Extra/Starred Mail -->
            <div class="tab-pane fade" id="extra">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-star" style="font-size: 3rem;"></i>
                        <p class="mt-3">No starred messages</p>
                        <p class="small">Star important messages to see them here</p>
                    </div>
                </div>
            </div>
            
            <!-- Settings -->
            <div class="tab-pane fade" id="settings">
                <div class="card px-4 mt-0 pt-0">
                    <?php include($dir['core_components'] . '/user_mail_settings.inc'); ?>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Mail pagination" class="mt-4" id="paginationContainer" style="display: none;">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled" id="prevPage">
                    <a class="page-link" href="#" tabindex="-1">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                </li>
                <li class="page-item active"><a class="page-link" href="#" data-page="1">1</a></li>
                <li class="page-item"><a class="page-link" href="#" data-page="2">2</a></li>
                <li class="page-item"><a class="page-link" href="#" data-page="3">3</a></li>
                <li class="page-item" id="nextPage">
                    <a class="page-link" href="#">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
            <div class="text-center text-muted small mt-2">
                Showing <span id="startItem">1</span>-<span id="endItem">20</span> of <span id="totalItems">0</span> messages
            </div>
        </nav>
    </div>
</div>

<!-- Enhanced scripts for tab switching, search, filters, and pagination -->
<script>
    // Pagination variables
    let currentPage = 1;
    const itemsPerPage = 20;
    let allMailItems = [];
    let filteredMailItems = [];

    // Function to activate a tab by its target
    function activateTab(targetHash) {
        // Remove active class from all tabs
        document.querySelectorAll('.nav-tab-clean').forEach(function(t) {
            t.classList.remove('active');
        });

        // Find and activate the tab with matching href
        var targetTab = document.querySelector('.nav-tab-clean a[href="' + targetHash + '"]');
        if (targetTab) {
            targetTab.parentElement.classList.add('active');
            
            // Show corresponding content
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            var targetPane = document.querySelector(targetHash);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        }
        
        // Reset pagination when switching tabs
        currentPage = 1;
        updatePagination();
    }

    // Search functionality
    function filterMail() {
        const searchTerm = document.getElementById('mailSearch').value.toLowerCase();
        const companyFilter = document.getElementById('companyFilter').value.toLowerCase();
        const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
        const sortBy = document.getElementById('sortBy').value;
        
        // Get current active tab
        const activeTab = document.querySelector('.nav-tab-clean.active a').getAttribute('href').substring(1);
        allMailItems = Array.from(document.querySelectorAll('#' + activeTab + ' .mail-item'));
        
        // Filter items
        filteredMailItems = allMailItems.filter(function(item) {
            const text = item.textContent.toLowerCase();
            const company = item.dataset.company || '';
            const type = item.dataset.type || '';
            
            const matchesSearch = searchTerm === '' || text.includes(searchTerm);
            const matchesCompany = companyFilter === '' || company.toLowerCase() === companyFilter;
            const matchesType = typeFilter === '' || type.toLowerCase() === typeFilter;
            
            return matchesSearch && matchesCompany && matchesType;
        });
        
        // Sort items if needed
        if (sortBy !== 'date') {
            sortFilteredItems(sortBy);
        }
        
        // Reset to first page and update pagination
        currentPage = 1;
        updatePagination();
    }

    // Sort filtered mail items
    function sortFilteredItems(sortBy) {
        filteredMailItems.sort(function(a, b) {
            if (sortBy === 'importance') {
                return (b.dataset.importance || 0) - (a.dataset.importance || 0);
            } else if (sortBy === 'company') {
                return (a.dataset.company || '').localeCompare(b.dataset.company || '');
            }
            return 0;
        });
    }
    
    // Update pagination display
    function updatePagination() {
        const activeTab = document.querySelector('.nav-tab-clean.active a').getAttribute('href').substring(1);
        
        // Don't show pagination for settings tab
        if (activeTab === 'settings') {
            document.getElementById('paginationContainer').style.display = 'none';
            return;
        }
        
        // Get items to paginate
        const itemsToShow = filteredMailItems.length > 0 ? filteredMailItems : allMailItems;
        const totalItems = itemsToShow.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        // Hide all items first
        allMailItems.forEach(item => item.style.display = 'none');
        
        // Show items for current page
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        for (let i = startIndex; i < endIndex; i++) {
            if (itemsToShow[i]) {
                itemsToShow[i].style.display = '';
            }
        }
        
        // Update pagination controls
        const paginationContainer = document.getElementById('paginationContainer');
        if (totalItems <= itemsPerPage) {
            paginationContainer.style.display = 'none';
        } else {
            paginationContainer.style.display = '';
            
            // Update page numbers
            const pagination = paginationContainer.querySelector('.pagination');
            pagination.innerHTML = '';
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
            prevLi.innerHTML = '<a class="page-link" href="#" onclick="changePage(' + (currentPage - 1) + '); return false;"><i class="bi bi-chevron-left"></i> Previous</a>';
            pagination.appendChild(prevLi);
            
            // Page numbers
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            if (startPage > 1) {
                const li = document.createElement('li');
                li.className = 'page-item';
                li.innerHTML = '<a class="page-link" href="#" onclick="changePage(1); return false;">1</a>';
                pagination.appendChild(li);
                
                if (startPage > 2) {
                    const dots = document.createElement('li');
                    dots.className = 'page-item disabled';
                    dots.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(dots);
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = 'page-item' + (i === currentPage ? ' active' : '');
                li.innerHTML = '<a class="page-link" href="#" onclick="changePage(' + i + '); return false;">' + i + '</a>';
                pagination.appendChild(li);
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('li');
                    dots.className = 'page-item disabled';
                    dots.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(dots);
                }
                
                const li = document.createElement('li');
                li.className = 'page-item';
                li.innerHTML = '<a class="page-link" href="#" onclick="changePage(' + totalPages + '); return false;">' + totalPages + '</a>';
                pagination.appendChild(li);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
            nextLi.innerHTML = '<a class="page-link" href="#" onclick="changePage(' + (currentPage + 1) + '); return false;">Next <i class="bi bi-chevron-right"></i></a>';
            pagination.appendChild(nextLi);
            
            // Update item count display
            document.getElementById('startItem').textContent = startIndex + 1;
            document.getElementById('endItem').textContent = endIndex;
            document.getElementById('totalItems').textContent = totalItems;
        }
    }
    
    // Change page
    function changePage(page) {
        const itemsToShow = filteredMailItems.length > 0 ? filteredMailItems : allMailItems;
        const totalPages = Math.ceil(itemsToShow.length / itemsPerPage);
        
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            updatePagination();
        }
    }

    // Reset filters
    function resetFilters() {
        document.getElementById('mailSearch').value = '';
        document.getElementById('companyFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('sortBy').value = 'date';
        filterMail();
    }

    // Document ready
    document.addEventListener("DOMContentLoaded", function () {
        // Initialize mail items
        const activeTab = document.querySelector('.nav-tab-clean.active a').getAttribute('href').substring(1);
        allMailItems = Array.from(document.querySelectorAll('#' + activeTab + ' .mail-item'));
        filteredMailItems = allMailItems;
        
        // Handle tab clicks
        document.querySelectorAll('.nav-tab-clean a').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var targetHash = this.getAttribute('href');
                activateTab(targetHash);
                
                // Update URL hash if not settings
                if (targetHash !== '#settings') {
                    history.pushState(null, null, targetHash);
                }
                
                // Reinitialize items for new tab
                const newActiveTab = targetHash.substring(1);
                allMailItems = Array.from(document.querySelectorAll('#' + newActiveTab + ' .mail-item'));
                filteredMailItems = allMailItems;
                updatePagination();
            });
        });

        // Handle settings button specially
        document.getElementById('settingsButton').addEventListener('click', function(e) {
            e.preventDefault();
            activateTab('#settings');
        });

        // Check URL hash on page load
        if (window.location.hash) {
            activateTab(window.location.hash);
        }

        // Handle browser back/forward
        window.addEventListener('popstate', function() {
            if (window.location.hash) {
                activateTab(window.location.hash);
            }
        });

        // Add event listeners for search and filters
        document.getElementById('mailSearch').addEventListener('input', filterMail);
        document.getElementById('companyFilter').addEventListener('change', filterMail);
        document.getElementById('typeFilter').addEventListener('change', filterMail);
        document.getElementById('sortBy').addEventListener('change', filterMail);
        
        // Update unread count and pagination
        updateUnreadCount();
        updatePagination();
    });

    // Update unread count badge
    function updateUnreadCount() {
        const unreadItems = document.querySelectorAll('#unread .mail-item').length;
        const badge = document.getElementById('unreadCount');
        if (badge) {
            badge.textContent = unreadItems;
            badge.style.display = unreadItems > 0 ? 'inline-block' : 'none';
        }
    }
</script>
<?php

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
