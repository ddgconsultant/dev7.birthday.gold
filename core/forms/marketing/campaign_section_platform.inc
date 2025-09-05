<?php
// Platform selection section
$section_title = 'Select Platform';
$section_icon = 'broadcast';
include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_open.inc');

// Check if external platforms exist
$has_external_platforms = false;
if (!empty($platforms)) {
    foreach ($platforms as $platform) {
        if ($platform['platform_type'] != 'birthday_gold_internal') {
            $has_external_platforms = true;
            break;
        }
    }
}

echo '
            <div class="mb-4">
                <label class="form-label">Choose your marketing platform <span class="text-danger">*</span></label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card platform-choice h-100 border-primary" data-platform="birthday-gold" onclick="selectPlatform(\'birthday-gold\')">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-gem display-4 text-primary mb-3"></i>
                                <h5>Birthday.Gold Platform</h5>
                                <p class="text-muted small flex-grow-1">Use our integrated marketing platform with tracking and analytics</p>
                                <div class="form-check mt-auto">
                                    <input class="form-check-input" type="radio" name="platform_choice" id="choice_birthday" value="birthday-gold" checked required>
                                    <label class="form-check-label" for="choice_birthday">
                                        Built-in Platform
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">';

if ($has_external_platforms) {
    // Show External Platform option if platforms exist
    echo '
                        <div class="card platform-choice h-100" data-platform="external" onclick="selectPlatform(\'external\')">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-link-45deg display-4 text-success mb-3"></i>
                                <h5>External Platform</h5>
                                <p class="text-muted small flex-grow-1">Choose from your connected Facebook, Google Ads, Instagram, or other platforms</p>
                                <div class="form-check mt-auto">
                                    <input class="form-check-input" type="radio" name="platform_choice" id="choice_external" value="external" required>
                                    <label class="form-check-label" for="choice_external">
                                        External Platform
                                    </label>
                                </div>
                            </div>
                        </div>';
} else {
    // Show Create Platform card if no external platforms exist (styled to match height)
    echo '
                        <div class="card h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <i class="bi bi-plus-circle display-4 text-muted mb-3"></i>
                                <h5>Create External Platform</h5>
                                <p class="text-muted small mb-3">Connect Facebook, Google Ads, Instagram, or other platforms to expand your reach</p>
                                <div class="mt-auto">
                                    <a href="/myaccount/marketing/platform-create.php" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Create Platform
                                    </a>
                                </div>
                            </div>
                        </div>';
}

echo '
                    </div>
                </div>
            </div>';

// Only show the form sections if external platforms exist
if ($has_external_platforms) {
    echo '
            <div id="campaign-form-sections" style="display: block;">
                <div class="row">
                    <div class="col-md-6" id="external-platform-selector" style="display: none;">
                        <div class="mb-3">
                            <label for="platform_id" class="form-label">External Platform <span class="text-danger">*</span></label>
                            <select class="form-select" id="platform_id" name="platform_id">
                                <option value="">Select external platform...</option>';

    foreach ($platforms as $platform) {
        if ($platform['platform_type'] != 'birthday_gold_internal') {
            $selected = (isset($_POST['platform_id']) && $_POST['platform_id'] == $platform['platform_id']) ? ' selected' : '';
            echo '
                                <option value="' . $platform['platform_id'] . '"' . $selected . '>
                                    ' . htmlspecialchars($platform['platform_name']) . '
                                </option>';
        }
    }

    echo '
                            </select>
                            <small class="text-muted">Choose from your connected external platforms</small>
                        </div>
                    </div>
                    <div class="col-md-6" id="birthday-platform-info" style="display: block;">
                        <div class="mb-3">
                            <label class="form-label">Platform</label>
                            <div class="form-control-plaintext">
                                <i class="bi bi-gem me-2"></i><strong>Birthday.Gold Marketing Platform</strong>
                            </div>
                            <small class="text-muted">Integrated platform with built-in tracking and analytics</small>
                            <input type="hidden" name="platform_id" value="0" id="birthday_platform_input">
                        </div>
                    </div>
                </div>
            </div>';
} else {
    // If no external platforms, always use Birthday.Gold (and show it by default)
    echo '
            <div id="campaign-form-sections" style="display: block;">
                <div class="row">
                    <div class="col-12" id="birthday-platform-info" style="display: block;">
                        <div class="mb-3">
                            <label class="form-label">Platform</label>
                            <div class="form-control-plaintext">
                                <i class="bi bi-gem me-2"></i><strong>Birthday.Gold Marketing Platform</strong>
                            </div>
                            <small class="text-muted">Integrated platform with built-in tracking and analytics</small>
                            <input type="hidden" name="platform_id" value="0" id="birthday_platform_input">
                        </div>
                    </div>
                </div>
            </div>';
}

include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_close.inc');

// Add JavaScript for platform selection
echo '
<script>
function selectPlatform(platform) {
    const formSections = document.getElementById("campaign-form-sections");
    const externalSelector = document.getElementById("external-platform-selector");
    const birthdayInfo = document.getElementById("birthday-platform-info");
    const birthdayInput = document.getElementById("birthday_platform_input");
    const externalPlatformSelect = document.getElementById("platform_id");
    
    // Budget related elements
    const budgetFields = document.getElementById("budget-fields");
    const birthdayBudgetNote = document.getElementById("birthday-budget-note");
    const budgetInput = document.getElementById("budget");
    const budgetTypeSelect = document.getElementById("budget_type");
    const budgetRequired = document.getElementById("budget-required");
    const budgetTypeRequired = document.getElementById("budget-type-required");
    
    // Clear previous selections
    document.querySelectorAll(".platform-choice").forEach(card => {
        card.classList.remove("border-primary");
    });
    
    // Highlight selected platform
    event.currentTarget.classList.add("border-primary");
    
    // Select the radio button
    const radio = event.currentTarget.querySelector("input[type=radio]");
    if (radio) {
        radio.checked = true;
    }
    
    // Show form sections
    formSections.style.display = "block";
    
    if (platform === "birthday-gold") {
        // Birthday.Gold platform selected
        if (externalSelector) externalSelector.style.display = "none";
        if (birthdayInfo) birthdayInfo.style.display = "block";
        if (birthdayInput) birthdayInput.disabled = false;
        if (externalPlatformSelect) {
            externalPlatformSelect.disabled = true;
            externalPlatformSelect.removeAttribute("required");
        }
        
        // Update campaign types for Birthday.Gold
        if (typeof updateCampaignTypes === "function") {
            updateCampaignTypes("birthday-gold");
        }
        
        // Hide budget fields for Birthday.Gold
        if (budgetFields) budgetFields.style.display = "none";
        if (birthdayBudgetNote) birthdayBudgetNote.style.display = "block";
        if (budgetInput) {
            budgetInput.removeAttribute("required");
            budgetInput.value = "0";
        }
        if (budgetTypeSelect) budgetTypeSelect.removeAttribute("required");
        if (budgetRequired) budgetRequired.style.display = "none";
        if (budgetTypeRequired) budgetTypeRequired.style.display = "none";
    } else if (platform === "external") {
        // External platform selected
        if (externalSelector) externalSelector.style.display = "block";
        if (birthdayInfo) birthdayInfo.style.display = "none";
        if (birthdayInput) birthdayInput.disabled = true;
        if (externalPlatformSelect) {
            externalPlatformSelect.disabled = false;
            externalPlatformSelect.setAttribute("required", "required");
        }
        
        // Update campaign types for external platform
        if (typeof updateCampaignTypes === "function") {
            // Get the selected external platform name if one is selected
            const selectedOption = externalPlatformSelect.options[externalPlatformSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                updateCampaignTypes("external", selectedOption.textContent.trim());
            } else {
                updateCampaignTypes("external");
            }
        }
        
        // Show budget fields for external platforms
        if (budgetFields) budgetFields.style.display = "block";
        if (birthdayBudgetNote) birthdayBudgetNote.style.display = "none";
        if (budgetInput) budgetInput.setAttribute("required", "required");
        if (budgetTypeSelect) budgetTypeSelect.setAttribute("required", "required");
        if (budgetRequired) budgetRequired.style.display = "inline";
        if (budgetTypeRequired) budgetTypeRequired.style.display = "inline";
    }
}

// Auto-select Birthday.Gold on page load (it\'s the default)
document.addEventListener("DOMContentLoaded", function() {
    const birthdayChoice = document.querySelector("[data-platform=\'birthday-gold\']");
    if (birthdayChoice) {
        // Trigger the selection to show the form sections
        selectPlatform.call(birthdayChoice, "birthday-gold");
    }
});
</script>';
?>