<?php

class Referral
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
  public function user_list($user_id = '')
  {
    if ($user_id == '') {
      $user = $this->session->get('current_user_data', '');
      $user_id = $user['user_id'];
    }

    $sql = 'SELECT u.*, a.status as referral_status, IFNULL(a.rank, 0) as referral_payout, 
          CASE WHEN DATE(a.create_dt) = CURDATE() THEN 1 ELSE 0 END as today_flag 
          FROM bg_users u 
          JOIN bg_user_attributes a ON u.user_id = a.name 
          WHERE a.user_id = :user_id and a.type="referred" limit 1';

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $results = $stmt->fetch(PDO::FETCH_ASSOC);

    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get_refcode_details($code = '')
  {
    $sql = 'SELECT a.user_id from bg_user_attributes a where `category`="referral_code" and `name`=:code limit 1';
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':code' => $code]);
    $results = $stmt->fetchOne(PDO::FETCH_ASSOC);
    return $results;
  }



  
  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getreferer($user_id = '')
  {
    if ($user_id == '') {
      $user = $this->session->get('current_user_data', '');
      $user_id = $user['user_id'];
    }

    $sql = 'SELECT u.*, a.status as referral_status, IFNULL(a.rank, 0) as referral_payout, 
          CASE WHEN DATE(a.create_dt) = CURDATE() THEN 1 ELSE 0 END as today_flag 
          FROM bg_users u 
          JOIN bg_user_attributes a ON u.user_id = a.user_id 
          WHERE a.name = :user_id and a.type="referred" limit 1';

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $results = $stmt->fetch(PDO::FETCH_ASSOC);

    return $results;
  
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function stats($user_id = '')
  {
    if ($user_id == '') {
      $user = $this->session->get('current_user_data', '');
      $user_id = $user['user_id'];
    }

    // SQL for grand total and distinct user count
    $sqlTotal = 'SELECT IFNULL(SUM(IFNULL(a.rank,0)),0) as grand_total, COUNT(DISTINCT a.name) as distinct_user_count_total 
               FROM bg_user_attributes a 
               WHERE a.user_id = :user_id AND a.type="referred"';

    // SQL for today's total count
    $sqlToday = 'SELECT COUNT(*) as today_total FROM bg_user_attributes WHERE user_id = :user_id AND DATE(create_dt) = CURDATE() AND type="referred"';

    // SQL for this week's total count
    $sqlThisWeek = 'SELECT COUNT(*) as this_week_total FROM bg_user_attributes WHERE user_id = :user_id AND YEARWEEK(create_dt, 1) = YEARWEEK(CURDATE(), 1) AND type="referred"';

    // SQL for this month's total count
    $sqlThisMonth = 'SELECT COUNT(*) as this_month_total FROM bg_user_attributes WHERE user_id = :user_id AND MONTH(create_dt) = MONTH(CURDATE()) AND YEAR(create_dt) = YEAR(CURDATE()) AND type="referred"';

    // SQL for past 30 days total and distinct user count
    $sqlLast30Days = 'SELECT IFNULL(SUM(IFNULL(a.rank,0)),0) as last_30_days_total, COUNT(DISTINCT a.name) as distinct_user_count_last_30_days 
                    FROM bg_user_attributes a 
                    WHERE a.user_id = :user_id AND a.create_dt >= CURDATE() - INTERVAL 30 DAY AND a.type="referred"';

    // SQL for confirmed total (excluding 'pending' status)
    $sqlConfirmedTotal = 'SELECT IFNULL(SUM(IFNULL(a.rank,0)),0) as confirmed_total 
                        FROM bg_user_attributes a 
                        WHERE a.user_id = :user_id AND a.status != "pending" AND a.type="referred"';

    // Execute and fetch grand total and distinct user count
    $stmtTotal = $this->db->prepare($sqlTotal);
    $stmtTotal->execute(['user_id' => $user_id]);
    $resultTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);

    // Execute and fetch today's total count
    $stmtToday = $this->db->prepare($sqlToday);
    $stmtToday->execute(['user_id' => $user_id]);
    $resultToday = $stmtToday->fetch(PDO::FETCH_ASSOC);

    // Execute and fetch this week's total count
    $stmtThisWeek = $this->db->prepare($sqlThisWeek);
    $stmtThisWeek->execute(['user_id' => $user_id]);
    $resultThisWeek = $stmtThisWeek->fetch(PDO::FETCH_ASSOC);

    // Execute and fetch this month's total count
    $stmtThisMonth = $this->db->prepare($sqlThisMonth);
    $stmtThisMonth->execute(['user_id' => $user_id]);
    $resultThisMonth = $stmtThisMonth->fetch(PDO::FETCH_ASSOC);

    // Execute and fetch last 30 days total and distinct user count
    $stmtLast30Days = $this->db->prepare($sqlLast30Days);
    $stmtLast30Days->execute(['user_id' => $user_id]);
    $resultLast30Days = $stmtLast30Days->fetch(PDO::FETCH_ASSOC);

    // Execute and fetch confirmed total
    $stmtConfirmedTotal = $this->db->prepare($sqlConfirmedTotal);
    $stmtConfirmedTotal->execute(['user_id' => $user_id]);
    $resultConfirmedTotal = $stmtConfirmedTotal->fetch(PDO::FETCH_ASSOC);

    // Combine results
    $results = [
      'grand_total' => $resultTotal['grand_total'],
      'distinct_user_count_total' => $resultTotal['distinct_user_count_total'],
      'today_total' => $resultToday['today_total'],
      'this_week_total' => $resultThisWeek['this_week_total'],
      'this_month_total' => $resultThisMonth['this_month_total'],
      'last_30_days_total' => $resultLast30Days['last_30_days_total'],
      'distinct_user_count_last_30_days' => $resultLast30Days['distinct_user_count_last_30_days'],
      'confirmed_total' => $resultConfirmedTotal['confirmed_total']
    ];

    return $results;
  }
}
