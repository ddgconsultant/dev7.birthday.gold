<?php
/**
 * Save Reward - Handles create/update for company rewards from reward-editor modal
 */
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/brands.php');
    exit;
}

$company_id = $_POST['company_id'] ?? null;
$reward_id = $_POST['reward_id'] ?? null;

if (!$company_id) {
    $_SESSION['error'] = 'Company ID is required';
    header('Location: /admin/brands.php');
    exit;
}

// Get form values
$reward_name = trim($_POST['reward_name'] ?? '');
$reward_type = $_POST['reward_type'] ?? 'physical';
$reward_description_short = trim($_POST['reward_description_short'] ?? '');
$reward_description_long = trim($_POST['reward_description_long'] ?? '');
$reward_value = $_POST['reward_value'] !== '' ? $_POST['reward_value'] : null;
$cash_value = $_POST['cash_value'] !== '' ? $_POST['cash_value'] : null;
$location_id = $_POST['location_id'] !== '' ? $_POST['location_id'] : null;
$minage = $_POST['minage'] !== '' ? $_POST['minage'] : 0;
$maxage = $_POST['maxage'] !== '' ? $_POST['maxage'] : 150;
$mindaysstart = $_POST['mindaysstart'] !== '' ? $_POST['mindaysstart'] : 0;

// Default reward_name to reward_description_short if empty
if (empty($reward_name) && !empty($reward_description_short)) {
    $reward_name = ucwords(strtolower($reward_description_short));
}

try {
    if (!empty($reward_id)) {
        // UPDATE existing reward
        $sql = "UPDATE bg_company_rewards SET
                    reward_name = :reward_name,
                    reward_type = :reward_type,
                    reward_description_short = :reward_description_short,
                    reward_description_long = :reward_description_long,
                    reward_value = :reward_value,
                    cash_value = :cash_value,
                    location_id = :location_id,
                    minage = :minage,
                    maxage = :maxage,
                    mindaysstart = :mindaysstart,
                    modify_dt = NOW(),
                    status = 'active'
                WHERE reward_id = :reward_id AND company_id = :company_id";

        $stmt = $database->prepare($sql);
        $stmt->execute([
            'reward_name' => $reward_name,
            'reward_type' => $reward_type,
            'reward_description_short' => $reward_description_short,
            'reward_description_long' => $reward_description_long,
            'reward_value' => $reward_value,
            'cash_value' => $cash_value,
            'location_id' => $location_id,
            'minage' => $minage,
            'maxage' => $maxage,
            'mindaysstart' => $mindaysstart,
            'reward_id' => $reward_id,
            'company_id' => $company_id
        ]);

        $_SESSION['success'] = 'Reward updated successfully';
    } else {
        // INSERT new reward
        $sql = "INSERT INTO bg_company_rewards
                    (company_id, location_id, category, reward_type, reward_name,
                     reward_description_short, reward_description_long,
                     reward_value, cash_value, minage, maxage, mindaysstart,
                     create_dt, status)
                VALUES
                    (:company_id, :location_id, 'birthday', :reward_type, :reward_name,
                     :reward_description_short, :reward_description_long,
                     :reward_value, :cash_value, :minage, :maxage, :mindaysstart,
                     NOW(), 'active')";

        $stmt = $database->prepare($sql);
        $stmt->execute([
            'company_id' => $company_id,
            'location_id' => $location_id,
            'reward_type' => $reward_type,
            'reward_name' => $reward_name,
            'reward_description_short' => $reward_description_short,
            'reward_description_long' => $reward_description_long,
            'reward_value' => $reward_value,
            'cash_value' => $cash_value,
            'minage' => $minage,
            'maxage' => $maxage,
            'mindaysstart' => $mindaysstart
        ]);

        $_SESSION['success'] = 'Reward created successfully';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

// Redirect back to company editor rewards tab
header('Location: /admin/company-editor-main?cid=' . $company_id . '&section=rewardeditor');
exit;
