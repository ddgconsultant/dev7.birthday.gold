<?php
// Check if this file is being included
if (!isset($componentmode) || $componentmode != 'include') {
    include_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
}

// Get company id if not already set
if (empty($cid)) {
    $cid = $_REQUEST['cid'] ?? null;
}

// Fetch the company details
$company = $app->getcompanydetails($cid);

// Set up variables for display
$localpanel_company_name = $company['company_name'] ?? 'Unknown Company';
$localpanel_company_joined = date('F d, Y', strtotime($company['create_dt'] ?? 'now'));
$localpanel_company_hq = $company['hq_address'] ?? 'Unknown Headquarters';
$localpanel_company_email = $company['contact_email'] ?? 'No email available';
$localpanel_company_phone = $company['contact_phone'] ?? 'No phone number available';
$localpanel_company_customer_service = $company['customer_service_phone'] ?? 'No customer service number available';

// Social media links
$localpanel_company_facebook = $company['facebook'] ?? '#';
$localpanel_company_twitter = $company['twitter'] ?? '#';
$localpanel_company_instagram = $company['instagram'] ?? '#';
$localpanel_company_tiktok = $company['tiktok'] ?? '#';

$additionalstyles .= '
    <style>
        body {
            padding-top: 20px;
        }
        .info-card {
            margin-bottom: 20px;
        }
        .info-card h5 {
            margin-bottom: 15px;
        }
    </style>
';

echo '
<div class="container-fluid">
    <div class="row">
        <!-- Company Logo -->
        <div class="col-md-2">
            <img class="img-fluid h-30 w-30 object-fit-cover" src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="Company Logo" >
        </div>

        <!-- Company Details -->
        <div class="col-md-10">
            <!-- Company Name and Joining Date -->
            <div class="card info-card bg-light mb-3">
                <div class="card-body">
                    <p class="card-text h2"><strong>Name:</strong> ' . htmlspecialchars($localpanel_company_name) . '</p>
                    <p class="card-text"><strong>Joined:</strong> ' . htmlspecialchars($localpanel_company_joined) . '</p>
                </div>
            </div>

            <!-- Social Media Links -->
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Social Media</h5>
                    <ul class="list-group">
                        <li class="list-group-item"><strong>Facebook:</strong> <a href="' . htmlspecialchars($localpanel_company_facebook) . '" target="_blank">' . htmlspecialchars($localpanel_company_facebook) . '</a></li>
                        <li class="list-group-item"><strong>Twitter:</strong> <a href="' . htmlspecialchars($localpanel_company_twitter) . '" target="_blank">' . htmlspecialchars($localpanel_company_twitter) . '</a></li>
                        <li class="list-group-item"><strong>Instagram:</strong> <a href="' . htmlspecialchars($localpanel_company_instagram) . '" target="_blank">' . htmlspecialchars($localpanel_company_instagram) . '</a></li>
                        <li class="list-group-item"><strong>TikTok:</strong> <a href="' . htmlspecialchars($localpanel_company_tiktok) . '" target="_blank">' . htmlspecialchars($localpanel_company_tiktok) . '</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Category Information -->
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Company Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Category:</strong> ' . htmlspecialchars($company['category'] ?? 'Not specified') . '</p>
                            <p><strong>Status:</strong> ' . htmlspecialchars($company['status'] ?? 'Not specified') . '</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Rewards:</strong> ' . count($company['rewards'] ?? []) . ' active rewards</p>
                            <p><strong>Total Users:</strong> ' . htmlspecialchars($company['total_users'] ?? '0') . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';
?>