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
    function grabdetails($database, $adminDetails, $userId, $companyId, $return)
    {
        // Get user details
        global $account;
        #$stmt = $database->query("SELECT * FROM bg_users WHERE user_id = :userId", ['userId' => $userId]);
        #$userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        $userDetails = $account->getuserdata($userId, 'user_id');


        $sql = "SELECT uc.user_company_id, c.company_name, uc.user_id, uc.company_id, uc.status,  c.status as company_status, 
SUBSTRING_INDEX(c.signup_url, '/', 3) AS signup_domain, c.signup_url,  c.bgrab_domain
FROM bg_user_companies uc
LEFT JOIN bg_companies c ON uc.company_id = c.company_id
left join bg_users u on uc.user_id=u.user_id
WHERE ((uc.status not in ('success', 'success-btn', 'success-sub', 'failed', 'removed')) or (uc.status ='failed' and u.modify_dt>uc.modify_dt)) and c.status='finalized' and c.signup_url != 'APP ONLY' 
and uc.user_id = :userId {{find_company}}  
AND NOT (uc.`status` LIKE '%failed%' AND uc.`reason` = 'account_exists')
order by uc.create_dt desc ";


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
    #    if ($return == 'js')
   #         $stmt = $database->query($sql, [':userId' => $userId]);
   #     else
            $stmt = $database->query($sql, [':userId' => $userId, ':companyId' => $companyId]);
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
}


