<?PHP

$addClasses[] = 'Mail';
$addClasses[] = 'allocationmanager';
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$skip = false;
if (!$account->isadmin()) {
    $skip = true;
}


#breakpoint($_REQUEST);
if ($app->formposted()) {

    if (empty($_REQUEST['uid'])) {
        $skip = true;
    }
}


if (!$skip) {

    $p_uid = $_REQUEST['uid'] ?? null; // Default to null if 'uid' is not set

    # breakpoint($_REQUEST);
    // Debug: Log what was received
    if (isset($_REQUEST['product_id'])) {
        error_log("set_accounttypeplan.php - Received product_id: " . $_REQUEST['product_id']);
    } else {
        error_log("set_accounttypeplan.php - No product_id received in request");
    }
    
    if ($p_uid !== null) {
        $workinguserdata = $account->getuserdata($p_uid, 'user_id');
        
        $p_status = $_REQUEST['accountstatus'] ?? $workinguserdata['status'];
        
        // Use the centralized CreateAccount function to resolve product details
        $product_details = $createaccount->resolveProductDetails($_REQUEST, $workinguserdata);
        
        // Extract the resolved values
        $p_product_id = $product_details['product_id'];
        $p_type = $product_details['account_type'];
        $p_plan = $product_details['account_plan'];
        $p_product_name = $product_details['product_name'];
        
        // Log the resolution for debugging
        error_log("set_accounttypeplan.php - Resolved product details: " . json_encode($product_details));

        $send_mail = $_REQUEST['send_mail'] ?? '';
        $previoustype = $workinguserdata['account_type'];
        $previousplan = $workinguserdata['account_plan'];
        $previousproductid = $workinguserdata['account_product_id'] ?? null;

        // Use a prepared statement with bound parameters to avoid SQL injection
        $params = [
            ':status' => $p_status,
            ':account_type' => $p_type,
            ':account_plan' => $p_plan,
            ':account_product_id' => $p_product_id,
            ':user_id' => $p_uid
        ];
        
        $sql = "UPDATE bg_users SET 
                `status` = :status, 
                account_type = :account_type, 
                account_plan = :account_plan, 
                account_product_id = :account_product_id,
                modify_dt = NOW() 
                WHERE user_id = :user_id LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute($params);
        
        // Additional processing when product changes (matching signup/checkout flow)
        if ($p_product_id && $p_product_id != $previousproductid) {
            
            // 1. Get the new product details
            $product_sql = "SELECT * FROM bg_products WHERE id = :product_id AND status = 'active'";
            $new_product = $database->getrow($product_sql, ['product_id' => $p_product_id]);
            
            if ($new_product) {
                // 2. Update user's plan metadata
                // Get plan features from bg_product_features
                $features_sql = "SELECT name, value FROM bg_product_features 
                                WHERE product_id = :product_id AND status = 'active'";
                $features = $database->getrows($features_sql, ['product_id' => $p_product_id]);
                
                // 3. Handle allocation changes
                $current_year = date('Y');
                
                // Get max allocations for new plan
                $max_allocations = 10; // Default
                foreach ($features as $feature) {
                    if ($feature['name'] == 'enrollments_per_period' || 
                        $feature['name'] == 'max_business_select') {
                        $max_allocations = intval($feature['value']);
                        break;
                    }
                }
                
                // Special handling for free plans
                if (strpos($p_plan, 'free') !== false) {
                    $max_allocations = 3;
                }
                
                // Check if user already has a plan allocation for this year
                $check_allocation_sql = "SELECT allocation_id, amount FROM bg_user_allocations 
                                        WHERE user_id = :user_id 
                                        AND allocation_year = :year 
                                        AND allocation_type = 'plan'
                                        AND status = 'active'";
                $existing_allocation = $database->getrow($check_allocation_sql, [
                    'user_id' => $p_uid,
                    'year' => $current_year
                ]);
                
                if ($existing_allocation) {
                    // Update existing plan allocation
                    $update_allocation_sql = "UPDATE bg_user_allocations 
                                            SET amount = :amount,
                                                allocation_comment = :comment
                                            WHERE allocation_id = :allocation_id";
                    $database->query($update_allocation_sql, [
                        'amount' => $max_allocations,
                        'comment' => 'Plan changed to ' . ($new_product['account_name'] ?? $p_plan),
                        'allocation_id' => $existing_allocation['allocation_id']
                    ]);
                } else {
                    // Create new plan allocation
                    $allocationmanager->ensurePlanAllocation($p_uid, $current_year);
                }
                
                // 4. Note: Subscription updates are handled by Stripe webhooks
                // The account_product_id update above is sufficient for tracking the plan change
                
                // 5. Update plan-specific user attributes
                // Update plan start date if this is a new plan
                if ($previousplan != $p_plan) {
                    // Check if user has a plan_start_date attribute
                    $check_start_sql = "SELECT attribute_id FROM bg_user_attributes 
                                       WHERE user_id = :user_id 
                                       AND type = 'plan' 
                                       AND name = 'plan_start_date' 
                                       AND status = 'active'";
                    $existing_start = $database->getrow($check_start_sql, ['user_id' => $p_uid]);
                    
                    if ($existing_start) {
                        // Update existing plan start date
                        $update_start_sql = "UPDATE bg_user_attributes 
                                           SET string_value = :date,
                                               modify_dt = NOW()
                                           WHERE attribute_id = :attribute_id";
                        $database->query($update_start_sql, [
                            'date' => date('Y-m-d'),
                            'attribute_id' => $existing_start['attribute_id']
                        ]);
                    } else {
                        // Create new plan start date
                        $insert_start_sql = "INSERT INTO bg_user_attributes 
                                           (user_id, type, name, string_value, status, create_dt)
                                           VALUES (:user_id, 'plan', 'plan_start_date', :date, 'active', NOW())";
                        $database->query($insert_start_sql, [
                            'user_id' => $p_uid,
                            'date' => date('Y-m-d')
                        ]);
                    }
                    
                    // Store billing period from product features
                    $billing_period = 'monthly'; // default
                    foreach ($features as $feature) {
                        if ($feature['name'] == 'billing_period') {
                            $billing_period = $feature['value'];
                            break;
                        }
                    }
                    
                    // Update or create billing period attribute
                    $check_period_sql = "SELECT attribute_id FROM bg_user_attributes 
                                        WHERE user_id = :user_id 
                                        AND type = 'plan' 
                                        AND name = 'billing_period' 
                                        AND status = 'active'";
                    $existing_period = $database->getrow($check_period_sql, ['user_id' => $p_uid]);
                    
                    if ($existing_period) {
                        $update_period_sql = "UPDATE bg_user_attributes 
                                            SET string_value = :period,
                                                modify_dt = NOW()
                                            WHERE attribute_id = :attribute_id";
                        $database->query($update_period_sql, [
                            'period' => $billing_period,
                            'attribute_id' => $existing_period['attribute_id']
                        ]);
                    } else {
                        $insert_period_sql = "INSERT INTO bg_user_attributes 
                                            (user_id, type, name, string_value, status, create_dt)
                                            VALUES (:user_id, 'plan', 'billing_period', :period, 'active', NOW())";
                        $database->query($insert_period_sql, [
                            'user_id' => $p_uid,
                            'period' => $billing_period
                        ]);
                    }
                }
                
                // 6. Log the plan change
                $log_sql = "INSERT INTO bg_user_attributes 
                           (user_id, type, name, string_value, status, create_dt)
                           VALUES (:user_id, 'plan_change', 'admin_change', :details, 'active', NOW())";
                $log_details = json_encode([
                    'from_product_id' => $previousproductid,
                    'to_product_id' => $p_product_id,
                    'from_plan' => $previousplan,
                    'to_plan' => $p_plan,
                    'changed_by' => $current_user_data['user_id'] ?? 0,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $database->query($log_sql, [
                    'user_id' => $p_uid,
                    'details' => $log_details
                ]);
            }
        }
    

        // $bg_users_accountstatus from site-arrays.inc
        // $bg_users_accounttypes from site-arrays.inc
        // $bg_users_accountplans from site-arrays.inc


        if (!empty($send_mail)) {
            $input['templatename'] = 'admin_changetypeplan';    // view in /core/'.$website['ui_version'].'/email/email-$input['templatename'].inc
            $input['source'] = 'set_accounttypeplan.php';  // typically page that can generate the email
            $input['type'] = 'all';
            $input['name'] = $workinguserdata['first_name'];
            $input['to'] = $workinguserdata['email'];
            // message body related variables  -----------------------
            $input['previousstatustag'] = $bg_users_accounttypes[$workinguserdata['status']];
            $input['previoustypetag'] = $bg_users_accounttypes[$workinguserdata['account_type']];
            $input['previousplantag'] = $bg_users_accountplans[$workinguserdata['account_plan']];
            $input['newstatustag'] = $bg_users_accountstatus[$p_status];
            $input['newtypetag'] = $bg_users_accounttypes[$p_type];
            $input['newplantag'] = isset($p_product_name) ? $p_product_name : $bg_users_accountplans[$p_plan];
            $input['productchanged'] = ($p_product_id && $p_product_id != $previousproductid) ? true : false;
            $mail->sendtemplate($input);
        }
    }
}


$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);
