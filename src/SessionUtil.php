<?php 
namespace Flexion;

/**
 * Session Utility
 * 
 * @author inlee <inlee@flexion.co.kr>
 */
final class SessionUtil {

  /**
   * Session Name. Use for array key.
   *
   * @var string
   */
  private $_sessionName = null;

  /**
   * Constructor
   *
   * @param string $sessionName   Session name that will use for array key.
   *                              Default is 
   */
  public function __construct($sessionName = '__fxsess__') {
    if($sessionName) {
      $this->_sessionName = $sessionName;
    }
  } 

  /**
   * Regenerate session
   */
  public static function regenerate() {
    if(session_status() == PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
  }

  /**
   * Session destory
   */
  public static function destroy() {
    $_SESSION = [];
    session_destroy();
  }
  
  /**
   * Get All Session Values
   *
   * @return arary  User Input Session Values
   */
  public function getAll() {
    $sessionName = $this->_sessionName;
    $ret = null;

    if(is_array($_SESSION)) {
      if(isset($_SESSION[$this->_sessionName])) {
        $ret = $_SESSION[$this->_sessionName];
      }
    }

    return $ret;
  }

  /**
   * Get the session value
   *
   * @param  string $key      A key for session array
   * @param  string $default  Default value if key not exists
   * @return string           Value for key or default param if not key exists
   */
  public function get($key, $default = null) {
    $sessionName = $this->_sessionName;
    $ret = $default;

    if(is_array($_SESSION) && array_key_exists($sessionName, $_SESSION)) {
      if(array_key_exists($key, $_SESSION[$sessionName])) {
        $ret = $_SESSION[$this->_sessionName][$key];
      }
    }

    return $ret;
  }

  /**
   * Set the session value
   *
   * @param string $key     A key for session array
   * @param string $value   A value for a key
   */
  public function set($key, $value) {
    $_SESSION[$this->_sessionName][$key] = $value;
  }

  /**
   * Delete session key & value
   *
   * @param string $key   A key for session key 
   */
  public function delete($key) {
    $sessionName = $this->_sessionName;
    
    if(is_array($_SESSION) && array_key_exists($sessionName, $_SESSION)) {
      if(array_key_exists($key, $_SESSION[$sessionName])) {
        unset($_SESSION[$this->_sessionName][$key]);
        $_SESSION[$this->_sessionName][$key] = null;
      }
    }
  }

  /**
   * Clear All Session Value not destroy session
   */
  public function clearAll() {
    $_SESSION[$this->_sessionName] = [];
  }
}

?>
