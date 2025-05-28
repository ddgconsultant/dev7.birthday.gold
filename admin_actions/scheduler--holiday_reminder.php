<?PHP
$addClasses[] = 'chat';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$debug=false;
// Get tomorrow's date
$tomorrowDate = date('Y-m-d', strtotime('+1 day'));


$messagessuffixes = array(
    " Please ensure you close out any necessary tasks so you can enjoy the day off without concerns.",
    " Make sure to plan ahead and finish any outstanding tasks before {{formattedHolidayDate}} to enjoy your day off without worries.",
    " Wrap up any pending work in advance to fully relax and enjoy the day off.",
    " Be sure to take care of unfinished tasks so you can make the most of your time off.",
    " Complete any outstanding work beforehand so you can unwind and enjoy your day.",
    " Take care of all pending tasks so you can fully enjoy your break without any distractions.",
    " Plan ahead and finish your work early to fully enjoy the holiday on {{formattedHolidayDate}}.",
    " Get your tasks squared away before {{formattedHolidayDate}} so you can enjoy the day off stress-free.",
    " Make sure all necessary work is handled beforehand so you can relax on your day off.",
    " Clear up your to-do list before the holiday so you can make the most of your time off.",
    " Be sure to complete your tasks early so you can enjoy the holiday without worry.",
    " Wrap up your tasks ahead of time to have a worry-free and relaxing holiday."
);



$qik->logmessage("starting holiday reminder script");
$upcomingdays=7;
// Query to get holidays that occur tomorrow
$query = "
SELECT 
    `category`,
    `name` AS Holiday,
    MAX(`content`) AS `HolidayDetails`,  -- Assuming content is aggregated for uniqueness
    MAX(`description`) AS `HolidayDate`, -- Assuming description is aggregated for uniqueness
    MAX(CASE WHEN `grouping` = YEAR(CURRENT_DATE()) THEN `content` END) AS `Current Year`,
    MAX(CASE WHEN `grouping` = YEAR(CURRENT_DATE()) + 1 THEN `content` END) AS `Next Year`,
    -- New columns for upcoming holidays
    MAX(CASE WHEN DATE(STR_TO_DATE(`description`, '%m/%d/%Y')) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 'Yes' ELSE 'No' END) AS `HolidayIn24Hrs`,
    MAX(CASE WHEN DATE(STR_TO_DATE(`description`, '%m/%d/%Y')) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ".$upcomingdays." DAY) THEN 'Yes' ELSE 'No' END) AS `UpcomingHoliday`
FROM 
    `bg_content`
WHERE 
    `type` LIKE 'calendar_%' 
    AND (
        DATE(STR_TO_DATE(`description`, '%m/%d/%Y')) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
        OR DATE(STR_TO_DATE(`description`, '%m/%d/%Y')) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ".$upcomingdays." DAY)
    )
    AND `grouping` IN (YEAR(CURRENT_DATE()), YEAR(CURRENT_DATE()) + 1)
GROUP BY 
    `category`, `name`
ORDER BY 
    MIN(`publish_dt`),
    MIN(STR_TO_DATE(`description`, '%m/%d/%Y')) 
LIMIT 15;
";
$qik->logmessage($query);
#breakpoint($query);
// Execute the holiday query

$stmt = $database->prepare($query);
$stmt->execute();
$holidayResults =  $stmt->fetchAll(PDO::FETCH_ASSOC);

