<?php

class Userdata
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
public function create_user($input = []) {        
        
        // Set default parameters from input
        $params = array(
            ':first_name' => $input['first_name'] ?? null,
            ':last_name' => $input['last_name'] ?? null,
            ':username' => $input['username'] ?? null,
            ':email' => $input['email'] ?? null,
            ':hashed_password' => $input['hashed_password'] ?? null,
            ':birthday' => $input['birthdate'] ?? null,
            // Account location
            ':city' => $input['city'] ?? null,
            ':state' => $input['state'] ?? null,
            ':zip_code' => $input['zip_code'] ?? null,
            // Profile location (same as account location)
            ':city2' => $input['city'] ?? null,
            ':state2' => $input['state'] ?? null,
            ':zip_code2' => $input['zip_code'] ?? null,
            // Other account details
            ':type' => $input['type'] ?? 'real',
            ':product_id' => $input['account_product_id'] ?? null,
            ':account_plan' => $input['account_plan'] ?? null,
            ':account_type' => $input['account_type'] ?? 'user',
            ':account_cost' => $input['account_cost'] ?? 0,
            ':account_validation' => $input['account_verification'] ?? 'required',
            ':avatar' => $input['avatar'] ?? null,
        );
    
        $additional_params = $input['additional_params'] ?? [];
        $params = array_merge($params, $additional_params);
        session_tracking('create user params', $params);
    
        // Add the user to bg_users table
        $sql = "INSERT INTO bg_users (first_name, last_name, username, email, password, birthdate, `status`, city, state, zip_code, 
                account_product_id, account_plan, account_type, account_cost, account_verification, `type`,
                profile_first_name, profile_last_name, profile_username, profile_email,
                profile_city, profile_state, profile_zip_code, avatar, create_dt, modify_dt)
                VALUES (:first_name, :last_name, :username, :email, :hashed_password, :birthday, 'pending', :city, :state, :zip_code, 
                :product_id, :account_plan, :account_type, :account_cost, :account_validation, :type,
                :first_name, :last_name, :username, :email,
                :city2, :state2, :zip_code2, :avatar, now(), now())";
        $stmt = $this->db->query($sql, $params);
        $lastId = $this->db->lastInsertId();
    
     
        return $lastId;
    }
    


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function attributes_accountProfile($input, $options) {

    $userid=$input['user_id']??false;
    if (empty($userid)) {return;}

    
    $attributes = [
        ':first_name' => $input['first_name'] ?? null,
        ':last_name' => $input['last_name'] ?? null,
        ':username' => $input['username'] ?? null,
        ':email' => $input['email'] ?? null,
        ':hashed_password' => $input['hashed_password'] ?? null,
        ':birthday' => $input['birthdate'] ?? null,
        ':city' => $input['city'] ?? null,
        ':state' => $input['state'] ?? null,
        ':zip_code' => $input['zip_code'] ?? null,
        ':city2' => $input['city'] ?? null,
        ':state2' => $input['state'] ?? null,
        ':zip_code2' => $input['zip_code'] ?? null,
        ':type' => $input['type'] ?? 'real',
        ':product_id' => $input['account_product_id'] ?? null,
        ':account_plan' => $input['account_plan'] ?? null,
        ':account_type' => $input['account_type'] ?? 'user',
        ':account_cost' => $input['account_cost'] ?? 0,
        ':account_validation' => $input['account_verification'] ?? 'required',
        ':avatar' => $input['avatar'] ?? null,
    ];

  foreach ($attributes as $name => $value) {
    $attributeParams = [
        ':user_id' => $userid,
        ':type' => 'profile',
        ':name' => $name,
        ':string_value' => $value,
        ':status' => 'active',
        ':create_dt' => 'now()',
        ':modify_dt' => 'now()'
    ];

    $sql = "INSERT INTO bg_user_attributes (user_id, type, name, string_value, status, create_dt, modify_dt)
            VALUES (:user_id, :type, :name, :string_value, :status, :create_dt, :modify_dt)";
    $stmt = $this->db->query($sql, $attributeParams);
}
}



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------

public function attributes_enrollmentProfile($input, $options) {

    $userid=$input['user_id']??false;
    if (empty($userid)) {return;}

    
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

  foreach ($attributes as $name => $value) {
    $attributeParams = [
        ':user_id' => $userid,
        ':type' => 'profile',
        ':name' => $name,
        ':string_value' => $value,
        ':status' => 'active',
        ':create_dt' => 'now()',
        ':modify_dt' => 'now()'
    ];

    $sql = "INSERT INTO bg_user_attributes (user_id, type, name, string_value, status, create_dt, modify_dt)
            VALUES (:user_id, :type, :name, :string_value, :status, :create_dt, :modify_dt)";
    $stmt = $this->db->query($sql, $attributeParams);
}


}

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------




}