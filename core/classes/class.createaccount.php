<?php

class CreateAccount
{

  private $db; // Database connection 
  private $session; // Session handler



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function __construct($database, $session)
  {
    $this->db = $database;
    $this->session = $session;
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  /**
   * Determine the correct product ID from various inputs
   * This is a CRITICAL function that ensures users are assigned to the correct product
   * Uses the Product class for lookups to avoid duplication
   * 
   * @param array $input Input data that may contain product_id, account_type, account_plan
   * @param array $existing_user_data Optional existing user data for fallback
   * @return array Returns array with product_id, account_type, account_plan, and product_name
   */
  public function resolveProductDetails($input, $existing_user_data = [])
  {
    global $website, $product;
    
    $result = [
      'product_id' => null,
      'account_type' => null,
      'account_plan' => null,
      'product_name' => null
    ];
    
    // Priority 1: Direct product_id provided
    if (!empty($input['product_id'])) {
      $productData = $product->getProduct($input['product_id'], 'id');
      if ($productData) {
        $result['product_id'] = $productData['id'];
        $result['account_type'] = $productData['account_type'];
        $result['account_plan'] = $productData['account_plan'];
        $result['product_name'] = $productData['account_name'];
        return $result;
      }
    }
    
    // Priority 2: Account type and plan provided - need to find matching product
    if (!empty($input['account_type']) && !empty($input['account_plan'])) {
      $version = $input['version'] ?? $website['plan_version'] ?? 'v7';
      
      // Look up the product ID from bg_products
      $sql = "SELECT id, account_name, account_type, account_plan 
              FROM bg_products 
              WHERE account_type = :account_type 
              AND account_plan = :account_plan 
              AND status = 'active' 
              AND version = :version
              ORDER BY id DESC 
              LIMIT 1";
      
      try {
        $productData = $this->db->getrow($sql, [
          'account_type' => $input['account_type'],
          'account_plan' => $input['account_plan'],
          'version' => $version
        ]);
        
        if ($productData) {
          $result['product_id'] = $productData['id'];
          $result['account_type'] = $productData['account_type'];
          $result['account_plan'] = $productData['account_plan'];
          $result['product_name'] = $productData['account_name'];
          return $result;
        }
      } catch (Exception $e) {
        error_log("resolveProductDetails: Database error - " . $e->getMessage());
      }
    }
    
    // Priority 3: Use existing user data
    if (!empty($existing_user_data)) {
      $result['product_id'] = $existing_user_data['account_product_id'] ?? null;
      $result['account_type'] = $existing_user_data['account_type'] ?? 'user';
      $result['account_plan'] = $existing_user_data['account_plan'] ?? 'free';
      
      // Try to get product name if we have a product_id
      if ($result['product_id']) {
        $productData = $product->getProduct($result['product_id'], 'id');
        if ($productData) {
          $result['product_name'] = $productData['account_name'];
        }
      }
    }
    
    // Final fallback: Default to user/free if nothing else works
    if (!$result['account_type']) {
      $result['account_type'] = 'user';
      $result['account_plan'] = 'free';
      
      // Try to find the free plan product ID
      $version = $website['plan_version'] ?? 'v7';
      $sql = "SELECT id FROM bg_products 
              WHERE account_type = 'user' 
              AND account_plan = 'free' 
              AND status = 'active' 
              AND version = :version
              LIMIT 1";
      
      try {
        $freeProduct = $this->db->getrow($sql, ['version' => $version]);
        if ($freeProduct) {
          $result['product_id'] = $freeProduct['id'];
        }
      } catch (Exception $e) {
        error_log("resolveProductDetails: Could not find free plan - " . $e->getMessage());
      }
    }
    
    return $result;
  }



  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function generate_username($first_name, $last_name, $birthday, $type='real')
  {
    switch( $type) {
      case 'test':
           $month_abbr = substr(date('F', strtotime($birthday)), 0, 3);
           $day = date('j', strtotime($birthday));
           // Concatenate 'test', the abbreviated month, day, and first name
           $username = 'test' . $month_abbr . $day . preg_replace('/[^a-zA-Z0-9]/', '', $first_name);
        break;
        
        default:
    $username_base = preg_replace('/[^a-zA-Z0-9]/', '', trim($first_name) . trim($last_name));
    $username = $username_base;

    // Create an array of 2-3 digit numbers from birthday
    preg_match_all('/\d{2,3}/', $birthday, $numbers_from_birthday);

    // Flat the array
    $numbers_from_birthday = array_reduce($numbers_from_birthday, 'array_merge', array());

    $i = 0;
    while (!$this->isavailable($username)) {
      // Append a random number from birthday if it exists, otherwise append a random number
      if (isset($numbers_from_birthday[$i])) {
        $random_number = $numbers_from_birthday[$i];
      } else {
        $random_number = rand(10, 999);
      }

      // Append the random number to the username base
      $username = $username_base . $random_number;
      $i++;
    }
break;
    }
    return $username;
  }


  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function isavailable($input, $type = 'username')
  {
    // Prepare and execute the SQL statement
    global $session;
    $username = trim(strtolower($input));
    $userid = $session->get('current_user_id', '');

    switch ($type) {
      case 'feature_email':
        $stmt = $this->db->prepare("SELECT user_id FROM bg_users WHERE lower(username) = ? or lower(email) = ? or lower(feature_email) = ?");
        $stmt->execute([$username, $username, $username]);
        break;

      case 'referral_code':
        $stmt = $this->db->prepare("SELECT user_id FROM bg_user_attributes WHERE lower(`name`) = ? and category='referral_code'");
        $stmt->execute([$username]);
        break;

      default:
        $stmt = $this->db->prepare("SELECT user_id FROM bg_users WHERE lower(username) = ? or lower(email) = ?");
        $stmt->execute([$username, $username]);
        break;
    }

    if ($stmt->rowCount() === 0) {
      return true;  // true if username is available
    } else {
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($result && $result['user_id'] == $userid) {
        return 2;  // return 2 if the result value equals $userid
      }
    }

    return false;  // false if username is not available and doesn't belong to the current user
  }


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
function isemailaccountavailable($email) 
{
  $email = trim(strtolower($email));
  
  // Check for existing email account, get full record if found
  $stmt = $this->db->prepare("
    SELECT * 
    FROM bg_users 
    WHERE lower(email) = ? 
    ORDER BY user_id ASC
    LIMIT 1
  ");
  $stmt->execute([$email]);
  
  if ($stmt->rowCount() === 0) {
    return true; // No record found - email is available
  }

  // Return the record details when email exists
  return $stmt->fetch(PDO::FETCH_ASSOC);
}


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  function isavailable_old($input, $type = 'username')
  {
    // Prepare and execute the SQL statement
    global $session;
    $username = trim(strtolower($input));
    $userid = $session->get('current_user_id', '');

    switch ($type) {
      case 'feature_email':
        if ($userid == '') {
          $stmt = $this->db->prepare("SELECT user_id FROM bg_users WHERE lower(username) = ? or lower(email) = ?  or lower(feature_email) = ?");
          $stmt->execute([$username, $username, $username]);
        } else {
          $stmt = $this->db->prepare("SELECT user_id FROM bg_users WHERE (lower(username) = ? or lower(email) = ? or lower(feature_email) = ?) and user_id != ?");
          $stmt->execute([$username, $username, $username, $userid]);
        }
        break;
      case 'referral_code':
        $stmt = $this->db->prepare("SELECT user_id FROM bg_user_attributes WHERE lower(`name`) = ? and category='referral_code'");
        $stmt->execute([$username]);
        break;

      default:
        if ($userid == '') {
          $stmt = $this->db->prepare("SELECT user_id FROM bg_users WHERE lower(username) = ? or lower(email) = ?");
          $stmt->execute([$username, $username]);
        } else {
          $stmt = $this->db->prepare("SELECT user_id FROM bg_users WHERE (lower(username) = ? or lower(email) = ?) and user_id != ?");
          $stmt->execute([$username, $username, $userid]);
        }

        break;
    }
    $output = false;
    if ($stmt->rowCount() === 0) {
      $output = true;  // true if username is available
    } else {
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($result && $result['user_id'] == $userid) {
        return 2;  // return 2 if the result value equals $userid
      }
    }

    return $output;
  }


  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function create_user($input = []) {        
        
    // Set default parameters from input
    $params = array(
        ':first_name' => $input['first_name'] ?? null,
        ':last_name' => $input['last_name'] ?? null,
        ':username' => $input['username'] ?? null,
        ':email' => $input['email'] ?? null,
        ':hashed_password' => $input['hashed_password'] ?? null,
        ':birthday' => $input['birthday'] ?? null,
        // Account location
        ':city' => $input['city'] ?? null,
        ':state' => $input['state'] ?? null,
        ':zip_code' => $input['zip_code'] ?? null,
        // Other account details
        ':type' => $input['type'] ?? 'real',
        ':product_id' => $input['product_id'] ?? null,
        ':account_plan' => $input['account_plan'] ?? null,
        ':account_type' => $input['account_type'] ?? 'user',
        ':account_cost' => $input['account_cost'] ?? 0,
        ':account_validation' => $input['account_verification'] ?? 'required',
       // ':avatar' => $input['avatar'] ?? null,
    );


   // Build SQL based on user type
   if ($input['type'] === 'test') {
    // Add test-specific fields to both SQL and params
    $sql = "INSERT INTO bg_users (
        first_name, last_name, username, email, `password`, birthdate, `status`, 
        city, `state`, zip_code,
        account_product_id, account_plan, account_type, account_cost, account_verification, `type`,
        phone_number, profile_phone_type, 
        create_dt, modify_dt
    ) VALUES (
        :first_name, :last_name, :username, :email, :hashed_password, :birthday, 'pending',
        :city, :state, :zip_code,
        :product_id, :account_plan, :account_type, :account_cost, :account_validation, :type,
        :phone_number, :profile_phone_type,  
        now(), now()
    )";

    // Add test-specific params
    $params[':phone_number'] = $input['phone_number'] ?? null;
    $params[':profile_phone_type'] = $input['profile_phone_type'] ?? null;
    session_tracking('create user SQLtest-function', $sql);

} else {
    // Standard user SQL - now includes phone_number
    $sql = "INSERT INTO bg_users (
        first_name, last_name, username, email, `password`, birthdate, `status`,
        city, `state`, zip_code,
        account_product_id, account_plan, account_type, account_cost, account_verification, `type`,
        phone_number, profile_phone_type,
        create_dt, modify_dt
    ) VALUES (
        :first_name, :last_name, :username, :email, :hashed_password, :birthday, 'pending',
        :city, :state, :zip_code,
        :product_id, :account_plan, :account_type, :account_cost, :account_validation, :type,
        :phone_number, :profile_phone_type,
        now(), now()
    )";

    // Add phone params for standard users too
    $params[':phone_number'] = $input['phone_number'] ?? null;
    $params[':profile_phone_type'] = $input['profile_phone_type'] ?? 'unknown';

    session_tracking('create user SQLreal-function', $sql);

}


    session_tracking('create user params-function', $params);

    $stmt = $this->db->query($sql, $params);
    $lastId = $this->db->lastInsertId();

    session_tracking('create user USERID', $lastId);

    // Create initial allocations for the user based on their plan
    $this->createInitialAllocations($lastId, $input);

    // Create user attributes for all profile columns
    $attributes = [
        'profile_first_name' => $input['first_name'] ?? '',
        'profile_last_name' => $input['last_name'] ?? '',
        'profile_username' => $input['username'] ?? '',
        'profile_email' => $input['email'] ?? '',
        'profile_mailing_address' => $input['mailing_address'] ?? '',
        'profile_city' => $input['city'] ?? '',
        'profile_state' => $input['state'] ?? '',
        'profile_zip_code' => $input['zip_code'] ?? '',
        'profile_country' => $input['country'] ?? 'United States',
        'profile_phone_number' => $input['phone_number'] ?? '',
        'profile_gender' => $input['gender'] ?? '',
        'profile_agree_terms' => $input['agree_terms'] ?? 'true',
        'profile_agree_email' => $input['agree_email'] ?? 'true',
        'profile_agree_text' => $input['agree_text'] ?? 'true',
        'profile_allergy_gluten' => $input['allergy_gluten'] ?? '',
        'profile_allergy_sugar' => $input['allergy_sugar'] ?? '',
        'profile_allergy_nuts' => $input['allergy_nuts'] ?? '',
        'profile_allergy_dairy' => $input['allergy_dairy'] ?? '',
        'profile_diet_vegan' => $input['diet_vegan'] ?? '',
        'profile_diet_kosher' => $input['diet_kosher'] ?? '',
        'profile_diet_pescatarian' => $input['diet_pescatarian'] ?? '',
        'profile_diet_keto' => $input['diet_keto'] ?? '',
        'profile_diet_paleo' => $input['diet_paleo'] ?? '',
        'profile_diet_vegetarian' => $input['diet_vegetarian'] ?? '',
        'profile_military' => $input['military'] ?? '',
        'profile_educator' => $input['educator'] ?? '',
        'profile_firstresponder' => $input['firstresponder'] ?? '',
    ];
$i = 0;
    foreach ($attributes as $name => $value) {
        $attributeParams = [
            ':user_id' => $lastId,
            ':type' => 'profile',
            ':name' => $name,
            ':string_value' => $value,
            ':status' => 'active',
           # ':create_dt' => 'now()',
           # ':modify_dt' => 'now()'
        ];

        $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, string_value, `status`, create_dt, modify_dt)
                VALUES (:user_id, :type, :name, :string_value, :status, now(), now())";
        $stmt = $this->db->query($sql, $attributeParams);
        $i++;
    }

