<?php
// Campaign scheduling section (Birthday.Gold platform only - no budget required)
$section_title = 'Schedule';
$section_icon = 'calendar-week';
include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_open.inc');

echo '
        <div class="alert alert-info mb-3">
            <i class="bi bi-gem me-2"></i>
            <strong>Birthday.Gold Platform:</strong> No budget required - included with your account at no additional cost.
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="' . htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) . '" 
                           min="' . date('Y-m-d') . '">
                    <div class="form-text">When should this campaign begin?</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="' . htmlspecialchars($_POST['end_date'] ?? '') . '">
                    <div class="form-text">Leave empty for ongoing campaigns</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <label for="status" class="form-label">Initial Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft"' . (($_POST['status'] ?? 'draft') == 'draft' ? ' selected' : '') . '>Draft - Not yet active</option>
                        <option value="active"' . (($_POST['status'] ?? '') == 'active' ? ' selected' : '') . '>Active - Start immediately</option>
                    </select>
                    <div class="form-text">You can change the status at any time after creation</div>
                </div>
            </div>
        </div>';

// Hidden budget fields for Birthday.Gold (set to 0)
echo '
        <input type="hidden" name="budget" value="0">
        <input type="hidden" name="budget_type" value="total">';

include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_close.inc');
?>