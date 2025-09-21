<?php
// database.php 

class Database
{

  private $pdo;

  public function __construct($local_config)
  {

    $dsn = "mysql:host={$local_config['DB_HOST']};dbname={$local_config['DB_DATABASE']}";

    $username = $local_config['DB_USERNAME'];
    $password = $local_config['DB_PASSWORD'];

    $this->pdo = new PDO($dsn, $username, $password, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function beginTransaction()
  {
    $this->pdo->beginTransaction();
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function commit()
  {
    $this->pdo->commit();
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function rollback()
  {
    $this->pdo->rollback();
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function query($sql, $params = [])
  {
    if (!empty($sql)) {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);
      return $stmt;
    } else {
      return false;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function exists($table, $where)
  {
    // check if row exists
  }

  private $fetchMode;


  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function prepare($sql)
  {
    if (empty($sql)) {  // If no query is passed
      return false;
    }
    return $this->pdo->prepare($sql);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function count($table, $where = '', $params = [])
  {
    // Construct query
    $sql = "SELECT COUNT(*) FROM $table";
    if ($where !== '') {
      $sql .= " WHERE $where";
    }
    // Prepare and execute
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    // Return count 
    return $stmt->fetchColumn();
  }

# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function errorInfo($stmt = null)
{
    // If statement provided, get its error info
    if ($stmt !== null) {
        $error = $stmt->errorInfo();
    }
    // Otherwise get connection error info
    else {
        $error = $this->pdo->errorInfo();
    }
    
    return [
        'code' => $error[0],
        'driver_code' => $error[1],
        'message' => $error[2]
    ];
}

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function fetchOne($sql, $params = [])
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function fetch($sql, $params = [])
  {
    // Prepare statement
    $stmt = $this->prepare($sql);
    // Bind parameters 
    if (!empty($params)) {
      foreach ($params as $param => $value) {
        $stmt->bindValue(":$param", $value);
      }
    }
    $stmt->execute();
    $results = $stmt->fetch(PDO::FETCH_ASSOC);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getrows($sql, $params = [])
  {
    // Prepare statement
    $stmt = $this->prepare($sql);
    // Bind parameters 
    if (!empty($params)) {
      foreach ($params as $param => $value) {
        // Remove colon if it's already there
        $param = ltrim($param, ':');
        $stmt->bindValue(":$param", $value);
      }
    }
    $stmt->execute();
    // Fetch all rows
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getrow($sql, $params = [])
  {
    // Alias for fetchOne for consistency
    return $this->fetchOne($sql, $params);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function lastInsertId()
  {
    return $this->pdo->lastInsertId();
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function update($table, $data, $where)
  {
    $fields = array_keys($data);
    $sql = "UPDATE $table SET " . implode(', ', array_map(function ($field) {
      return "$field = :$field";
    }, $fields)) . " WHERE " . key($where) . " = :" . key($where) . " LIMIT 1";

    $stmt = $this->pdo->prepare($sql);

    // bind data values
    foreach ($data as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    // bind where clause value
    $stmt->bindValue(":" . key($where), reset($where));

    return $stmt->execute();
  }
}
