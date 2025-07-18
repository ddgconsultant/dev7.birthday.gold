<?php
// inbox.php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Mail Inbox";



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------


$errormessage = '';

#$uid=($qik->decodeId($_REQUEST['uid']) ?? $current_user_data['user_id']);
$uid = !empty($_REQUEST['uid']) ? $qik->decodeId($_REQUEST['uid']) : $current_user_data['user_id'];

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------


// Initialize variables for sorting and filtering
$sort = $_GET['sort'] ?? 'date';
$order = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 100;

// Get messages
$messages_results = $mail->getmessagelist(    $uid,     'user',    
    [
        'sort' => $sort,
        'order' => $order,
        'search' => $search,
        'page' => $page,
        'per_page' => $per_page
    ]
    
);

$messages = $messages_results['messages'];
$total_messages = $messages_results['counts']['total'];
$total_pages = ceil($total_messages / $per_page);


// Add v7 theme CSS and custom styles
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">
<style>
.message-row { transition: background-color 0.15s ease-in-out; cursor: pointer; }
.message-row:hover { background-color: rgba(0, 0, 0, .03); }
.message-row.selected { background-color: rgba(13, 110, 253, .1); }
.message-row.unread { background-color: rgba(248, 249, 250, .7); font-weight: 600; }
.company-logo { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; }
.sort-icon { opacity: 0.3; }
.sort-active .sort-icon { opacity: 1; }
@media (max-width:768px) {
.sender-col { max-width: 120px; }
.date-col { max-width: 70px; }
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
</style>';
    
    
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-envelope me-3"></i>Mail Inbox</h1>
            <p class="lead mb-3">View and manage your birthday reward messages</p>
            
            <!-- Search bar in header -->
            <div class="search-container">
                <form method="GET" id="search-form">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-lg" placeholder="Search messages..." 
                               name="search" value="<?php echo htmlspecialchars($search); ?>" id="mailSearch">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="container">
        <!-- Clean tab navigation with settings gear -->
        <div class="d-flex justify-content-between align-items-center tabs-container">
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean active">
                    <a href="#all" data-bs-toggle="tab">
                        <i class="bi bi-envelope me-2"></i>All Mail
                        <?php if ($total_messages > 0): ?>
                        <span class="tab-badge bg-secondary"><?php echo $total_messages; ?></span>
                        <?php endif; ?>
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
                    <a href="#starred" data-bs-toggle="tab">
                        <i class="bi bi-star me-2"></i>Starred
                    </a>
                </li>
            </ul>
            <ul class="nav-tabs-clean mb-0">
                <li class="nav-tab-clean">
                    <a href="/myaccount/manage-mail#settings" id="settingsButton">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="row g-3 align-items-center">
                <div class="col-md-2">
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-check2-square me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item bulk-action" href="#" data-action="mark-read">
                                <i class="bi bi-envelope-open me-2"></i>Mark as Read
                            </a></li>
                            <li><a class="dropdown-item bulk-action" href="#" data-action="mark-unread">
                                <i class="bi bi-envelope me-2"></i>Mark as Unread
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item bulk-action text-danger" href="#" data-action="delete">
                                <i class="bi bi-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Sort by</label>
                    <select class="form-select" id="sortBy" onchange="updateSort(this.value)">
                        <option value="date-desc" <?php echo $sort === 'date' && $order === 'desc' ? 'selected' : ''; ?>>Date (Newest First)</option>
                        <option value="date-asc" <?php echo $sort === 'date' && $order === 'asc' ? 'selected' : ''; ?>>Date (Oldest First)</option>
                        <option value="sender-asc" <?php echo $sort === 'sender' && $order === 'asc' ? 'selected' : ''; ?>>Sender (A-Z)</option>
                        <option value="sender-desc" <?php echo $sort === 'sender' && $order === 'desc' ? 'selected' : ''; ?>>Sender (Z-A)</option>
                        <option value="subject-asc" <?php echo $sort === 'subject' && $order === 'asc' ? 'selected' : ''; ?>>Subject (A-Z)</option>
                        <option value="subject-desc" <?php echo $sort === 'subject' && $order === 'desc' ? 'selected' : ''; ?>>Subject (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-2 ms-auto text-end">
                    <button type="button" class="btn btn-outline-secondary" id="refresh-btn">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab content -->
        <div class="tab-content">
            <!-- All Mail -->
            <div class="tab-pane fade show active" id="all">
                <div class="mail-content">
                    <!-- Messages List -->
                    <div class="card">
                       <div class="card-body p-0">
                           <!-- Table Header -->
                           <div class="table-responsive">
                               <table class="table table-hover mb-0">
                                   <thead class="table-light">
                                       <tr>
                                           <th class="ps-3" style="width: 40px;">
                                               <div class="form-check">
                                                   <input class="form-check-input" type="checkbox" id="select-all">
                                               </div>
                                           </th>
                                           <th style="width: 200px;">Sender</th>
                                           <th>Subject</th>
                                           <th class="text-end" style="width: 120px;">Date</th>
                                       </tr>
                                   </thead>
                                   <tbody>

<?php

if (empty($messages)) {
   echo '<tr><td colspan="4" class="text-center">No messages found.</td></tr>';
} else {
   foreach ($messages as $message) {
       $date = new DateTime($message['create_dt']);
       $today = new DateTime();
       $dateformat = $date->format('Y-m-d') === $today->format('Y-m-d') ? 'h:i a' : 'M j';
       $formatted_date = $display->formatdate($message['create_dt'], $dateformat);

       $is_unread = $message['processstatus'] !== 'read';
       $company = !empty($message['company_id']) ? $app->getcompany($message['company_id']) : null;

       echo '
       <tr class="message-row ' . ($is_unread ? 'unread' : '') . '" 
           data-message-id="' . $message['message_id'] . '"
         data-server="' . htmlspecialchars($message['host'] ?? '') . '"
         >
           <td class="ps-3">
               <div class="form-check">
                   <input class="form-check-input message-checkbox" type="checkbox" 
                          value="' . $message['message_id'] . '" 
                          onclick="event.stopPropagation();">
               </div>
           </td>
           <td class="sender-col">
               <div class="d-flex align-items-center">';
       
       if (!empty($company['company_logo'])) {
           echo '<img src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" 
                     class="company-logo me-2" alt="Company Logo">';
       } else {
           echo '<div class="company-logo bg-secondary me-2 d-flex align-items-center justify-content-center">
                   <i class="bi bi-cake text-white"></i>
                 </div>';
       }
       
       echo '<span class="text-truncate ' . ($is_unread ? 'fw-bold' : '') . '">
               ' . htmlspecialchars($company['company_display_name'] ?? 'Reward Provider') . '
             </span>
               </div>
           </td>
           <td>
               <span class="' . ($is_unread ? 'fw-bold' : '') . '">
                   ' . htmlspecialchars($message['subject']) . '
               </span>
           </td>
           <td class="text-end date-col ' . ($is_unread ? 'fw-bold' : '') . '">
               ' . $formatted_date . '
           </td>
       </tr>';
   }
}

?>
                                   </tbody>
                               </table>
                           </div>

<?php
if ($total_pages > 1) {
   echo '
   <div class="d-flex justify-content-between align-items-center p-3 border-top">
       <div class="text-muted">
           Showing ' . (($page - 1) * $per_page + 1) . ' to ' . 
           min($page * $per_page, $total_messages) . ' of ' . 
           $total_messages . ' messages
       </div>
       <nav>
           <ul class="pagination mb-0">';
           
           $show_pages = 5;
           
           // Always show first page
           $url_params = ($sort ? "&sort=" . urlencode($sort) : '') .
                        ($order ? "&order=" . urlencode($order) : '') .
                        ($search ? "&search=" . urlencode($search) : '');
           
           echo '<li class="page-item ' . (1 === $page ? 'active' : '') . '">
                   <a class="page-link" href="?page=1' . $url_params . '">1</a>
                 </li>';

           // Show first few pages after 1
           if ($page <= $show_pages + 3) {
               for ($i = 2; $i <= $show_pages; $i++) {
                   if ($i < $page - 2) continue;
                   echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                           <a class="page-link" href="?page=' . $i . $url_params . '">' . $i . '</a>
                         </li>';
               }
           } else {
               echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
           }

           // Show current page range
           $start = max($show_pages + 1, $page - 2);
           $end = min($total_pages - $show_pages, $page + 2);
           for ($i = $start; $i <= $end; $i++) {
               echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                       <a class="page-link" href="?page=' . $i . $url_params . '">' . $i . '</a>
                     </li>';
           }

           // Show ellipsis before last pages
           if ($page < $total_pages - ($show_pages + 2)) {
               echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
           }

           // Show last few pages
           for ($i = max($total_pages - $show_pages + 1, $end + 1); $i <= $total_pages; $i++) {
               echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                       <a class="page-link" href="?page=' . $i . $url_params . '">' . $i . '</a>
                     </li>';
           }

   echo '
           </ul>
       </nav>
   </div>';
}
?>
                       </div>
                   </div>
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
            
            <!-- Starred Mail -->
            <div class="tab-pane fade" id="starred">
                <div class="mail-content">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-star" style="font-size: 3rem;"></i>
                        <p class="mt-3">No starred messages</p>
                        <p class="small">Star important messages to see them here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to update sort order
function updateSort(value) {
    const [sortBy, sortOrder] = value.split('-');
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('sort', sortBy);
    searchParams.set('order', sortOrder);
    window.location.search = searchParams.toString();
}

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
}

document.addEventListener('DOMContentLoaded', function() {
    // Count unread messages
    const unreadCount = document.querySelectorAll('.message-row.unread').length;
    const unreadBadge = document.getElementById('unreadCount');
    if (unreadBadge && unreadCount > 0) {
        unreadBadge.textContent = unreadCount;
        unreadBadge.style.display = 'inline-block';
    }
    
    // Handle tab clicks
    document.querySelectorAll('.nav-tab-clean a').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var targetHash = this.getAttribute('href');
            activateTab(targetHash);
        });
    });
    
    // Message row click handler
    document.querySelectorAll('.message-row').forEach(row => {
        row.addEventListener('click', function() {
            const messageId = this.dataset.messageId;
            const server = this.dataset.server;
            window.location.href = `/myaccount/mail-read?id=${messageId}&server=${server}`;
        });
    });

    // Select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const messageCheckboxes = document.querySelectorAll('.message-checkbox');

    selectAllCheckbox.addEventListener('change', function() {
        messageCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            updateRowSelection(checkbox);
        });
    });

    messageCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRowSelection(this);
            updateSelectAllState();
        });
    });

    // Bulk actions
    document.querySelectorAll('.bulk-action').forEach(action => {
        action.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const selectedIds = Array.from(document.querySelectorAll('.message-checkbox:checked'))
                                   .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Please select at least one message');
                return;
            }

            const actionType = this.dataset.action;
            
            if (actionType === 'delete' && !confirm('Are you sure you want to delete the selected messages?')) {
                return;
            }

            try {
                const response = await fetch('/api/messages/bulk-action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        action: actionType,
                        messageIds: selectedIds
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            }
        });
    });

    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', () => {
        location.reload();
    });
});

function updateRowSelection(checkbox) {
    const row = checkbox.closest('.message-row');
    row.classList.toggle('selected', checkbox.checked);
}

function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('select-all');
    const messageCheckboxes = document.querySelectorAll('.message-checkbox');
    const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
    
    selectAllCheckbox.checked = checkedBoxes.length === messageCheckboxes.length;
    selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < messageCheckboxes.length;
}
</script>



<?PHP
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
