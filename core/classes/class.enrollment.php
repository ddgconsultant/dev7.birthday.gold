<?PHP

class enrollment
{





    # ##--------------------------------------------------------------------------------------------------------------------------------------------------

    function formatDate($date, $format)
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return ''; // Invalid date
        }

        return date($format, $timestamp);
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
function grabdetails($database, $adminDetails, $userId, $companyId, $return, $suffix = null)  {
        // Check for test mode
        $test_mode = ($userId == -1);
        
        // Get user details
        global $account;
        if ($test_mode) {
            // In test mode, use user 20's data
            $real_userId = 20;
            $userDetails = $account->getuserdata($real_userId, 'user_id');
            session_tracking('ENROLLMENT TEST MODE', 'Using user 20 data for test mode');
        } else {
            $userDetails = $account->getuserdata($userId, 'user_id');
        }

        // Build SQL query based on mode
        if ($test_mode) {
            // Test mode query - no user join required, no finalized status check
            $sql = "SELECT 
                    999999 as user_company_id,  -- Fake ID for test mode
                    c.company_name, 
                    20 as user_id,  -- Always user 20 for test
                    c.company_id, 
                    'selected' as status,  -- Default status for test
                    c.status as company_status, 
                    SUBSTRING_INDEX(c.signup_url, '/', 3) AS signup_domain, 
                    c.signup_url,  
                    c.bgrab_domain
                    FROM bg_companies c
                    WHERE c.signup_url IS NOT NULL 
                    AND c.signup_url != '' 
                    AND c.signup_url != 'APP ONLY' 
                    {{find_company}}
                    ORDER BY c.company_name ASC";
        } else {
            // Production query - original logic
            $sql = "SELECT uc.user_company_id, c.company_name, uc.user_id, uc.company_id, uc.status,  c.status as company_status, 
SUBSTRING_INDEX(c.signup_url, '/', 3) AS signup_domain, c.signup_url,  c.bgrab_domain
FROM bg_user_companies uc
LEFT JOIN bg_companies c ON uc.company_id = c.company_id
left join bg_users u on uc.user_id=u.user_id
WHERE ((uc.status not in ('success', 'success-btn', 'success-sub', 'failed', 'removed')) or (uc.status ='failed' and u.modify_dt>uc.modify_dt)) and c.status='finalized' and c.signup_url != 'APP ONLY' 
and uc.user_id = :userId {{find_company}}  
AND NOT (uc.`status` LIKE '%failed%' AND uc.`reason` = 'account_exists')
order by uc.create_dt desc ";
        }


if ($companyId == 0) {
            $findcompanytag = 'and c.company_id>:companyId ';
        } else {
        $findcompanytag = 'and c.company_id=:companyId ';
        session_tracking('grabdetails - companyId provided', $companyId);
        }
       /* if ($return == 'js') {
            $findcompanytag = '';
        }
*/
        // Get companies
        $sql = str_replace('{{find_company}}', $findcompanytag, $sql);
        
        // Execute query based on mode
        if ($test_mode) {
            // Test mode doesn't need userId parameter
            if ($companyId == 0) {
                $stmt = $database->query($sql, [':companyId' => $companyId]);
            } else {
                $stmt = $database->query($sql, [':companyId' => $companyId]);
            }
        } else {
            // Production mode with userId
            $stmt = $database->query($sql, [':userId' => $userId, ':companyId' => $companyId]);
        }
        
        $registrationList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        session_tracking('number of records found', count($registrationList));


//       // Get field mappings for each company
        foreach ($registrationList as $key => $company) {
            # $key=$company['company_id'];
            $sql = "SELECT max(version) as version FROM bg_form_field_mappings WHERE company_id = :company_id and version_status='active' group by company_id limit 1";
            $version = $database->query($sql, ['company_id' => $company['company_id']])->fetchAll();
            $versionnumber = $version[0]['version'];

            $stmt = $database->query("SELECT website_field_name, user_field_name, fieldformattype, fieldformat 
FROM bg_form_field_mappings WHERE `status`='active' and company_id = :companyId and version=$versionnumber order by `rank`", ['companyId' => $company['company_id']]);
            $fieldMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $updatedFieldMappings = [];
            $keyorder = 1;
            foreach ($fieldMappings as $field) {
                $userFieldName = $field['user_field_name'];
                $fieldFormatType = $field['fieldformattype'];
                $fieldFormat = $field['fieldformat'];
                if (strpos($userFieldName, 'FIXEDVALUE:') !== false) {

                    $formattedValue = str_replace('FIXEDVALUE:', '', $userFieldName);
                } else {
                    $userDetails[$userFieldName] = $userDetails[$userFieldName] ?? '';
                    if (empty($userDetails[$userFieldName])) {
                        $finalvalue = 'value-not-provided';
                        if (strpos($userFieldName, 'agree') !== false) $finalvalue = 'true';
                    } else
                        $finalvalue = $userDetails[$userFieldName];

                    $formattedValue =  $finalvalue;
                }


                // Apply formatting logic if fieldformattype is set
                switch ($fieldFormatType) {
                    case 'date-calculate':
                        # $mydate = '2023-02-05'; $formattedValue
                        #  $calculationstr = "{m}+81098";   $fieldFormat             
                        // Extract format within {}  
                        preg_match('/\{(\w+)\}/', $fieldFormat, $matches);
                        $format = $matches[1];

                        // Get date portion based on format 
                        $dateVal = date($format, strtotime($formattedValue));

                        // Extract the calculation portion
                        preg_match('/[+-]\d+/', $fieldFormat, $matches);
                        $calc = $matches[0];

                        // Evaluate calculation 
                        eval("\$formattedValue = $dateVal $calc;");
                        #  $formattedValue = $result;
                        #echo "$dateVal $calc = $formattedValue";
                        break;
                    case 'date-numberformat':
                        $formattedValue = $this->formatDate($formattedValue, $fieldFormat);
                        $formattedValue = number_format($formattedValue, 0, ',', ',');

                        break;

                    case 'date':
                        // Apply date formatting logic here
                        $formattedValue = $this->formatDate($formattedValue, $fieldFormat);
                        break;
                    case 'lowerdate':
                        // Apply date formatting logic here
                        $formattedValue = strtolower($this->formatDate($formattedValue, $fieldFormat));
                        break;
                    case 'state':
                        // Apply date formatting logic here
                        if ($fieldFormat == 'code') {

                            $states = [
                                'Alabama' => 'AL',
                                'Alaska' => 'AK',
                                'Arizona' => 'AZ',
                                'Arkansas' => 'AR',
                                'California' => 'CA',
                                'Colorado' => 'CO',
                                'Connecticut' => 'CT',
                                'Delaware' => 'DE',
                                'Florida' => 'FL',
                                'Georgia' => 'GA',
                                'Hawaii' => 'HI',
                                'Idaho' => 'ID',
                                'Illinois' => 'IL',
                                'Indiana' => 'IN',
                                'Iowa' => 'IA',
                                'Kansas' => 'KS',
                                'Kentucky' => 'KY',
                                'Louisiana' => 'LA',
                                'Maine' => 'ME',
                                'Maryland' => 'MD',
                                'Massachusetts' => 'MA',
                                'Michigan' => 'MI',
                                'Minnesota' => 'MN',
                                'Mississippi' => 'MS',
                                'Missouri' => 'MO',
                                'Montana' => 'MT',
                                'Nebraska' => 'NE',
                                'Nevada' => 'NV',
                                'New Hampshire' => 'NH',
                                'New Jersey' => 'NJ',
                                'New Mexico' => 'NM',
                                'New York' => 'NY',
                                'North Carolina' => 'NC',
                                'North Dakota' => 'ND',
                                'Ohio' => 'OH',
                                'Oklahoma' => 'OK',
                                'Oregon' => 'OR',
                                'Pennsylvania' => 'PA',
                                'Rhode Island' => 'RI',
                                'South Carolina' => 'SC',
                                'South Dakota' => 'SD',
                                'Tennessee' => 'TN',
                                'Texas' => 'TX',
                                'Utah' => 'UT',
                                'Vermont' => 'VT',
                                'Virginia' => 'VA',
                                'Washington' => 'WA',
                                'West Virginia' => 'WV',
                                'Wisconsin' => 'WI',
                                'Wyoming' => 'WY',
                                // The following are territories, not states, but they have postal abbreviations:
                                'District of Columbia' => 'DC',
                                'American Samoa' => 'AS',
                                'Guam' => 'GU',
                                'Northern Mariana Islands' => 'MP',
                                'Puerto Rico' => 'PR',
                                'United States Minor Outlying Islands' => 'UM',
                                'U.S. Virgin Islands' => 'VI'
                            ];
                            $formattedValue = isset($states[$formattedValue]) ? $states[$formattedValue] : $formattedValue;
                            # $formattedValue = $states[$formattedValue];
                        }


                        break;


                    case 'title':
                        if ($fieldFormat == 'noperiod') $formattedValue = str_replace('.', '', $formattedValue);
                        #  if ($fieldFormat=='codelong') $formattedValue ='USA';

                        break;


                    case 'name':
                        $search = array('{first_name}', '{middle_name}', '{last_name}', '{middle_initial}');
                        $replace = array($userDetails['profile_first_name'], $userDetails['profile_middle_name'], $userDetails['profile_last_name'], substr($userDetails['profile_middle_name'], 0, 1) . '.');
                        $formattedValue = str_replace($search, $replace,  $formattedValue);
                        break;
                    case 'gender':
                        if (!empty($formattedValue)) {
                            switch ($fieldFormat) {
                                case 'uppercode':
                                    $formattedValue = ($formattedValue == "male") ? "M" : "F";
                                    break;
                                case 'lowercode':
                                    $formattedValue = ($formattedValue == "male") ? "m" : "f";
                                    break;
                                case 'upper':
                                    $formattedValue = ($formattedValue == "male") ? "MALE" : "FEMALE";
                                    break;
                                case 'ucwords':
                                    $formattedValue = ($formattedValue == "male") ? "Male" : "Female";
                                    break;
                                case 'MF->12':
                                case  'mf->12':
                                    $formattedValue = ($formattedValue == "male") ? "1" : "2";
                                    break;
                            }
                        }
                        break;

                    case 'tf->yn':
                        switch ($fieldFormat) {
                            case 'NNo':
                                $formattedValue = ($formattedValue == "true") ? "N" : "No";
                                break;
                            case 'uinitial':
                                $formattedValue = ($formattedValue == "true") ? "Y" : "N";
                                break;
                            case 'ucwords':
                                $formattedValue = ($formattedValue == "true") ? "Yes" : "No";
                                break;
                            case 'upper':
                                $formattedValue = ($formattedValue == "true") ? "YES" : "NO";
                                break;
                            case 'lower':
                                $formattedValue = ($formattedValue == "true") ? "yes" : "no";
                                break;
                        }
                        break;
                    case 'tf->10':
                        $formattedValue = ($formattedValue == "true") ? "1" : "0";
                        break;
                    case 'tf->fixed':
                        list($truevalue, $falsevalue) = explode('/', $fieldFormat, 2);
                        $formattedValue = ($formattedValue == "true") ? $truevalue : $falsevalue;
                        break;
                    case 'tf->fixedpipe':
                        list($truevalue, $falsevalue) = explode('|', $fieldFormat, 2);
                        $formattedValue = ($formattedValue == "true") ? $truevalue : $falsevalue;
                        break;
                    case 'country':
                        if ($fieldFormat == 'code') $formattedValue = 'US';
                        if ($fieldFormat == 'codelong') $formattedValue = 'USA';
                        if ($fieldFormat == 'fullname_lower') $formattedValue = 'united states';
                        break;

                    case 'phone_OLD':
                        $pattern = $fieldFormat;
                        $phoneNumber = $userDetails['profile_phone_number'] ?? '';

                        $pattern = preg_replace("/[^0-9]/", "", $pattern); // Remove non-numeric characters from pattern
                        $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber); // Remove non-numeric characters from phone number

                        if (strlen($pattern) != strlen($phoneNumber)) {
                            #throw new Exception('Pattern and phone number do not have the same number of digits');
                            echo "throw new Exception('Pattern and phone number do not have the same number of digits');";
                        }

                        $formattedNumber = "";
                        for ($i = 0; $i < strlen($pattern); $i++) {
                            $formattedNumber .= str_replace($pattern[$i], $phoneNumber[$i], $pattern[$i]);
                        }

                        $formattedValue = $formattedNumber;
                        break;



                    case 'phone':
                        $pattern = $fieldFormat;
                        $phoneNumber = $userDetails['profile_phone_number'] ?? '';

                        if ($pattern !== null) {
                            $pattern = preg_replace("/\D/", "", $pattern);
                        }

                        if ($phoneNumber !== null) {
                            $phoneNumber = preg_replace("/\D/", "", $phoneNumber);
                        }

                        // Extract segments from phone number
                        $firstThreeDigits = substr($phoneNumber, 0, 3);
                        $middleThreeDigits = substr($phoneNumber, 3, 3);
                        $lastFourDigits = substr($phoneNumber, 6, 4);

                        switch ($pattern) {
                            case "012":
                                $formattedValue = $firstThreeDigits;
                                break;
                            case "345":
                                $formattedValue = $middleThreeDigits;
                                break;
                            case "6789":
                                $formattedValue = $lastFourDigits;
                                break;
                            default:
                                if (strlen($pattern) == strlen($phoneNumber)) {
                                    $formattedNumber = "";
                                    for ($i = 0; $i < strlen($pattern); $i++) {
                                        $formattedNumber .= str_replace($pattern[$i], $phoneNumber[$i], $pattern[$i]);
                                    }
                                    $formattedValue = $formattedNumber;
                                } else {
                                    // Handle mismatch - set to original or empty value
                                    $formattedValue = $phoneNumber;
                                    session_tracking("Pattern and phone number mismatched - using original phone number ($phoneNumber) for COMPANY_ID: " . $company['company_id']);
                                }
                                break;
                        }
                        break;


                        // Add more cases for other field format types if needed
                }

                #        $updatedFieldMappings[right("00".$keyorder,2).'||'.$field['website_field_name']] = $formattedValue;
                $updatedFieldMappings[substr(str_pad($keyorder, 2, '0', STR_PAD_LEFT), -2) . '||' . $field['website_field_name']] = $formattedValue;

                $keyorder++;
            }

            if (strpos($registrationList[$key]['signup_domain'], 'punchh') !== false) $registrationList[$key]['signup_domain'] = $registrationList[$key]['signup_url'];

            $registrationList[$key]['FIELDMAPPING'] = $updatedFieldMappings;
        }

        // Modify user details for test mode before returning
        if ($test_mode && $userDetails) {
            $timestamp = time();
            // Use provided suffix or generate a random one
            if (!empty($suffix)) {
                $random = $suffix;
            } else {
                $random = substr(md5(uniqid(rand(), true)), 0, 8);
            }
            $test_email = "test-20-{$random}@birthday-gold.xyz";
            
            // Override sensitive fields with test data
            $userDetails['profile_email'] = $test_email;
            $userDetails['email'] = $test_email;
            $userDetails['profile_username'] = "testuser_20_{$random}";
            $userDetails['username'] = "testuser_20_{$random}";
            $userDetails['profile_password'] = 'TestPass123!';
            $userDetails['test_mode'] = true;
            $userDetails['original_user_id'] = 20;
            
            session_tracking('TEST MODE - Modified user data', [
                'test_email' => $test_email,
                'test_username' => "testuser_20_{$random}"
            ]);
        }

        // Output as JSON
        return array(json_encode(['ADMINDETAILS' => $adminDetails,  'USERDETAILS' => $userDetails, 'REGISTRATIONLIST' => $registrationList]), $adminDetails, $userDetails, $registrationList);
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function findCompanyById($registrationList, $companyId)
    {
        foreach ($registrationList as $item) {
            if (isset($item['company_id']) && $item['company_id'] == $companyId) {
                return $item;  // Return the full sub-array if the company_id matches
            }
        }
        return null;  // Return null if no match is found
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function findRegistrationByCompanyId($registrationList, $companyId)
    {
        session_tracking('searching array', $registrationList, '__NOREQUESTDATA__');
        foreach ($registrationList['REGISTRATIONLIST'] as $registration) {
    
            if (isset($registration['company_id']) && $registration['company_id'] == $companyId) {
                session_tracking('found array', $registration['company_id'], '__NOREQUESTDATA__');
                return $registration;  // Return the full sub-array
            }
        }
        session_tracking('failed to find array', $registration['company_id'], '__NOREQUESTDATA__');
    
        return $registrationList['REGISTRATIONLIST'];  // Return null if no matching company_id is found
    }
    
    


    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    function postToUrl($url, $postData)
    {
       global $DEBUG;
         // Build the full URL for the referer
         $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
         $host = $_SERVER['HTTP_HOST'];
         $script = $_SERVER['PHP_SELF'];
         $referer = $scheme . $host . $script;
   
       #  $referer='';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_HEADER, $DEBUG); // Include headers in output
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output
   
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    # ELIGIBILITY SYSTEM METHODS
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    
    // Class constants for eligibility tables
    const ELIGIBILITY_TABLE = 'bg_user_eligibility';
    const REASONS_TABLE = 'bg_eligibility_reasons';
    
    // Reason ID constants for easy reference
    const REASON_INCOMPLETE_PROFILE = 1;
    const REASON_MISSING_PHONE = 2;
    const REASON_MISSING_EMAIL = 3;
    const REASON_MISSING_BIRTHDATE = 4;
    const REASON_PASSWORD_REQUIREMENTS = 40;
    const REASON_AGE_RESTRICTION = 61;
    const REASON_ACCOUNT_SUSPENDED = 81;
    
    /**
     * Check and store eligibility for a member/company combination
     * @param int $member_id
     * @param int $company_id
     * @return int|null Reason ID if ineligible, null if eligible
     */
    public function checkAndStoreEligibility($member_id, $company_id) {
        global $database;
        
        $member = $this->getMemberProfile($member_id);
        $requirements = $this->getCompanyRequirements($company_id);
        
        $reason_id = $this->evaluateEligibility($member, $requirements);
        
        if ($reason_id) {
            $this->storeEligibilityIssue($member_id, $company_id, $reason_id);
        } else {
            $this->removeEligibilityIssue($member_id, $company_id);
        }
        
        return $reason_id;
    }
    
    /**
     * Get eligibility status for multiple companies
     * @param int $member_id
     * @param array $company_ids
     * @return array Company ID => eligibility data
     */
    public function getCompanyEligibilities($member_id, $company_ids) {
        global $database;
        
        if (empty($company_ids)) {
            return array();
        }
        
        $placeholders = array_map(function($i) { return ':company_' . $i; }, array_keys($company_ids));
        $params = ['member_id' => $member_id];
        foreach ($company_ids as $i => $id) {
            $params['company_' . $i] = $id;
        }
        
        $sql = "SELECT e.company_id, e.reason_id, r.message, r.code
                FROM " . self::ELIGIBILITY_TABLE . " e
                JOIN " . self::REASONS_TABLE . " r ON e.reason_id = r.id
                WHERE e.member_id = :member_id
                AND e.company_id IN (" . implode(',', $placeholders) . ")";
        
        $issues = $database->getrows($sql, $params);
        $issues_by_company = [];
        
        foreach ($issues as $issue) {
            $issues_by_company[$issue['company_id']] = $issue;
        }
        
        $eligibilities = array();
        foreach ($company_ids as $company_id) {
            if (isset($issues_by_company[$company_id])) {
                $eligibilities[$company_id] = array(
                    'eligible' => false,
                    'reason_id' => $issues_by_company[$company_id]['reason_id'],
                    'message' => $issues_by_company[$company_id]['message'],
                    'code' => $issues_by_company[$company_id]['code'],
                    'action_url' => $this->getActionUrlForCode($issues_by_company[$company_id]['code'])
                );
            } else {
                $eligibilities[$company_id] = array(
                    'eligible' => true
                );
            }
        }
        
        return $eligibilities;
    }
    
    /**
     * Evaluate eligibility in priority order
     * @param object $member Member data object
     * @param object $requirements Company requirements
     * @return int|null Reason ID or null if eligible
     */
    private function evaluateEligibility($member, $requirements) {
        // Critical account issues first
        if ($member['status'] == 'suspended') return self::REASON_ACCOUNT_SUSPENDED;
        if (!empty($member['fraud_flag'])) return 87; // fraud_flag
        
        // Basic profile requirements
        if (empty($member['email'])) return self::REASON_MISSING_EMAIL;
        if (!filter_var($member['email'], FILTER_VALIDATE_EMAIL)) return 8; // invalid_email
        
        if ($requirements->requires_phone && empty($member['phone'])) return self::REASON_MISSING_PHONE;
        if ($requirements->requires_birthdate && empty($member['birthdate'])) return self::REASON_MISSING_BIRTHDATE;
        
        // Address requirements
        if ($requirements->requires_address) {
            if (empty($member['address']) && empty($member['address1'])) return 5; // missing_address
        }
        
        // Password requirements (if we store password status)
        if (!empty($member['password_expired'])) return 47; // password_expired
        
        // Verification requirements
        if ($requirements->requires_email_verification && empty($member['email_verified'])) return 21; // email_unverified
        if ($requirements->requires_phone_verification && empty($member['phone_verified'])) return 22; // phone_unverified
        
        // Age restrictions
        if ($requirements->minimum_age && $this->getMemberAge($member) < $requirements->minimum_age) return self::REASON_AGE_RESTRICTION;
        
        // Location restrictions
        if (!empty($requirements->restricted_states) && !empty($member['state'])) {
            $restricted_states = json_decode($requirements->restricted_states, true);
            if (is_array($restricted_states) && in_array($member['state'], $restricted_states)) {
                return 62; // location_restricted
            }
        }
        
        return null; // Eligible
    }
    
    /**
     * Store eligibility issue
     * @param int $member_id
     * @param int $company_id
     * @param int $reason_id
     */
    private function storeEligibilityIssue($member_id, $company_id, $reason_id) {
        global $database;
        
        $sql = "INSERT INTO " . self::ELIGIBILITY_TABLE . " 
                (member_id, company_id, reason_id) 
                VALUES (:member_id, :company_id, :reason_id)
                ON DUPLICATE KEY UPDATE 
                reason_id = VALUES(reason_id),
                last_checked = CURRENT_TIMESTAMP";
        
        $params = [
            'member_id' => $member_id,
            'company_id' => $company_id,
            'reason_id' => $reason_id
        ];
        
        $database->query($sql, $params);
    }
    
    /**
     * Remove eligibility issue (member is now eligible)
     * @param int $member_id
     * @param int $company_id
     */
    private function removeEligibilityIssue($member_id, $company_id) {
        global $database;
        
        $sql = "DELETE FROM " . self::ELIGIBILITY_TABLE . " 
                WHERE member_id = :member_id 
                AND company_id = :company_id";
        
        $params = [
            'member_id' => $member_id,
            'company_id' => $company_id
        ];
        
        $database->query($sql, $params);
    }
    
    /**
     * Get display-friendly reason
     * @param int $reason_id
     * @param bool $detailed Show specific message or general category
     * @return array Reason data
     */
    public function getDisplayReason($reason_id, $detailed = false) {
        global $database;
        
        // Group similar issues for cleaner display
        if (!$detailed) {
            // Password issues -> general message
            if (in_array($reason_id, array(41, 42, 43, 44, 45, 46, 47))) {
                $reason_id = self::REASON_PASSWORD_REQUIREMENTS;
            }
            
            // Profile issues -> general message
            if (in_array($reason_id, array(2, 3, 4, 5, 6, 7, 8))) {
                $reason_id = self::REASON_INCOMPLETE_PROFILE;
            }
        }
        
        $sql = "SELECT * FROM " . self::REASONS_TABLE . " WHERE id = :reason_id";
        return $database->getrow($sql, ['reason_id' => $reason_id]);
    }
    
    /**
     * Get member profile data needed for eligibility checks
     * @param int $member_id
     * @return array Member data
     */
    private function getMemberProfile($member_id) {
        global $database;
        
        // Get user data
        $sql = "SELECT u.*
                FROM bg_users u 
                WHERE u.user_id = :user_id";
        
        $user = $database->getrow($sql, ['user_id' => $member_id]);
        
        if ($user) {
            // Check email verification status from validations table
            $verify_sql = "SELECT COUNT(*) as verified 
                          FROM bg_validations 
                          WHERE user_id = :user_id 
                          AND validation_type = 'email' 
                          AND status = 'validated'
                          AND validation_dt IS NOT NULL";
            
            $email_result = $database->getrow($verify_sql, ['user_id' => $member_id]);
            $user['email_verified'] = $email_result['verified'] > 0 ? 1 : 0;
            
            // Check phone verification status
            $phone_sql = "SELECT COUNT(*) as verified 
                         FROM bg_validations 
                         WHERE user_id = :user_id 
                         AND validation_type = 'phone' 
                         AND status = 'validated'
                         AND validation_dt IS NOT NULL";
            
            $phone_result = $database->getrow($phone_sql, ['user_id' => $member_id]);
            $user['phone_verified'] = $phone_result['verified'] > 0 ? 1 : 0;
        }
        
        return $user;
    }
    
    /**
     * Get company requirements
     * @param int $company_id
     * @return object Requirements data
     */
    private function getCompanyRequirements($company_id) {
        global $database;
        
        // Default requirements if none specified
        $requirements = new stdClass();
        $requirements->requires_phone = false;
        $requirements->requires_birthdate = true; // Birthday Gold always needs birthdate
        $requirements->requires_address = false;
        $requirements->requires_email_verification = false;
        $requirements->requires_phone_verification = false;
        $requirements->minimum_age = 13;  // Default minimum age
        $requirements->maximum_age = 120; // Default maximum age
        $requirements->restricted_states = null;
        $requirements->restricted_countries = null;
        
        // Get age requirements from bg_company_attributes (ABO extracted data)
        $age_sql = "SELECT description FROM bg_company_attributes 
                    WHERE company_id = :company_id 
                    AND type = 'age_requirements' 
                    AND name = 'birthday_program' 
                    AND status = 'active'
                    ORDER BY modify_dt DESC
                    LIMIT 1";
        
        $age_result = $database->getrow($age_sql, ['company_id' => $company_id]);
        
        if ($age_result && !empty($age_result['description'])) {
            $age_data = json_decode($age_result['description'], true);
            if (is_array($age_data)) {
                if (isset($age_data['minimum_age']) && is_numeric($age_data['minimum_age'])) {
                    $requirements->minimum_age = (int)$age_data['minimum_age'];
                }
                if (isset($age_data['maximum_age']) && is_numeric($age_data['maximum_age'])) {
                    $requirements->maximum_age = (int)$age_data['maximum_age'];
                }
            }
        }
        
        // Check for verification requirements from company policies
        $policy_sql = "SELECT name, description FROM bg_company_attributes 
                      WHERE company_id = :company_id 
                      AND type = 'policy' 
                      AND status = 'active'
                      AND name IN ('email_verification_required', 'phone_verification_required')";
        
        $policies = $database->getrows($policy_sql, ['company_id' => $company_id]);
        
        foreach ($policies as $policy) {
            if ($policy['name'] === 'email_verification_required' && $policy['description'] === '1') {
                $requirements->requires_email_verification = true;
            }
            if ($policy['name'] === 'phone_verification_required' && $policy['description'] === '1') {
                $requirements->requires_phone_verification = true;
            }
        }
        
        // Get location restrictions if any
        $location_sql = "SELECT description FROM bg_company_attributes 
                        WHERE company_id = :company_id 
                        AND type = 'location_restrictions' 
                        AND status = 'active'
                        LIMIT 1";
        
        $location_result = $database->getrow($location_sql, ['company_id' => $company_id]);
        
        if ($location_result && !empty($location_result['description'])) {
            $location_data = json_decode($location_result['description'], true);
            if (is_array($location_data)) {
                if (isset($location_data['restricted_states']) && is_array($location_data['restricted_states'])) {
                    $requirements->restricted_states = $location_data['restricted_states'];
                }
                if (isset($location_data['restricted_countries']) && is_array($location_data['restricted_countries'])) {
                    $requirements->restricted_countries = $location_data['restricted_countries'];
                }
            }
        }
        
        // For backward compatibility, also check bg_companies table
        $company_sql = "SELECT * FROM bg_companies WHERE company_id = :company_id";
        $company_data = $database->getrow($company_sql, ['company_id' => $company_id]);
        
        if ($company_data) {
            if (!empty($company_data['min_age'])) {
                $requirements->minimum_age = $company_data['min_age'];
            }
            if (!empty($company_data['max_age'])) {
                $requirements->maximum_age = $company_data['max_age'];
            }
        }
        
        return $requirements;
    }
    
    /**
     * Mark member eligibility for refresh when profile changes
     * @param int $member_id
     */
    public function markMemberEligibilityStale($member_id) {
        global $database;
        
        $sql = "UPDATE " . self::ELIGIBILITY_TABLE . " 
                SET last_checked = DATE_SUB(NOW(), INTERVAL 2 DAY)
                WHERE member_id = :member_id";
        
        $database->query($sql, ['member_id' => $member_id]);
    }
    
    /**
     * Get eligibility statistics
     * @return array Statistics data
     */
    public function getEligibilityStats() {
        global $database;
        
        $stats = array();
        
        // Total issues
        $sql = "SELECT COUNT(*) as total FROM " . self::ELIGIBILITY_TABLE;
        $result = $database->getrow($sql);
        $stats['total_issues'] = $result['total'] ?? 0;
        
        // Issues by reason
        $sql = "SELECT r.code, r.message, COUNT(*) as count 
                FROM " . self::ELIGIBILITY_TABLE . " e
                JOIN " . self::REASONS_TABLE . " r ON e.reason_id = r.id
                GROUP BY e.reason_id
                ORDER BY count DESC
                LIMIT 10";
        
        $stats['top_issues'] = $database->getrows($sql);
        
        // Stale records
        $sql = "SELECT COUNT(*) as count 
                FROM " . self::ELIGIBILITY_TABLE . "
                WHERE last_checked < DATE_SUB(NOW(), INTERVAL 48 HOUR)";
        
        $result = $database->getrow($sql);
        $stats['stale_records'] = $result['count'] ?? 0;
        
        // Affected users and companies
        $sql = "SELECT 
                COUNT(DISTINCT member_id) as affected_users,
                COUNT(DISTINCT company_id) as affected_companies
                FROM " . self::ELIGIBILITY_TABLE;
        
        $counts = $database->getrow($sql);
        $stats['affected_users'] = $counts['affected_users'];
        $stats['affected_companies'] = $counts['affected_companies'];
        
        return $stats;
    }
    
    /**
     * Get member age from birthdate
     * @param array $member
     * @return int Age in years
     */
    private function getMemberAge($member) {
        if (empty($member['birthdate'])) {
            return 0;
        }
        
        $birthdate = new DateTime($member['birthdate']);
        $today = new DateTime();
        $age = $today->diff($birthdate);
        
        return $age->y;
    }
    
    /**
     * Get action URL for a specific reason code
     * @param string $code Reason code
     * @return string|null Action URL or null
     */
    private function getActionUrlForCode($code) {
        $action_urls = array(
            // Profile issues
            'incomplete_profile' => '/myaccount/profile.php',
            'missing_phone' => '/myaccount/profile.php',
            'missing_email' => '/myaccount/profile.php',
            'missing_birthdate' => '/myaccount/profile.php',
            'missing_address' => '/myaccount/profile.php',
            'missing_name' => '/myaccount/profile.php',
            'invalid_phone' => '/myaccount/profile.php',
            'invalid_email' => '/myaccount/profile.php',
            
            // Verification issues
            'email_unverified' => '/myaccount/verify-email.php',
            'phone_unverified' => '/myaccount/verify-phone.php',
            'identity_unverified' => '/myaccount/verify-identity.php',
            'address_unverified' => '/myaccount/verify-address.php',
            
            // Security issues
            'password_requirements' => '/myaccount/security.php',
            'missing_password' => '/myaccount/security.php',
            'weak_password' => '/myaccount/security.php',
            'password_no_number' => '/myaccount/security.php',
            'password_no_special' => '/myaccount/security.php',
            'password_no_uppercase' => '/myaccount/security.php',
            'password_common' => '/myaccount/security.php',
            'password_expired' => '/myaccount/security.php',
            'mfa_required' => '/myaccount/2fa-setup.php',
            
            // Account issues
            'account_suspended' => '/support/contact.php',
            'account_inactive' => '/myaccount/',
            'payment_required' => '/myaccount/billing.php',
            'terms_not_accepted' => '/terms.php',
            'fraud_flag' => '/support/contact.php'
        );
        
        return isset($action_urls[$code]) ? $action_urls[$code] : null;
    }
}


