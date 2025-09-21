<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

// Get the recipient criteria from the request
$recipient_criteria = isset($_POST['recipient_criteria']) ? json_decode($_POST['recipient_criteria'], true) : ['type' => 'all'];
$cta_category = isset($_POST['cta_category']) ? $_POST['cta_category'] : '';

// Build the query based on recipient criteria
$query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.city, u.birth_month, u.birth_day
          FROM bg_users u 
          WHERE u.status = 'active'";

$params = [];

// Apply recipient criteria filters
if (isset($recipient_criteria['type']) && $recipient_criteria['type'] === 'custom') {
    $conditions = [];
    
    foreach ($recipient_criteria['criteria'] as $criterion) {
        switch ($criterion['field']) {
            case 'birthday_month':
                if ($criterion['operator'] === 'equals') {
                    $conditions[] = "u.birth_month = :birth_month";
                    $params['birth_month'] = $criterion['value'];
                } elseif ($criterion['operator'] === 'in') {
                    $months = explode(',', $criterion['value']);
                    $placeholders = [];
                    foreach ($months as $idx => $month) {
                        $placeholders[] = ":month_$idx";
                        $params["month_$idx"] = trim($month);
                    }
                    $conditions[] = "u.birth_month IN (" . implode(',', $placeholders) . ")";
                }
                break;
                
            case 'age':
                if ($criterion['operator'] === 'greater_than') {
                    $conditions[] = "TIMESTAMPDIFF(YEAR, CONCAT('1900-', LPAD(u.birth_month, 2, '0'), '-', LPAD(u.birth_day, 2, '0')), CURDATE()) > :age_gt";
                    $params['age_gt'] = $criterion['value'];
                } elseif ($criterion['operator'] === 'less_than') {
                    $conditions[] = "TIMESTAMPDIFF(YEAR, CONCAT('1900-', LPAD(u.birth_month, 2, '0'), '-', LPAD(u.birth_day, 2, '0')), CURDATE()) < :age_lt";
                    $params['age_lt'] = $criterion['value'];
                } elseif ($criterion['operator'] === 'between') {
                    $ages = explode('-', $criterion['value']);
                    $conditions[] = "TIMESTAMPDIFF(YEAR, CONCAT('1900-', LPAD(u.birth_month, 2, '0'), '-', LPAD(u.birth_day, 2, '0')), CURDATE()) BETWEEN :age_min AND :age_max";
                    $params['age_min'] = $ages[0];
                    $params['age_max'] = $ages[1];
                }
                break;
                
            case 'city':
                if ($criterion['operator'] === 'equals') {
                    $conditions[] = "u.city = :city";
                    $params['city'] = $criterion['value'];
                } elseif ($criterion['operator'] === 'contains') {
                    $conditions[] = "u.city LIKE :city_like";
                    $params['city_like'] = '%' . $criterion['value'] . '%';
                }
                break;
                
            case 'state':
                if ($criterion['operator'] === 'equals') {
                    $conditions[] = "u.state = :state";
                    $params['state'] = $criterion['value'];
                } elseif ($criterion['operator'] === 'in') {
                    $states = explode(',', $criterion['value']);
                    $placeholders = [];
                    foreach ($states as $idx => $state) {
                        $placeholders[] = ":state_$idx";
                        $params["state_$idx"] = trim($state);
                    }
                    $conditions[] = "u.state IN (" . implode(',', $placeholders) . ")";
                }
                break;
                
            case 'plan_type':
                $conditions[] = "EXISTS (SELECT 1 FROM bg_user_attributes WHERE user_id = u.user_id AND name = 'plan_type' AND value = :plan_type)";
                $params['plan_type'] = $criterion['value'];
                break;
                
            case 'last_login':
                $days = intval($criterion['value']);
                if ($criterion['operator'] === 'within') {
                    $conditions[] = "u.last_login_dt > DATE_SUB(NOW(), INTERVAL :login_days DAY)";
                    $params['login_days'] = $days;
                } elseif ($criterion['operator'] === 'not_within') {
                    $conditions[] = "u.last_login_dt <= DATE_SUB(NOW(), INTERVAL :login_days DAY)";
                    $params['login_days'] = $days;
                }
                break;
        }
    }
    
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
}

// Get just the first matching user for preview
$query .= " LIMIT 1";

$preview_user = $database->getrow($query, $params);

if (!$preview_user) {
    // If no users match criteria, get a default user for preview
    $preview_user = $database->getrow("SELECT user_id, first_name, last_name, email, city, birth_month, birth_day 
                                        FROM bg_users 
                                        WHERE status = 'active' 
                                        LIMIT 1");
}

// Get sample companies for CTA block preview
$cta_companies = [];
if ($cta_category) {
    $company_query = "SELECT c.company_id, c.company_name, c.logo, c.offer_text 
                      FROM bg_company c 
                      WHERE c.status = 'active' 
                      AND c.category_id = :category
                      ORDER BY RAND() 
                      LIMIT 3";
    
    $cta_companies = $database->getrows($company_query, ['category' => $cta_category]);
}

// Format birth month name
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$response = [
    'success' => true,
    'user' => [
        'first_name' => $preview_user['first_name'] ?: 'John',
        'last_name' => $preview_user['last_name'] ?: 'Doe',
        'email' => $preview_user['email'] ?: 'john.doe@example.com',
        'city' => $preview_user['city'] ?: 'Seattle',
        'birthday_month' => isset($month_names[$preview_user['birth_month']]) ? $month_names[$preview_user['birth_month']] : 'January'
    ],
    'companies' => $cta_companies,
    'matched_criteria' => !empty($params) // Indicates if a real match was found
];

echo json_encode($response);
?>