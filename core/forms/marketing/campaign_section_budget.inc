<?php
// Campaign budget and scheduling section
$section_title = 'Budget & Schedule';
$section_icon = 'currency-dollar';
include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_open.inc');

echo '
        <div id="budget-fields">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="budget" class="form-label">Budget Amount <span class="text-danger" id="budget-required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="budget" name="budget" 
                                   step="0.01" min="0" value="' . htmlspecialchars($_POST['budget'] ?? '') . '" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="budget_type" class="form-label">Budget Type <span class="text-danger" id="budget-type-required">*</span></label>
                        <select class="form-select" id="budget_type" name="budget_type" required>
                            <option value="total"' . (($_POST['budget_type'] ?? 'total') == 'total' ? ' selected' : '') . '>Total Budget</option>
                            <option value="daily"' . (($_POST['budget_type'] ?? '') == 'daily' ? ' selected' : '') . '>Daily Budget</option>
                            <option value="monthly"' . (($_POST['budget_type'] ?? '') == 'monthly' ? ' selected' : '') . '>Monthly Budget</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info" id="birthday-budget-note" style="display: none;">
            <i class="bi bi-info-circle me-2"></i>
            <strong>No Budget Required:</strong> Birthday.Gold Marketing Platform is included with your account at no additional cost.
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="' . htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) . '" 
                           min="' . date('Y-m-d') . '">
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

include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/marketing/campaign_section_close.inc');
?>