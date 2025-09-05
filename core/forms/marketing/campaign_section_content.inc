<?php
// Campaign content and targeting section
$section_title = 'Content & Targeting';
$section_icon = 'bullseye';
include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_open.inc');

echo '
        <div class="mb-3">
            <label for="campaign_content" class="form-label">Campaign Content</label>
            <textarea class="form-control" id="campaign_content" name="campaign_content" rows="4">' . 
            htmlspecialchars($_POST['campaign_content'] ?? '') . '</textarea>
            <div class="form-text">Add your campaign copy, creative brief, or content details</div>
        </div>
        
        <div class="mb-3">
            <label for="target_audience" class="form-label">Target Audience</label>
            <textarea class="form-control" id="target_audience" name="target_audience" rows="3">' . 
            htmlspecialchars($_POST['target_audience'] ?? '') . '</textarea>
            <div class="form-text">Describe your target audience demographics, interests, and behaviors</div>
        </div>
        
        <div class="mb-3">
            <label for="campaign_goals" class="form-label">Campaign Goals</label>
            <textarea class="form-control" id="campaign_goals" name="campaign_goals" rows="3">' . 
            htmlspecialchars($_POST['campaign_goals'] ?? '') . '</textarea>
            <div class="form-text">What do you want to achieve with this campaign?</div>
        </div>';

include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_close.inc');
?>