<?PHP

///////////////////////////////////////////////////////////////
$bg_secured_paths =  ['/myaccount/', '/admin/', '/staff/', '/hr/'];



///////////////////////////////////////////////////////////////
$bg_users_profile_keys = [
    'profile_username', 'profile_email', 'profile_password', 'profile_title', 'profile_first_name',
    'profile_middle_name', 'profile_last_name', 'profile_mailing_address', 'profile_city',
    'profile_state', 'profile_zip_code', 'profile_country', 'profile_phone_number',
    'profile_phone_type', 'profile_gender', 'profile_agree_terms', 'profile_agree_email',
    'profile_agree_text', 'profile_allergy_gluten', 'profile_allergy_sugar', 'profile_allergy_nuts',
    'profile_allergy_dairy', 'profile_diet_vegan', 'profile_diet_kosher', 'profile_diet_pescatarian',
    'profile_diet_keto', 'profile_diet_paleo', 'profile_diet_vegetarian', 'profile_military',
    'profile_educator', 'profile_firstresponder'
];

$bg_users_accountstatus = [
        'active' => 'Active',
        'giftlock' => 'Gift Lock',
        'pending' => 'Pending',
        'validated' => 'Validated',        
    'deleted' => 'Deleted',
    'duplicate' => 'Duplicate',
    ];

    

$bg_users_accounttypes = [
    'user' => 'User',
    'parental' => 'Parental',
    'minor' => 'Minor',
    'giftcertificate' => 'Gift Certificate',
];

$bg_users_accountplans = [
    'free' => 'Free',
    'gold' => 'Gold',
    'life' => 'Life',
    '-' => 'secondary',
];

// Array containing all the picture elements
$icons_writecomment = [
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f600/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f600/512.gif" alt="😀" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f603/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f603/512.gif" alt="😃" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f601/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f601/512.gif" alt="😁" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f609/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f609/512.gif" alt="😉" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f643/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f643/512.gif" alt="🙃" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f642/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f642/512.gif" alt="🙂" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f913/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f913/512.gif" alt="🤓" width="32" height="32"></picture>',
    '<picture><source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/270f_fe0f/512.webp" type="image/webp"><img src="https://fonts.gstatic.com/s/e/notoemoji/latest/270f_fe0f/512.gif" alt="✏" width="32" height="32"></picture>'
];

$bg_timezone_map = [
    'MOUNTAIN' => 'America/Denver',
    'PACIFIC' => 'America/Los_Angeles',
    'CENTRAL' => 'America/Chicago',
    'EASTERN' => 'America/New_York',
    'UTC' => 'UTC'
    // Add more mappings as needed
];



///////////////////////////////////////////////////////////////
$bg_users_accounttypecolors = [
    'user' => 'primary',
    'parental' => 'danger',
    'minor' => 'warning',
    'giftcertificate' => 'success',
    '-' => 'secondary',
];

$bg_users_accountplancolors = [
    'free' => 'primary',
    'gold' => 'warning',
    'life' => 'success',
    '-' => 'secondary',
];

$bg_users_accountstatuscolors = [
    'active' => 'success',
    'validated' => 'primary',
    'pending' => 'warning',
    'giftlock' => 'danger',
    'deleted' => 'danger',
    'duplicate' => 'danger',
    'admin-delete' => 'danger',
    '-' => 'secondary',
];


$bg_login_messages = [
    0 => 'Login unsuccessful. Please check your email/password and note passwords are case-sensitive.',
    1 => 'Hmm, that didn\'t work. Please check your email and password and try again.',
    2 => 'Still no luck? Double-check your credentials or use "Forgot Password" below.',
    3 => 'For security, please complete the CAPTCHA below before trying again.',
    4 => 'Multiple failed attempts detected. Consider resetting your password.',
    5 => 'Account temporarily locked for security. Please try again in 15 minutes.'
];

$bg_account_security = [
    'max_attempts' => 5,
    'lockout_minutes' => 15
];


///////////////////////////////////////////////////////////////
$bg_sales_associatecommissions =$bg_users_attributes_associatecommissions =$bg_users_attributes_associatedcommissions = [
    'hourly' => 10.33, // Hourly wage for Associate level
    'referral' => 1.00,
    'free' => 0.00,
    'gold' => 3.00,
    'life' => 3.00,
    'parental' => 4.00,
    'giftcertificate' => 3.00
];

