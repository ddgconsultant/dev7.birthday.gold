<?php
// database.php 

class Database
{

  private $pdo;
  private $lastQuery = null;
  private $lastParams = [];

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
      $this->lastQuery = $sql;
      $this->lastParams = $params;
      try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
      } catch (PDOException $e) {
        // Log query details with the error
        error_log("Database Query Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
        throw $e;
      }
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
    $this->lastQuery = $sql;
    try {
      return $this->pdo->prepare($sql);
    } catch (PDOException $e) {
      error_log("Database Prepare Error: " . $e->getMessage() . " | Query: " . $sql);
      throw $e;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function count($table, $where = '', $params = [])
  {
    // Construct query
    $sql = "SELECT COUNT(*) FROM $table";
    if ($where !== '') {
      $sql .= " WHERE $where";
    }
    $this->lastQuery = $sql;
    $this->lastParams = $params;
    try {
      // Prepare and execute
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);
      // Return count
      return $stmt->fetchColumn();
    } catch (PDOException $e) {
      error_log("Database Count Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
      throw $e;
    }
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
    $this->lastQuery = $sql;
    $this->lastParams = $params;
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database FetchOne Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
      throw $e;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function fetch($sql, $params = [])
  {
    $this->lastParams = $params;
    try {
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
    } catch (PDOException $e) {
      error_log("Database Fetch Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
      throw $e;
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getrows($sql, $params = [])
  {
    $this->lastParams = $params;
    try {
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
    } catch (PDOException $e) {
      error_log("Database GetRows Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($params));
      throw $e;
    }
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

    $this->lastQuery = $sql;
    $this->lastParams = array_merge($data, $where);

    try {
      $stmt = $this->pdo->prepare($sql);

      // bind data values
      foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
      }

      // bind where clause value
      $stmt->bindValue(":" . key($where), reset($where));

      return $stmt->execute();
    } catch (PDOException $e) {
      error_log("Database Update Error: " . $e->getMessage() . " | Query: " . $sql . " | Params: " . json_encode($this->lastParams));
      throw $e;
    }
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getLastQuery()
  {
    return $this->lastQuery;
  }

  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function getLastParams()
  {
    return $this->lastParams;
  }
}
