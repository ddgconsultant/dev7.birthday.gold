<?php
// session.php

// Define the session save path one directory level above the web root
$session_path = $dir['base'] . '/../_SESSIONS_';

// Ensure the directory exists
if (!is_dir($session_path)) {
    mkdir($session_path, 0770, true);
}

// Resolve to real path (needed for Ubuntu 24.04)
$session_path = realpath($session_path);

// Set the session save path (only if headers not sent)
if (!headers_sent()) {
    ini_set('session.save_path', $session_path);
}

# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class Session
{
  public function __construct($local_config)
  {
    // Start session if it has not already started, with retry for Windows file locking issues
    if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
      $retries = 3;
      $started = false;
      while ($retries > 0 && !$started) {
        $started = @session_start();
        if (!$started) {
          $retries--;
          if ($retries > 0) usleep(50000); // 50ms delay between retries
        }
      }
    }
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function unset($key)
  {
    // Set a value into the session
    if (!empty($_SESSION[$key])) unset($_SESSION[$key]);
    return;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function seta($key, $position, $value)
  {
    // Set a value into the session
    $_SESSION[$key[$position]] = $value;
    return $value;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function set($key, $value)
  {
    // Set a value into the session
    $_SESSION[$key] = $value;
    return $value;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function get($key, $default = null, $set = false)
  {
    // Get a value from the session
    if (isset($_SESSION[$key]))
      $output = $_SESSION[$key];
    else {
      $output = $default;
      if (!empty($set)) $this->set($key, $output);
    }

    if (strpos($key, 'pagemessage-') !== false) $this->unset($key);
    if (strpos($key, 'pageid-') !== false) $this->unset($key);
    if (strpos($key, 'pageurl-') !== false) $this->unset($key);
    return $output;
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function exists($key)
  {
    // Check if a key exists in the session
    return isset($_SESSION[$key]);
  }



  # ##--------------------------------------------------------------------------------------------------------------------------------------------------
  public function destroy()
  {
    // Clear all session variables first
    $_SESSION = array();

    // Delete the session cookie if it exists
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
      );
    }

    // Destroy the session
    session_destroy();

    // Ensure session save path is set before starting new session
    global $dir;
    $session_path = realpath($dir['base'] . '/../_SESSIONS_');
    if ($session_path && !headers_sent()) {
      ini_set('session.save_path', $session_path);
    }

    // Start a new session with retry for Windows file locking issues
    if (!headers_sent()) {
      $retries = 3;
      $started = false;
      while ($retries > 0 && !$started) {
        $started = @session_start();
        if (!$started) {
          $retries--;
          if ($retries > 0) usleep(50000); // 50ms delay between retries
        }
      }
    }
  }
}
