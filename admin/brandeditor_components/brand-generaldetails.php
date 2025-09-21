<?php


#$company= $app->getcompanydetails($cid);
// Fetch the company details (make sure $company is already fetched)
$localpanel_company_name = $company['company_name'] ?? 'Unknown Brand';
$localpanel_company_joined = date('F d, Y', strtotime($company['create_dt'] ?? 'now')); // Assuming 'joined_date' exists in your company record.
$localpanel_company_hq = $company['hq_address'] ?? 'Unknown Headquarters';
$localpanel_company_email = $company['contact_email'] ?? 'No email available';
$localpanel_company_phone = $company['contact_phone'] ?? 'No phone number available';
$localpanel_company_customer_service = $company['customer_service_phone'] ?? 'No customer service number available';

// Assuming you have social media links stored
$localpanel_company_facebook = $company['facebook_link'] ?? '#';
$localpanel_company_twitter = $company['twitter_link'] ?? '#';
$localpanel_company_linkedin = $company['linkedin_link'] ?? '#';
$localpanel_company_instagram = $company['instagram_link'] ?? '#';

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
    <h1 class="mb-4">Brand General Information</h1>
    <div class="row">
        <!-- Brand Logo -->
        <div class="col-md-2">
            <img class="img-fluid h-30 w-30 object-fit-cover" src="' . $display->companyimage($company['company_id'] . '/' . $company['company_logo']) . '" alt="Brand Logo" >
        </div>

        <!-- Brand Details -->
        <div class="col-md-10">
    <!-- Brand Name and Joining Date -->
    <div class="card info-card bg-light mb-3">
        <div class="card-body">
            <p class="card-text h2"><strong>Name:</strong> ' . htmlspecialchars($localpanel_company_name) . '</p>
            <p class="card-text"><strong>Joined:</strong> ' . htmlspecialchars($localpanel_company_joined) . '</p>
        </div>
    </div>

    <!-- Locations -->
    <div class="card info-card d-none">
        <div class="card-body">
            <h5 class="card-title">Locations</h5>
            <ul class="list-group">
                <li class="list-group-item"><strong>Headquarters:</strong> ' . htmlspecialchars($localpanel_company_hq) . '</li>
                <!-- Add other branches if applicable -->
                <li class="list-group-item"><strong>Branch 1:</strong> 456 Elm St, Los Angeles, CA, USA</li>
                <li class="list-group-item"><strong>Branch 2:</strong> 789 Maple St, Chicago, IL, USA</li>
                <li class="list-group-item"><strong>International Office:</strong> 101 Pine St, London, UK</li>
            </ul>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="card info-card  d-none">
        <div class="card-body">
            <h5 class="card-title">Contact Information</h5>
            <p class="card-text"><strong>Email:</strong> ' . htmlspecialchars($localpanel_company_email) . '</p>
            <p class="card-text"><strong>Phone:</strong> ' . htmlspecialchars($localpanel_company_phone) . '</p>
            <p class="card-text"><strong>Customer Service:</strong> ' . htmlspecialchars($localpanel_company_customer_service) . '</p>
        </div>
    </div>

    <!-- Social Media Links -->
    <div class="card info-card">
        <div class="card-body">
            <h5 class="card-title">Social Media</h5>
            <ul class="list-group">
                <li class="list-group-item"><strong>Facebook:</strong> <a href="' . htmlspecialchars($localpanel_company_facebook) . '">facebook.com/awesomebrand</a></li>
                <li class="list-group-item"><strong>Twitter:</strong> <a href="' . htmlspecialchars($localpanel_company_twitter) . '">twitter.com/awesomebrand</a></li>
                <li class="list-group-item"><strong>LinkedIn:</strong> <a href="' . htmlspecialchars($localpanel_company_linkedin) . '">linkedin.com/company/awesomebrand</a></li>
                <li class="list-group-item"><strong>Instagram:</strong> <a href="' . htmlspecialchars($localpanel_company_instagram) . '">instagram.com/awesomebrand</a></li>
            </ul>
        </div>
    </div>
</div>
';