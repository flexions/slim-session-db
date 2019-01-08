<?php 
namespace Flexion;

/**
 * Session Utility
 */
final class SessionUtil {
  
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
   * Get the session value
   *
   * @param  [string] $key      A key for session array
   * @param  [string] $default  Default value if key not exists
   * @return [string]           Value for key or default param if not key exists
   */
  public function get($key, $default = null) {
    if(is_array($_SESSION) && array_key_exists($key, $_SESSION)) {
      return $_SESSION[$key];
    }
    return $default;
  }

  /**
   * Set the session value
   *
   * @param [string] $key     A key for session array
   * @param [string] $value   A value for a key
   */
  public function set($key, $value) {
    $_SESSION[$key] = $value;
  }

  /**
   * Delete session key & value
   *
   * @param [string] $key   A key for session key 
   */
  public function delete($key) {
    if(is_array($_SESSION) && array_key_exists($key, $_SESSION)) {
      unset($_SESSION[$key]);
      $_SESSION[$key] = null;
    }
  }

  /**
   * Clear All Session Value not destroy session
   */
  public function clearAll() {
    $_SESSION = [];
  }
}

?>