    session_tracking('create user bg_user_attributes', count($attributes).' - '.$i);

    // ADD create time, cover banner, avatar to bg_user_attributes
$coverbanner = '/public/images/site_covers/cbanner_' . $input['birthday_month'] . '.jpg';
$sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, category, create_dt, modify_dt)
VALUES 
(:user_id, 'created', 'timeline', '', 'active', 100, null, NOW(), NOW()),
(:user_id2, 'profile_image', 'avatar', :avatar, 'active', 100, 'primary', NOW(), NOW()),
(:user_id3, 'profile_image', 'account_cover', :banner, 'active', 100, 'primary', NOW(), NOW())
";
$stmt = $this->db->query($sql, [
  ':user_id' => $lastId, 
  ':user_id2' => $lastId, 
  ':user_id3' => $lastId, 
  ':avatar' => $input['avatar_file'], 
  ':banner' => $coverbanner]);

  session_tracking('create user bg_user_attributes-images', $sql);

  // Handle referral code if provided
  if (!empty($input['referral_code'])) {
      // Look up the referrer's user_id
      $referral_sql = "SELECT user_id FROM bg_user_attributes 
                       WHERE type = 'referralcode' 
                       AND name = 'generated_code' 
                       AND description = :referral_code 
                       AND status = 'active'";
      $referrer_result = $this->db->query($referral_sql, ['referral_code' => $input['referral_code']]);
      $referrer_data = $referrer_result->fetch(PDO::FETCH_ASSOC);
      
      if ($referrer_data) {
          // Create referral relationship
          $referral_relationship_sql = "INSERT INTO bg_user_attributes (user_id, type, name, value, status, create_dt, modify_dt)
                                        VALUES (:user_id, 'referralcode', 'referred_by_user_id', :referrer_id, 'active', NOW(), NOW())";
          $this->db->query($referral_relationship_sql, [
              'user_id' => $lastId,
              'referrer_id' => $referrer_data['user_id']
          ]);
          
          session_tracking('create referral relationship', 'User ' . $lastId . ' referred by ' . $referrer_data['user_id'] . ' via code ' . $input['referral_code']);
      }
  }

