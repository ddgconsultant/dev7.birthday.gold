<?PHP
class TimeClock
{
  private $db;
  private $session;

  public function __construct($database, $session)
  {
    $this->db = $database;
    $this->session = $session;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function clock_in($user_id)
  {
    $query = "INSERT INTO bg_timeclock (user_id, `type`, clock_in, create_dt, modify_dt, create_by, modify_by) VALUES (:user_id, 'user', NOW(), NOW(), NOW(), :user_idc, :user_idm)";
    $this->db->prepare($query)->execute(['user_id' => $user_id, 'user_idc' => $user_id, 'user_idm' => $user_id]);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function clock_out($user_id)
  {
    $query = "UPDATE bg_timeclock SET clock_out = NOW() , `modify_dt`=now(), modify_by=:user_idm  WHERE user_id = :user_id AND clock_out IS NULL and `type`='user'";
    $this->db->prepare($query)->execute(['user_id' => $user_id,  'user_idm' => $user_id]);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function timecard_lock($user_id, $reason)
  {
    $query = "INSERT INTO bg_timeclock (user_id, `type`, clock_in, reason, create_dt, modify_dt, create_by, modify_by) VALUES (:user_id, 'lock', NOW(), :reason,  NOW(), NOW(), :user_idc, :user_idm)";
    $this->db->prepare($query)->execute(['user_id' => $user_id, 'reason' => $reason, 'user_idc' => $user_id, 'user_idm' => $user_id]);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function timecard_unlock($user_id, $entry_id, $reason)
  {
    $query = "UPDATE bg_timeclock set  clock_out=now(), `msg`=:message, modify_dt=now(), modify_by=:user_idm WHERE entry_id = :entry_id";
    $this->db->prepare($query)->execute(['message' => $reason, 'user_idm' => $user_id,  'entry_id' => $entry_id]);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function report_hours($user_id)
  {
    $results = ['day' => 0, 'week' => 0, 'payperiod' => 0, 'month' => 0, 'year' => 0];
    $queries = [
      'day' => "SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) AS minutes FROM bg_timeclock WHERE user_id = :user_id AND DATE(clock_in) = CURDATE() and `type`='user'",
      'week' => "SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) AS minutes FROM bg_timeclock WHERE user_id = :user_id AND YEARWEEK(clock_in, 1) = YEARWEEK(CURDATE(), 1) and `type`='user'",
      'payperiod' => "SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) AS minutes FROM bg_timeclock WHERE user_id = :user_id AND clock_in >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE())-1 DAY) AND clock_in < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE())-1 DAY), INTERVAL 1 MONTH) and `type`='user'",
      'month' => "SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) AS minutes FROM bg_timeclock WHERE user_id = :user_id AND MONTH(clock_in) = MONTH(CURDATE()) AND YEAR(clock_in) = YEAR(CURDATE()) and `type`='user'",
      'year' => "SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) AS minutes FROM bg_timeclock WHERE user_id = :user_id AND YEAR(clock_in) = YEAR(CURDATE()) and `type`='user'"
    ];

    foreach ($queries as $key => $query) {
      $stmt = $this->db->prepare($query);
      $stmt->execute(['user_id' => $user_id]);
      $result = $stmt->fetchColumn();
      $results[$key] = floor($result / 60 * 4) / 4; // Convert minutes to hours and round down to nearest quarter-hour
    }

    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get_time_records($user_id)
  {
    $sql = "SELECT `entry_id`, `user_id`, `clock_in`, `clock_out`, ifnull(`reason`, '') as reason_for_change, `create_dt`, `modify_dt` , (TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) AS minutes, floor((TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) / 60 * 4) / 4 as timeformatted FROM bg_timeclock WHERE user_id = :user_id  and `type`='user' ORDER BY clock_in asc";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get_locked_records()
  {
    $sql = "SELECT `entry_id`, u.*, `clock_in`, `clock_out`, ifnull(`reason`, '') as reason_for_lock, 0 as timeformatted, ifnull(msg, '') msg, t.`create_dt` as t_create_dt, t.`modify_dt` as t_modify_dt  FROM bg_timeclock t, bg_users u WHERE t.user_id = u.user_id  and t.`type`='lock' and clock_out is null ORDER BY clock_in asc";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function update_time_record($entry_id, $clock_in, $clock_out, $reason, $user_id)
  {
    $sql = "UPDATE bg_timeclock SET clock_in = :clock_in, clock_out = :clock_out, reason = :reason, `modify_dt`=now(), modify_by=:user_idm  WHERE entry_id = :entry_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      'entry_id' => $entry_id,
      'clock_in' => $clock_in,
      'clock_out' => $clock_out,
      'reason' => $reason,
      'user_idm' => $user_id
    ]);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function is_clocked_in($user_id)
  {
    $result = false;
    $query = "SELECT COUNT(*) FROM bg_timeclock WHERE user_id = :user_id AND clock_out IS NULL  and `type`='user'";
    $stmt = $this->db->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) $result = true;
    return $result;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function is_locked($user_id)
  {
    $result = false;
    $query = "SELECT COUNT(*) FROM bg_timeclock WHERE user_id = :user_id AND clock_out IS NULL  and `type`='lock'";
    $stmt = $this->db->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) $result = true;
    return $result;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function is_recent_unlock($user_id)
  {
    // Initialize the result as false, assuming no recent unlock has occurred
    $result = false;

    // Prepare the SQL query to check for a 'lock' record that has been cleared within the last 24 hours
    $query = "SELECT COUNT(*) FROM bg_timeclock WHERE user_id = :user_id AND clock_out IS NOT NULL AND `type`='lock' AND clock_out >= NOW() - INTERVAL 1 DAY";

    // Prepare the SQL statement
    $stmt = $this->db->prepare($query);

    // Execute the statement with the provided user_id
    $stmt->execute(['user_id' => $user_id]);

    // Fetch the count of records that match the criteria
    $count = $stmt->fetchColumn();

    // If the count is greater than 0, it means a lock was cleared within the last 24 hours
    if ($count > 0) {
      $result = true;
    }

    // Return the result
    return $result;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get_payroll($user_id)
  {
    $query = "
  SELECT 
  u.user_id,
  u.first_name,
  CAST(MIN(a.description) AS DECIMAL(10,2)) AS hourly_rate,  
  SUM(TIMESTAMPDIFF(MINUTE, tc.clock_in, tc.clock_out)) AS total_minutes,
  SUM(FLOOR(TIMESTAMPDIFF(MINUTE, tc.clock_in, tc.clock_out) / 15) * 15) AS rounded_minutes,
  SUM(CAST(a.description AS DECIMAL(10,2)) * (FLOOR(TIMESTAMPDIFF(MINUTE, tc.clock_in, tc.clock_out) / 15) * 15 / 60)) AS pay_amount
FROM 
  bg_users AS u 
JOIN 
  bg_user_attributes AS a ON u.user_id = a.user_id 
JOIN 
  bg_timeclock AS tc ON u.user_id = tc.user_id
WHERE 
  a.name = 'hourly_pay_rate' AND a.status = 'A'
  AND tc.clock_in >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
  AND tc.clock_in < CURDATE() 
  AND u.user_id=:user_id
GROUP BY 
  u.user_id, u.first_name";

  $stmt = $this->db->prepare($query);

  // Execute the statement with the provided user_id
  $stmt->execute([':user_id' => $user_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }



}
