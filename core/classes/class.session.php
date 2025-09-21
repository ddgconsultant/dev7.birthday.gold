<?php
// session.php

// Define the session save path one directory level above the web root
$session_path = $dir['base'] . '/../_SESSIONS_';

// Ensure the directory exists
if (!is_dir($session_path)) {
    mkdir($session_path, 0770, true); // Create the directory with 770 permissions
}

// Set the session save path
ini_set('session.save_path', $session_path);

# ##==================================================================================================================================================
# ##==================================================================================================================================================
# ##==================================================================================================================================================
class Session
{
  public function __construct($local_config)
  {
    // Start session if it has not already started
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
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
    // Destroy the session
    session_destroy();
    // Start a new session immediately after destroying the old one
    session_start();
  }
}
