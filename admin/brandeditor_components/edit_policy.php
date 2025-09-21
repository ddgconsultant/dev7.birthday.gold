<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php'); ?>



<?

#-------------------------------------------------------------------------------
# HANDLE THE UPDATE ATTEMPT
#-------------------------------------------------------------------------------



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company_id'];
    $terms_url = $_POST['terms_url'] ?? '';
    $privacy_url = $_POST['privacy_url'] ?? '';
    $custom_url = $_POST['custom_url'] ?? '';
    foreach (['terms', 'privacy', 'custom'] as $policy) {
        // Expire any previous matching records
        $expire_query = "UPDATE bg_company_attributes SET status='replaced', end_dt=now() 
WHERE company_id=? AND (`status`='active' OR `status`='' OR `status` IS NULL) AND end_dt IS NULL and name=? and type='url' and description !=?";
        $stmt = $database->prepare($expire_query);
        $stmt->execute([$company_id, $policy, $_POST[$policy . '_url']]);
    }
    $insert_query = "INSERT INTO bg_company_attributes (company_id, `type`, `name`, description, `status`, create_dt, modify_dt, `grouping`, start_dt) 
VALUES (?, 'url', ?, ?, 'active', now(), now(), 'policies', now())";

    $stmt = $database->prepare($insert_query);

    if (!empty($terms_url)) {
        $stmt->execute([$company_id, 'terms', $terms_url]);
    }

    if (!empty($privacy_url)) {
        $stmt->execute([$company_id, 'privacy', $privacy_url]);
    }

    if (!empty($custom_url)) {
        $stmt->execute([$company_id, strtolower($_POST['custom_policy']), $custom_url]);
    }

    echo "<b>Inserted URLs for company ID: $company_id </b>";
}









#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manual Processor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript">
        function openWindow(url) {
            window.open(url, 'myNamedWindow');
        }
    </script>


</head>

<body>


    <?php

    $company_id = $_REQUEST['company_id'];

    // Fetch companies from bg_companies
    $query = "SELECT company_id, company_name, company_url, info_url, signup_url FROM bg_companies WHERE  company_id=?";
    $stmt = $database->prepare($query);
    $stmt->execute([$company_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo '<h1>Manual Processor</h1>
# of companies: ' . count($companies) . '
<div class="container">
<div class="accordion" id="companyAccordion">';


    foreach ($companies as $company) {
        $company_id = $company['company_id'];
        $policyx['collectedtag'] = '';
        $headerColor = 'bg-danger'; // Initialize header color
        $linkColor = 'text-black';
        $urlx['terms'] = $urlx['privacy'] = '';
        // Check if bg_company_attributes are found for the company_id (Add your logic here)
        // If not found, set headerColor to 'bg-danger'

        $query = "SELECT *   FROM bg_company_attributes WHERE company_id= ? and  `type`='url' and `grouping`='policies'";
        $stmt = $database->prepare($query);
        $stmt->execute([$company_id]);
        $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowcnt = count($policies);

        $urlx['count'] = 0;
        if ($policies) {
            $headerColor = 'bg-warning';
            foreach ($policies as $policy) {
                if (!empty($policy['description'])) {
                    if ($policy['name'] == 'terms') {
                        $urlx['terms'] = $policy['description'];
                        $urlx['count']++;
                    }
                    if ($policy['name'] == 'privacy') {
                        $urlx['privacy'] = $policy['description'];
                        $urlx['count']++;
                    }
                    $policyx['collectedtag'] = ' - <small> collected: ' . $policy['create_dt'] . '</small>';
                }
            }
            if ($urlx['count'] >= 2) {
                $headerColor = 'bg-success';
                $linkColor = 'text-white';
            }
        }
        echo '<div class="accordion-item">
<h2 class="accordion-header  fw-bold " id="heading' . $company_id . '">
<button class="accordion-button ' . $headerColor . '  bg-opacity-50 ' . $linkColor . ' fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $company_id . '" aria-expanded="true" aria-controls="collapse' . $company_id . '">
[' . $urlx['count'] . '/' . $rowcnt . '] | Company ID: ' . $company_id . ' - ' . $company['company_name'] . ' ' . $policyx['collectedtag'] . '
</button>
</h2>
<div id="collapse' . $company_id . '" class="accordion-collapse show" aria-labelledby="heading' . $company_id . '" data-bs-parent="#companyAccordion">
<div class="accordion-body">';

        $urls = [$company['company_url'], $company['info_url'], $company['signup_url']];
        foreach ($urls as $url) {
            if (!$url) continue;
            echo "<a href='javascript:void(0);' onclick='openWindow(\"$url\");'>$url</a><br>";
        }

        echo '<form action="manual_policies.php" method="post" class="mt-4">
<input type="hidden" name="company_id" value="' . $company_id . '">

<div class="d-flex mb-3 align-items-center">
<label for="terms_url_' . $company_id . '" class="form-label me-2" style="width: 120px;">';

        if (!empty($urlx['terms'])) {
            echo '<a href="' . $urlx['terms'] . '" target="_blank">Terms URL:</a>';
        } else {
            echo 'Terms URL:';
        }

        echo '</label>
<input type="text" class="form-control" id="terms_url_' . $company_id . '" name="terms_url" value="' . $urlx['terms'] . '">
</div>

<div class="d-flex mb-3 align-items-center">
<label for="privacy_url_' . $company_id . '" class="form-label me-2" style="width: 120px;">';

        if (!empty($urlx['privacy'])) {
            echo '<a href="' . $urlx['privacy'] . '" target="_blank">Privacy URL:</a>';
        } else {
            echo 'Privacy URL:';
        }

        echo '</label>
<input type="text" class="form-control" id="privacy_url_' . $company_id . '" name="privacy_url" value="' . $urlx['privacy'] . '">
</div>';


        echo '<div class="row mb-3 align-items-center">
<div class="col-3">
<button id="toggleBtn_' . $company_id . '" type="button" class="btn btn-secondary" onclick="togglePolicyTypeDiv(' . $company_id . ')">Add Policy/Type</button>
</div>
<div id="policyTypeDiv_' . $company_id . '" class="col-9 d-none">
<div class="row">
<div class="col-5 d-flex align-items-center">
<label for="type_' . $company_id . '" class="form-label me-2" style="width: 120px;">Policy Name</label>
<input type="text" class="form-control" id="type_' . $company_id . '" name="custom_policy">
</div>
<div class="col-7 d-flex align-items-center">
<label for="policy_' . $company_id . '" class="form-label me-2" >URL</label>
<input type="text" class="form-control" id="policy_' . $company_id . '" name="custom_url">
</div>
</div>
</div>
</div>
<script>
function togglePolicyTypeDiv(company_id) {
var div = document.getElementById("policyTypeDiv_" + company_id);
div.classList.toggle("d-none");
}
</script>';





        echo '
<button type="submit" class="btn btn-primary">Submit</button>
</form>
</div>
</div>
</div>';
    }
    ?>
    </div>
    </div>


</body>

</html>