// Helper function to generate content hash
const generateHash = async (content) => {
    const msgBuffer = new TextEncoder().encode(content);
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    return hashHex;
};

// Policy validation rules
const validatePolicy = (data) => {
    const errors = {};
    
    if (!data.policy_name?.trim()) {
        errors.policy_name = 'Policy name is required';
    }
    
    if (!data.policy_url?.trim()) {
        errors.policy_url = 'Policy URL is required';
    } else {
        try {
            new URL(data.policy_url);
        } catch (e) {
            errors.policy_url = 'Invalid URL format';
        }
    }
    
    return errors;
};

// Initialize policy manager functionality
document.addEventListener('DOMContentLoaded', function() {
    const policyForm = document.getElementById('policyForm');
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    
    // Handle policy verification
    document.querySelectorAll('.verify-policy').forEach(button => {
        button.addEventListener('click', async function() {
            const policyId = this.dataset.policyId;
            const card = this.closest('.policy-card');
            const badge = card.querySelector('.verification-badge');
            
            try {
                badge.classList.add('verifying');
                button.disabled = true;
                
                // Fetch current content
                const response = await fetch('verify-policy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ policy_id: policyId })
                });
                
                const result = await response.json();
                
                if (result.changed) {
                    // Update UI to show changes
                    badge.classList.remove('bg-success', 'bg-secondary');
                    badge.classList.add('bg-warning');
                    badge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Changed';
                    
                    // Show change notification
                    const toast = new bootstrap.Toast(document.getElementById('policyChangeToast'));
                    document.getElementById('policyChangeName').textContent = result.policy_name;
                    toast.show();
                } else {
                    // Update UI to show verification
                    badge.classList.remove('bg-warning', 'bg-secondary');
                    badge.classList.add('bg-success');
                    badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Verified';
                }
            } catch (error) {
                console.error('Policy verification failed:', error);
                badge.classList.remove('bg-success', 'bg-warning');
                badge.classList.add('bg-danger');
                badge.innerHTML = '<i class="bi bi-x-circle me-1"></i>Error';
            } finally {
                badge.classList.remove('verifying');
                button.disabled = false;
            }
        });
    });
    
    // Handle policy history viewing
    document.querySelectorAll('.view-history').forEach(button => {
        button.addEventListener('click', async function() {
            const policyId = this.dataset.policyId;
            const historyList = document.getElementById('historyList');
            historyList.innerHTML = ''; // Clear existing history
            
            try {
                const response = await fetch('get-policy-history.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ policy_id: policyId })
                });
                
                const history = await response.json();
                
                history.forEach(version => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">Version ${version.version}</h6>
                                <p class="mb-1 small text-muted">
                                    ${new Date(version.created_at).toLocaleString()}
                                </p>
                            </div>
                            <div class="text-end">
                                <code class="small truncate-hash">${version.content_hash}</code>
                                <div class="btn-group btn-group-sm mt-1">
                                    <button type="button" class="btn btn-outline-secondary view-version"
                                            data-version-id="${version.version_id}">
                                        View
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary restore-version"
                                            data-version-id="${version.version_id}">
                                        Restore