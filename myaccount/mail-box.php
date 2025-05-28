<?php
// inbox.php
$addClasses[] = 'mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



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


$additionalstyles.='
<style>
.message-row { transition: background-color 0.15s ease-in-out; cursor: pointer; }
.message-row:hover { background-color: rgba(0, 0, 0, .03); }
.message-row.selected { background-color: rgba(13, 110, 253, .1); }
.message-row.unread { background-color: rgba(248, 249, 250, .7); }
.company-logo { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; }
.sort-icon { opacity: 0.3; }
.sort-active .sort-icon { opacity: 1; }
@media (max-width:768px) {
.sender-col { max-width: 120px; }
.date-col { max-width: 70px; }
}
</style>
    ';
    
    
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');

include($dir['core_components'] . '/bg_user_leftpanel.inc');


echo '    
<div class="container main-content mt-0 pt-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Mail Inbox</h2>
  <a href="/myaccount/"  class="btn btn-sm btn-outline-secondary">Back To MyAccount</a>
</div>
';
echo '
<!-- Toolbar -->
<div class="card mb-3">
   <div class="card-body">
       <div class="row align-items-center">
           <div class="col-md-6 mb-2 mb-md-0">
               <div class="btn-group me-2">
                   <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                       Bulk Actions
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
               <button type="button" class="btn btn-outline-secondary" id="refresh-btn">
                   <i class="bi bi-arrow-clockwise"></i>
               </button>
           </div>
           <div class="col-md-6">
               <form class="d-flex" method="GET" id="search-form">
                   <input type="search" name="search" class="form-control me-2" placeholder="Search messages..." 
                          value="' . htmlspecialchars($search) . '">
                   <button class="btn btn-primary" type="submit">
                       <i class="bi bi-search"></i>
                   </button>
               </form>
           </div>
       </div>
   </div>
</div>';



echo '
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
                       <th style="width: 200px;">
                           <a href="?sort=sender&order=' . ($sort === 'sender' && $order === 'asc' ? 'desc' : 'asc') . '" 
                              class="text-decoration-none text-dark ' . ($sort === 'sender' ? 'sort-active' : '') . '">
                               Sender
                               <i class="bi bi-arrow-' . ($order === 'asc' ? 'up' : 'down') . ' sort-icon"></i>
                           </a>
                       </th>
                       <th>
                           <a href="?sort=subject&order=' . ($sort === 'subject' && $order === 'asc' ? 'desc' : 'asc') . '" 
                              class="text-decoration-none text-dark ' . ($sort === 'subject' ? 'sort-active' : '') . '">
                               Subject
                               <i class="bi bi-arrow-' . ($order === 'asc' ? 'up' : 'down') . ' sort-icon"></i>
                           </a>
                       </th>
                       <th class="text-end" style="width: 120px;">
                           <a href="?sort=date&order=' . ($sort === 'date' && $order === 'asc' ? 'desc' : 'asc') . '" 
                              class="text-decoration-none text-dark ' . ($sort === 'date' ? 'sort-active' : '') . '">
                               Date
                               <i class="bi bi-arrow-' . ($order === 'asc' ? 'up' : 'down') . ' sort-icon"></i>
                           </a>
                       </th>
                   </tr>
               </thead>
               <tbody>';

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

echo '
               </tbody>
           </table>
       </div>';


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

echo '
       </div>
   </div>
</div>
</div>
   </div>
</div>';

?>


<script>
document.addEventListener('DOMContentLoaded', function() {
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
