<?php
// Campaign basic details section
$section_title = 'Campaign Details';
$section_icon = 'pencil-square';
include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_open.inc');

echo '
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="campaign_name" class="form-label">
                        Campaign Name <span class="text-danger">*</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-1" data-bs-toggle="modal" data-bs-target="#campaignNamingModal">
                            <i class="bi bi-info-circle text-primary"></i>
                        </button>
                    </label>
                    <input type="text" class="form-control" id="campaign_name" name="campaign_name" 
                           value="' . htmlspecialchars($_POST['campaign_name'] ?? '') . '" maxlength="100" required>
                    <div class="form-text">Give your campaign a descriptive name</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="campaign_type" class="form-label">Campaign Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="campaign_type" name="campaign_type" required>
                        <option value="">Select platform first...</option>
                    </select>
                    <div class="form-text" id="campaign_type_help">Campaign type will be available after selecting a platform</div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">' . 
            htmlspecialchars($_POST['description'] ?? '') . '</textarea>
            <div class="form-text">Describe the purpose and goals of this campaign</div>
        </div>';

include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_close.inc');

// Add the Campaign Naming Modal
echo '
<!-- Campaign Naming Tips Modal -->
<div class="modal fade" id="campaignNamingModal" tabindex="-1" aria-labelledby="campaignNamingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="font-size: 90%;">
            <div class="modal-header">
                <h5 class="modal-title" id="campaignNamingModalLabel">
                    <i class="bi bi-lightbulb me-2"></i>Campaign Naming Tips
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>While your Campaign Name does not have to be unique, it is easier to locate the correct campaign in a long list if you give them descriptive names that stand out.</p>
                
                <div class="table-responsive">
                    <table class="table table-sm small">
                        <thead>
                            <tr>
                                <th class="text-success"><i class="bi bi-check-circle me-1"></i>Good Examples</th>
                                <th class="text-danger"><i class="bi bi-x-circle me-1"></i>Poor Examples</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-success">Summer Sale 2025 - Facebook</td>
                                <td class="text-danger">Campaign 1</td>
                            </tr>
                            <tr>
                                <td class="text-success">Holiday Promo - Instagram Stories</td>
                                <td class="text-danger">New Campaign</td>
                            </tr>
                            <tr>
                                <td class="text-success">Back to School - Google Ads</td>
                                <td class="text-danger">Test</td>
                            </tr>
                            <tr>
                                <td class="text-success">Valentine Special - Email</td>
                                <td class="text-danger">Marketing Campaign</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <strong>Pro Tip:</strong> Include the promotion, platform, and time period for easy identification!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
            </div>
        </div>
    </div>
</div>

<script>
// Campaign type options based on platform
const campaignTypes = {
    "birthday-gold": [
        { value: "birthday_rewards", label: "Birthday Rewards Campaign" },
        { value: "email", label: "Email Marketing" },
        { value: "newsletter", label: "Newsletter" },
        { value: "text_message", label: "SMS/Text Message" },
        { value: "loyalty", label: "Loyalty Program" },
        { value: "promotion", label: "Special Promotion" },
        { value: "announcement", label: "Announcement" },
        { value: "survey", label: "Customer Survey" }
    ],
    "facebook": [
        { value: "awareness", label: "Brand Awareness" },
        { value: "traffic", label: "Traffic Generation" },
        { value: "engagement", label: "Post Engagement" },
        { value: "leads", label: "Lead Generation" },
        { value: "conversions", label: "Conversions" },
        { value: "video_views", label: "Video Views" }
    ],
    "google": [
        { value: "search", label: "Search Campaign" },
        { value: "display", label: "Display Campaign" },
        { value: "shopping", label: "Shopping Campaign" },
        { value: "video", label: "Video Campaign" },
        { value: "performance_max", label: "Performance Max" }
    ],
    "instagram": [
        { value: "feed_post", label: "Feed Post" },
        { value: "story", label: "Story Campaign" },
        { value: "reels", label: "Reels Campaign" },
        { value: "shopping", label: "Shopping Tags" },
        { value: "influencer", label: "Influencer Campaign" }
    ],
    "default": [
        { value: "awareness", label: "Brand Awareness" },
        { value: "traffic", label: "Traffic Generation" },
        { value: "engagement", label: "Engagement" },
        { value: "leads", label: "Lead Generation" },
        { value: "sales", label: "Sales/Conversions" },
        { value: "other", label: "Other" }
    ]
};

// Function to update campaign types based on platform
function updateCampaignTypes(platform, platformName = null) {
    const campaignTypeSelect = document.getElementById("campaign_type");
    const campaignTypeHelp = document.getElementById("campaign_type_help");
    
    if (!campaignTypeSelect) return;
    
    // Clear existing options
    campaignTypeSelect.innerHTML = "";
    
    // Determine which types to use
    let types = [];
    
    if (platform === "birthday-gold") {
        types = campaignTypes["birthday-gold"];
        campaignTypeHelp.textContent = "Choose from Birthday.Gold campaign types";
    } else if (platform === "external" && platformName) {
        // Try to match platform name to predefined types
        const lowerName = platformName.toLowerCase();
        if (lowerName.includes("facebook")) {
            types = campaignTypes["facebook"];
        } else if (lowerName.includes("google")) {
            types = campaignTypes["google"];
        } else if (lowerName.includes("instagram")) {
            types = campaignTypes["instagram"];
        } else {
            types = campaignTypes["default"];
        }
        campaignTypeHelp.textContent = `Campaign types for ${platformName}`;
    } else {
        // Default types
        types = campaignTypes["default"];
        campaignTypeHelp.textContent = "Select a campaign type";
    }
    
    // Add default option
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "Select campaign type...";
    campaignTypeSelect.appendChild(defaultOption);
    
    // Add type options
    types.forEach(type => {
        const option = document.createElement("option");
        option.value = type.value;
        option.textContent = type.label;
        campaignTypeSelect.appendChild(option);
    });
    
    // Restore previously selected value if it exists
    const previousValue = "' . htmlspecialchars($_POST['campaign_type'] ?? '') . '";
    if (previousValue && Array.from(campaignTypeSelect.options).some(opt => opt.value === previousValue)) {
        campaignTypeSelect.value = previousValue;
    }
}

// Listen for platform selection changes (this will be called from platform selection)
document.addEventListener("DOMContentLoaded", function() {
    // Check if Birthday.Gold is pre-selected
    const birthdayRadio = document.getElementById("choice_birthday");
    if (birthdayRadio && birthdayRadio.checked) {
        updateCampaignTypes("birthday-gold");
    }
});

// Also listen for external platform dropdown changes
document.addEventListener("DOMContentLoaded", function() {
    const externalPlatformSelect = document.getElementById("platform_id");
    if (externalPlatformSelect) {
        externalPlatformSelect.addEventListener("change", function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                updateCampaignTypes("external", selectedOption.textContent.trim());
            }
        });
    }
});
</script>';
?>