$bg_sales_seniorcommissions = $bg_users_attributes_seniorcommissions = [
    'hourly' => 15.83, // Hourly wage for Senior level
    'referral' => 2.00,
    'free' => 0.00,
    'gold' => 2.00,
    'life' => 3.00,
    'parental' => 4.00,
    'giftcertificate' => 3.00, // Assuming you wanted to include this based on previous examples
    'family' => 4.00,
    'corporate' => 1.00,
    'business' => 250.00
];

$bg_sales_trackingstatus= [
    'Active' => 0,
    'Pending' => 0,
    'Paid' => 0,
    'Reviewing' => 0
];

$bg_enrollment_schedule_timeBlockMap = [
    1 => ['start' => '00:00', 'end' => '04:00'],
    2 => ['start' => '04:00', 'end' => '08:00'],
    3 => ['start' => '08:00', 'end' => '12:00'],
    4 => ['start' => '12:00', 'end' => '16:00'],
    5 => ['start' => '16:00', 'end' => '20:00'],
    6 => ['start' => '20:00', 'end' => '00:00'],
];



///////////////////////////////////////////////////////////////
$bg_systemdata_states = [
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
    'Washington D.C.' => 'DC',
    'West Virginia' => 'WV',
    'Wisconsin' => 'WI',
    'Wyoming' => 'WY',
];

$bg_funfacts = [
    'birth_flower.inc',
    'birth_stone.inc',
    'birth_color.inc',
    'astrosign.inc',
    'chinesezodiac.inc',
    'alive_days.inc',
    'heartbeats.inc',
    'gas_prices.inc',
    'car_prices.inc',
    'baby_names.inc',
    'toy_oftheyear.inc',
    'fashiontrend_oftheyear.inc',
    'random_facts.inc',
    'breaths_taken.inc',
    'hours_slept.inc',
    'minimum_wage.inc',
    'stamp_price.inc',
    'popular_movie.inc',
    'us_president.inc',
    'popular_computer.inc',
    'world_population.inc',
    'internet_users.inc',
    'home_price.inc',
];

$bg_systemdata_qikstaticcodemaps = [
    'A' => 'CDEFGH23JK4',
    'B' => 'MNOPQR45VW',
    'C' => 'A3DEF6J5K9',
    'D' => 'FG8JKLBCD2',
    'E' => 'C2FGHJ7XY9Z',
    'F' => 'MNO7PQRSTUX',
    'G' => 'ACDEFGH6JK8',
    'H' => 'FGHJKLM4BC9',
    'I' => 'NOPQRSTUVWXY',
    'J' => 'KLMNOPQRST7',
    'K' => 'AC9EFGHJKX3',
    'L' => 'MNO6QRSTVW7',
    'M' => 'ABCFG5JKL9',
    'N' => 'M9PQR7STUY5',
    'O' => 'DEFGHJKLMN',
    'P' => 'NO3QRSTUVWXY',
    'Q' => 'ACD9GHJK4',
    'R' => 'MNOPQ2ST7Z9',
    'S' => 'CDEFG7JK4M9',
    'T' => 'NO8QRSTUVW6',
    'U' => 'ACD6FGH9JKY',
    'V' => 'MNO7QRST3XY',
    'W' => 'A4DEFGH6JKZ9',
    'X' => 'M7PQ9STWZ',
    'Y' => 'ABCEFGH6JK8',
    'Z' => 'BCD9FGHJK7',
    '0' => 'DEFG2IJM4N5',
    '1' => 'FG3JKLMN9PQ4',
    '2' => 'HIJKLMNO3PQ5',
    '3' => 'GHIJK4MNPQR7',
    '4' => 'HIJKL5MN8QR9',
    '5' => 'IJKLMNOPQRST',
    '6' => 'JKLMN6OPR7ST',
    '7' => 'KLMNO7PQRST8V',
    '8' => 'LMNOP8QRST9VW',
    '9' => 'MNOPQRSTU8VWZ',
];

$bg_systemdata_useragents_desktop = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
    'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.64',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Safari/605.1.15',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Firefox/78.0',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15'
];

$bge_companydir='/admin/bgrab';