return $lastId;

}

/**
 * Create initial allocations for a new user based on their plan
 */
private function createInitialAllocations($user_id, $input) {
    global $app;

    // Get plan details
    $account_plan = $input['account_plan'] ?? 'free';
    $product_id = $input['product_id'] ?? null;

    // Get allocation amount from product features - ALL plans, including free
    $allocation_amount = 0;

    if ($product_id) {
        // Get from product features - this is the only source of truth
        $sql = "SELECT value FROM bg_product_features
                WHERE product_id = :product_id
                AND name = 'max_business_select'
                AND status = 'active'";
        $result = $this->db->getrow($sql, ['product_id' => $product_id]);
        if ($result && isset($result['value'])) {
            $allocation_amount = intval($result['value']);
            error_log('Product ' . $product_id . ' has max_business_select = ' . $allocation_amount);
        } else {
            // Log if product doesn't have max_business_select defined
            error_log('Product ' . $product_id . ' does not have max_business_select defined in bg_product_features');
        }
    } else {
        error_log('No product_id provided for user ' . $user_id . ' - no allocations created');
    }

    // Only create allocation record if amount > 0
    if ($allocation_amount > 0) {
        $current_year = date('Y');

        $sql = "INSERT INTO bg_user_allocations (
                    user_id,
                    allocation_type,
                    allocation_year,
                    amount,
                    amount_used,
                    allocation_comment,
                    reference_type,
                    status,
                    is_recurring,
                    created_at,
                    created_by
                ) VALUES (
                    :user_id,
                    'plan',
                    :year,
                    :amount,
                    0,
                    :comment,
                    'account_creation',
                    'active',
                    1,
                    NOW(),
                    :user_id
                )";

        $params = [
            'user_id' => $user_id,
            'year' => $current_year,
            'amount' => $allocation_amount,
            'comment' => ucfirst($account_plan) . ' plan initial allocation'
        ];

        try {
            $this->db->query($sql, $params);
            session_tracking('create_initial_allocations', 'Created ' . $allocation_amount . ' allocations for user ' . $user_id);
        } catch (Exception $e) {
            error_log('Failed to create initial allocations for user ' . $user_id . ': ' . $e->getMessage());
        }
    } else {
        session_tracking('create_initial_allocations', 'No allocations created for free plan user ' . $user_id);
    }
}


}
