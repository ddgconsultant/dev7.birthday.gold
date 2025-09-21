<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';
$additionalstyles = '
<style>
    /* Clean modern tab navigation */
    .nav-tabs-modern {
        display: flex;
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 1.5rem;
        gap: 2rem;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .nav-tabs-modern::-webkit-scrollbar {
        display: none;
    }
    
    .nav-tab-item {
        position: relative;
        padding: 0.75rem 0;
        text-decoration: none;
        color: #757575;
        font-weight: 500;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: color 0.2s ease;
        background: none;
        border: none;
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .nav-tab-item:hover {
        color: #424242;
        text-decoration: none;
        background: none;
    }
    
    .nav-tab-item.active {
        color: #000;
    }
    
    .nav-tab-item.active::after {
        content: "";
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #000;
    }
    
    .nav-tab-item .badge {
        font-size: 0.7rem;
        padding: 0.15em 0.4em;
        margin-left: 0.5rem;
        vertical-align: middle;
        background-color: #dc3545;
        border-radius: 10px;
    }
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');
?>

<div class="container main-content mt-0 pt-0">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0 page-title">Test Tabs</h2>
            </div>
            
            <!-- Tab navigation -->
            <nav class="nav-tabs-modern">
                <a href="#" class="nav-tab-item active" data-filter="unread">
                    Unread <span class="badge bg-danger ms-1">3</span>
                </a>
                <a href="#" class="nav-tab-item" data-filter="read">
                    Read
                </a>
                <a href="#" class="nav-tab-item" data-filter="all">
                    All
                </a>
            </nav>
            
            <div class="card">
                <div class="card-body">
                    <p>Test content here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Tab navigation functionality
    var filterTabs = document.querySelectorAll('.nav-tab-item');
    filterTabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            filterTabs.forEach(function(t) {
                t.classList.remove('active');
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
        });
    });
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>