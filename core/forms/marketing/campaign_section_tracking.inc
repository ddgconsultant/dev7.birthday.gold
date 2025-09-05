<?php
// Campaign tracking and links section
$section_title = 'Tracking & Links';
$section_icon = 'graph-up';
include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_open.inc');

echo '
        <div class="mb-3">
            <label for="tracking_link_action" class="form-label">Tracking Link Action</label>
            <select class="form-select" id="tracking_link_action" name="tracking_link_action">
                <option value="forward_signup"' . (($_POST['tracking_link_action'] ?? 'forward_signup') == 'forward_signup' ? ' selected' : '') . '>Forward to Signup</option>
                <option value="forward_rewards"' . (($_POST['tracking_link_action'] ?? '') == 'forward_rewards' ? ' selected' : '') . '>Forward to Rewards</option>
                <option value="forward_enrollments"' . (($_POST['tracking_link_action'] ?? '') == 'forward_enrollments' ? ' selected' : '') . '>Forward to Enrollments</option>
                <option value="forward_url"' . (($_POST['tracking_link_action'] ?? '') == 'forward_url' ? ' selected' : '') . '>Forward to Custom URL</option>
                <option value="forward_campaign"' . (($_POST['tracking_link_action'] ?? '') == 'forward_campaign' ? ' selected' : '') . '>Forward to Another Campaign</option>
            </select>
            <div class="form-text">Choose where tracked clicks should be redirected</div>
        </div>
        
        <div class="mb-3" id="destination_url_field" style="display: none;">
            <label for="destination_url" class="form-label">Destination URL</label>
            <input type="url" class="form-control" id="destination_url" name="destination_url" 
                   value="' . htmlspecialchars($_POST['destination_url'] ?? '') . '">
            <div class="form-text">Enter the full URL where clicks should be redirected</div>
        </div>
        
        <div class="mb-3" id="destination_campaign_field" style="display: none;">
            <label for="destination_campaign_id" class="form-label">Destination Campaign</label>
            <select class="form-select" id="destination_campaign_id" name="destination_campaign_id">
                <option value="">Select campaign...</option>
                <!-- Campaign options would be populated here -->
            </select>
        </div>';

include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_close.inc');
?>

<script>
document.getElementById('tracking_link_action').addEventListener('change', function() {
    const urlField = document.getElementById('destination_url_field');
    const campaignField = document.getElementById('destination_campaign_field');
    
    if (this.value === 'forward_url') {
        urlField.style.display = 'block';
        campaignField.style.display = 'none';
    } else if (this.value === 'forward_campaign') {
        urlField.style.display = 'none';
        campaignField.style.display = 'block';
    } else {
        urlField.style.display = 'none';
        campaignField.style.display = 'none';
    }
});

// Trigger on page load
document.getElementById('tracking_link_action').dispatchEvent(new Event('change'));
</script>