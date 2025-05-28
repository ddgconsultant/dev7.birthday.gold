<?PHP

$addClasses[] = 'Referral';
$addClasses[] = 'TimeClock';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

if (!empty($current_user_data['user_id'])){


    $goto_locked=false;
    $nodb=false;
    $lock_reason='';
$recentunlock=false;
#-------------------------------------------------------------------------------
# check to see if user is has restrictions
#-------------------------------------------------------------------------------

if (!empty($timeclock->is_locked($current_user_data['user_id']) && !$goto_locked)) {
    $goto_locked=true;
    $nodb=true;
    $lock_reason=$current_user_data['first_name'].', your||Time Card is still locked.';

}

$recentunlock=$timeclock->is_recent_unlock($current_user_data['user_id']);


$timereport=$timeclock->report_hours($current_user_data['user_id']);

// -- max hours per day
$value=$account->getUserAttribute($current_user_data['user_id'], 'timeclock::max_hours_day');
if (!empty($value) && !$goto_locked ) {
   if ( $timereport['day']>=$value['description'] && !$recentunlock) {
    $goto_locked=true;
    $lock_reason=$current_user_data['first_name'].', you are at||Max Hours for the day - Yours: '.$timereport['day'].' / Max: '.$value['description'];
   }
}


   
#breakpoint($value);
// -- max hours per week
$value=$account->getUserAttribute($current_user_data['user_id'], 'timeclock::max_hours_week');
if (!empty($value) && !$goto_locked) {
   if ( $timereport['week']>=$value['description'] && !$recentunlock) {
    $goto_locked=true;
    $lock_reason=$current_user_data['first_name'].', you are at||Max Hours for the week - Yours: '.$timereport['day'].' / Max: '.$value['description'];
   }
}




// -- growth_to_hours_ratio
$value=$account->getUserAttribute($current_user_data['user_id'], 'timeclock::growth_to_hours_ratio');
$value_minhours=$account->getUserAttribute($current_user_data['user_id'], 'timeclock::growth_to_hours_minimumhours');
if (!empty($value) && !$goto_locked) {
    $referralstats=$referral->stats();

    # $referralstats['today_total'] = 3
    # $value['description'] = 1
    # $value_minhours['description'] = 4

 // Check if hours worked is at least at the minimum threshold.
 if ($timereport['day'] >= $value_minhours['description'] && !$recentunlock) {
     // Check if the hours worked is greater than or equal to the minimum hours after which the rule applies.
     if ($timereport['day'] >= $value_minhours['description']) {
        // Calculate the minimum required referrals based on hours worked.
        // Since the rule kicks in after $value_minhours, we add 1 to ensure the minimum referral per hour is met.
        $minRequiredReferrals = $timereport['day'] - $value_minhours['description'] + 1;

        // Check if the total referrals is less than the minimum required referrals.
        if ($referralstats['today_total'] < $minRequiredReferrals) {
            $goto_locked = true;
            $lock_reason = $current_user_data['first_name'].', your||Time card locked due to insufficient referrals. Required: ' . $minRequiredReferrals . ', Yours: ' . $referralstats['today_total'];
        }
    }
}

}

if ($goto_locked) {
    $referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
$lock_reason_msg=str_replace('||', ' ', $lock_reason);
$lock_reason_x=explode('||', $lock_reason);

    $session->set('clockin_lock_reason', $lock_reason_msg);
     $session->set('clockin_referer_page', $referrer);
if (!$nodb) $timeclock->timecard_lock($current_user_data['user_id'], $lock_reason_x[1]);

    header('Location: /staff/clockin_locked');
exit;
}
}


$timeclock->clock_in($current_user_data['user_id']);
$referrer = $_SERVER['HTTP_REFERER'] ?? '/myaccount/';
header('Location: ' . $referrer);