$qik->logmessage("COUNT: ".count($holidayResults));
#breakpoint($holidayResults  );
// Check if there are any holidays tomorrow
if ($holidayResults) {
    $qik->logmessage("Starting to process holidays...");
    $qik->logmessage(print_r($holidayResults, true)); // Log the content of $holiday

    $staffdetails = $account->getstaff();
    $qik->logmessage("Fetching staff details...");

    // Fetch all staff users
    $staffQuery = "
        SELECT distinct 
            u.user_id, 
            u.username, 
            u.email, 
            u.zip_code, 
            u.first_name, 
            u.last_name, 
            u.avatar, 
            z.timezone
        FROM 
            bg_users u
        LEFT JOIN 
            ds_ref_zipcodes z ON u.zip_code = z.zipcode
        WHERE 
            EXISTS (
                " . str_replace('WHERE', 'WHERE staff_ua.user_id = u.user_id AND ', $staffdetails['sql']) . "
            )
    ";
    $qik->logmessage("Executing staff query: " .$staffQuery);

  
    
    // Execute the staff query
$stmt = $database->prepare($staffQuery);
$stmt->execute();
$staffResults = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all staff members

$qik->logmessage(count($staffResults) . " staff members fetched.");







// Assume you have already executed the query and have $holidayResults from your database
foreach ($holidayResults as $holiday) {
    $holidaycategory = $holiday['category'];
    $holidayName = $holiday['Holiday'];
    $holidayDetails = $holiday['HolidayDetails']; // Example: "Friday, October 11, 2024"
    $holidayDate = new DateTime($holiday['HolidayDate']); // Example: "10/11/2024"

    $qik->logmessage("Processing holiday: {$holidayName} ({$holidayDetails})");

    // Check if the holiday is within 24 hours or within the upcoming $upcomingdays days
    $isHolidayIn24Hrs = ($holiday['HolidayIn24Hrs'] === 'Yes');
    $isUpcomingHoliday = ($holiday['UpcomingHoliday'] === 'Yes');

    // Loop through all staff members
    foreach ($staffResults as $staff) {
        $qik->logmessage("Processing staff member: {$staff['first_name']} {$staff['last_name']} ({$staff['username']})");

        $attribute = 'employee_onboarding::rocketchat_user';  // Use type and name
        $description = $account->getUserAttribute($staff['user_id'], $attribute, '', 'description');

        if ($description) {
            $channel = '@' . $description;

            // Determine the user's timezone (default to UTC if not found)
            $userTimezone = $staff['timezone'] ?: 'UTC';

            // Map the custom timezone to a valid PHP timezone
            $validTimezone = isset($bg_timezone_map[$userTimezone]) ? $bg_timezone_map[$userTimezone] : 'UTC';

            $qik->logmessage("User timezone: {$userTimezone} (mapped to: {$validTimezone})");

            try {
                // Set the timezone for the holiday date and current time
                $userTimeZoneObj = new DateTimeZone($validTimezone);

                // Get the current UTC time and convert it to the user's local time
                $currentDateTimeUTC = new DateTime('now', new DateTimeZone('UTC'));
                $currentDateTimeUser = clone $currentDateTimeUTC;
                $currentDateTimeUser->setTimezone($userTimeZoneObj);

                // Get the hour in the user's local time
                $currentHourUser = (int)$currentDateTimeUser->format('H');
                $qik->logmessage("Current time for {$staff['username']} is " . $currentDateTimeUser->format('Y-m-d H:i:s'));

                // Determine if the holiday is tomorrow in the user's timezone
                $tomorrowInUserTimezone = clone $currentDateTimeUser;
                $tomorrowInUserTimezone->modify('+1 day')->setTime(0, 0, 0);
                $isHolidayTomorrow = ($holidayDate->format('Y-m-d') === $tomorrowInUserTimezone->format('Y-m-d'));

                $personalMessage = '';

                           // This block will execute only if it's 7 AM localtime
                           $sendtimehour=7;
                if ($currentHourUser === $sendtimehour) {     
                    // Process based on the time and holiday proximity
                    if ($isHolidayIn24Hrs && $isHolidayTomorrow) {

                        shuffle($messagessuffixes);

                        // If the holiday is tomorrow, send a "tomorrow" message
                        $formattedHolidayDate = $holidayDate->format('F j, Y'); // Example: "October 11, 2024"

                        $message1 = str_replace('{{formattedHolidayDate}}', $formattedHolidayDate, $messagessuffixes[0]);
                        $message2 = str_replace('{{formattedHolidayDate}}', $formattedHolidayDate, $messagessuffixes[1]);


                        $personalMessage = "Hi {$staff['first_name']}, just a reminder... tomorrow is a \"{$holidaycategory}\" -- {$holidayDetails} to observe {$holidayName}.";
                        $personalMessage .= $message1;
                      
                        $qik->logmessage("Sending 'tomorrow' message to {$staff['username']}...");


                    } elseif ($isUpcomingHoliday &&  (date('N') == 1 || date('N') ==3)) {
                        // If the holiday is within the next $upcomingdays days, send an upcoming holiday message -- only if it's Monday or Wednesday
                        $formattedHolidayDate = $holidayDate->format('F j, Y'); // Example: "October 11, 2024"

                        $personalMessage = "Hi {$staff['first_name']}, the next \"{$holidaycategory}\" is coming up soon on {$holidayDetails}.  Review https://www.bd.gold/BDGOLD_timeoff for more information.";
                        $personalMessage .= $message2;
                        
                        $qik->logmessage("Sending 'upcoming holiday' message to {$staff['username']}...");
                    }

                    // If there's a message to send, send it to the Rocket.Chat channel
                    if ($personalMessage != '') {
                     #   $channel='@Richard';
                     $qik->logmessage("Posting message to channel: {$channel}");
                        $system->postToRocketChat($personalMessage, $channel);
                    }
                } else {
                    // Log if it's not the $sendtimehour AM hour
                    $qik->logmessage("Not $sendtimehour AM for {$staff['username']}, no message sent.");
                }
            } catch (Exception $e) {
                $qik->logmessage("Error creating DateTimeZone: " . $e->getMessage());
            }
        }
    }
}
$qik->logmessage("Holiday processing completed.");

}
session_tracking('holidaynotification_log', $qik->logmessage('!FINALIZE!'));