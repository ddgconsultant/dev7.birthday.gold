<script>
document.addEventListener("DOMContentLoaded", function() {
    const businesses = ' . json_encode($enrollments) . ';
    
    function loadBusiness(businessId) {
        const business = businesses.find(b => b.company_id === businessId);
        if (!business) return;
        
        // Update active state in business list
        document.querySelectorAll(".business-item").forEach(item => {
            item.classList.remove("active");
        });
        document.querySelector(`[data-business-id="${businessId}"]`).classList.add("active");
        
        // Update business details panel
        document.getElementById("businessName").textContent = business.company_name;
        document.getElementById("businessDescription").textContent = business.description || "No description available";
        // Format and display enrollment hints
        const hints = business.enrollment_hints ? 
            business.enrollment_hints.split('\n\n').map(hint => `<li class="mb-2">${hint}</li>`).join('') :
            '<li>No specific enrollment instructions available</li>';
        document.getElementById("enrollmentHelp").innerHTML = `<ul class="list-unstyled">${hints}</ul>`;
        
        // Update hint count
        const hintCount = business.enrollment_hints ? business.enrollment_hints.split('\n\n').length : 0;
        document.getElementById("hintCount").textContent = `${hintCount} Hint${hintCount !== 1 ? 's' : ''}`;
        
        // Store current business ID for hint addition
        document.getElementById("currentBusinessId").value = business.company_id;
        
        // Update BGREB extension
        window.postMessage({
            type: "BGREB_LOAD_URL",
            url: business.signup_url,
            businessId: business.company_id
        }, "*");
        
        // Update form action buttons
        document.getElementById("currentBusinessId").value = business.company_id;
    }
    
    // Load first business by default
    if (businesses.length > 0) {
        loadBusiness(businesses[0].company_id);
    }
    
    // Add click handlers for business list items
    document.querySelectorAll(".business-item").forEach(item => {
        item.addEventListener("click", () => {
            loadBusiness(parseInt(item.dataset.businessId));
        });
    });
    
    // Handle success/failure actions
    document.getElementById("successBtn").addEventListener("click", () => {
        const businessId = document.getElementById("currentBusinessId").value;
        submitEnrollmentStatus("success", businessId);
    });
    
    document.getElementById("failureBtn").addEventListener("click", () => {
        const businessId = document.getElementById("currentBusinessId").value;
        const reason = document.getElementById("failureReason").value;
        if (!reason) {
            alert("Please provide a failure reason");
            return;
        }
        submitEnrollmentStatus("failure", businessId, reason);
    });
});

<?PHP
// Add Hint Modal Functions
/*
let hintModal;

function showAddHintModal() {
    if (!hintModal) {
        hintModal = new bootstrap.Modal(document.getElementById('addHintModal'));
    }
    document.getElementById('addHintForm').reset();
    hintModal.show();
}

function submitHint(event) {
    event.preventDefault();
    const form = event.target;
    const businessId = document.getElementById("currentBusinessId").value;
    
    const formData = new FormData(form);
    formData.append("action", "add_hint");
    formData.append("company_id", businessId);
    
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            hintModal.hide();
            window.location.reload(); // Refresh to show new hint
        } else {
            alert(data.message || 'Failed to add hint');
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert('Failed to add hint');
    });
    
    return false;
}

function submitEnrollmentStatus(status, businessId, reason = "") {
    const formData = new FormData();
    formData.append("action", "enrollment_" + status);
    formData.append("company_id", businessId);
    if (reason) formData.append("reason", reason);
    
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            window.location.reload();
        }
    })
    .catch(error => console.error("Error:", error));
}
</script>

<script>
document.querySelectorAll('input[name="failureReason"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const otherInput = document.getElementById('otherReasonInput');
        if (this.value === 'other') {
            otherInput.classList.add('show');
            document.getElementById('customFailureReason').focus();
        } else {
            otherInput.classList.remove('show');
        }
    });
});

function submitFailureReason() {
    const selectedReason = document.querySelector('input[name="failureReason"]:checked');
    let failureReason = selectedReason.value;
    
    if (failureReason === 'other') {
        failureReason = document.getElementById('customFailureReason').value;
        if (!failureReason.trim()) {
            alert('Please enter a custom reason');
            return;
        }
    }
    
    submitEnrollmentStatus('failure', currentBusinessId, failureReason);
}
*/
?>


function copyToClipboard(text) {
navigator.clipboard.writeText(text).then(function() {
// Create a temporary tooltip
const tooltip = document.createElement('div');
tooltip.className = 'position-fixed bg-dark text-white px-2 py-1 rounded';
tooltip.style.zIndex = '9999';
tooltip.textContent = 'Copied!';
document.body.appendChild(tooltip);

// Position near cursor
document.addEventListener('mousemove', function handler(e) {
tooltip.style.left = (e.clientX + 10) + 'px';
tooltip.style.top = (e.clientY + 10) + 'px';
document.removeEventListener('mousemove', handler);
});

// Remove after delay
setTimeout(() => tooltip.remove(), 1000);
});
}
</script>


<script>
    function openEnrollmentWindow(signupUrl, userId, aid, bid) {
    console.log("openEnrollmentWindow called with:", {signupUrl, userId, aid, bid});
    
    // Ensure we have valid parameters
    if (!signupUrl || !userId || !aid) {
        console.error("Missing required parameters:", {signupUrl, userId, aid, bid});
        return;
    }
    
    try {
        // First process the user data so extension has it
        processUser(userId, aid, bid);
        
        // Base64 encode the parameters
        const encodedUserId = btoa(userId);
        const encodedAid = btoa(aid);
        
        // Add parameters to URL
      //  const urlWithParams = `${signupUrl}`;
      //  console.log("Opening URL:", urlWithParams);
        
        // Then open in specific window named "enrollerwindow"
        const enrollWindow = window.open(signupUrl, "enrollerwindow", 
            "width=1024,height=1200,toolbar=yes,scrollbars=yes,menubar=yes,resizable=yes,status=yes");
            
        // Ensure window opened successfully
        if (enrollWindow) {
            console.log("Window opened successfully");
            enrollWindow.focus();
        } else {
            console.error("Popup was blocked");
            alert("Please allow popups for this site to enroll in rewards programs.");
        }
    } catch (error) {
        console.error("Error opening enrollment window:", error);
    }
}
</script>

<script>
function processUser(userId, aid, bid) {  // Added bid parameter
    console.log('processUser called with:', { userId, aid, bid });  // Added bid to logging
    
    // Convert parameters to numbers if they're strings
    userId = parseInt(userId, 10);
    aid = parseInt(aid, 10);
    bid = parseInt(bid, 10);  // Added conversion for bid
    
    const event = new CustomEvent('processUser', {
        detail: {
            userId: userId,
            aid: aid,
            bid: bid,    // Added bid to event detail
            mode: 'desktop'  
        },
        bubbles: true
    });
    
    console.log('Dispatching event:', event);
    document.dispatchEvent(event);
    console.log('Event dispatched');
}
</script>


<script>
    // Add the JavaScript for auto-closing alerts
document.addEventListener("DOMContentLoaded", function() {
    const alerts = document.querySelectorAll(".alert.alert-dismissible");
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        setTimeout(function() {
            bsAlert.close();
        }, 15000);
    });
});
</script>