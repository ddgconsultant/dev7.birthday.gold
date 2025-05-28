<?PHP

$addClasses[] = 'TimeClock';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



$timeclock->get_payroll_total(20);

$query = "SELECT u.user_id, u.name, a.value AS hourly_rate, tc.clock_in, tc.clock_out 
          FROM bg_users AS u 
          JOIN bg_user_attributes AS a ON u.user_id = a.user_id 
          JOIN bg_timeclock AS tc ON u.user_id = tc.user_id
          WHERE a.name = 'hourly_pay_rate' AND a.status = 'A'
          AND tc.clock_in >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
          AND tc.clock_in < CURDATE() 
          ORDER BY u.user_id, tc.clock_in";
