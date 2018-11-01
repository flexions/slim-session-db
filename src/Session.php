<?php 
namespace Flexion;

final class Session {
  
  public static function regenerate() {
    if(session_status() == PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
  }

  public static function destroy() {
    $_SESSION = [];
    session_destroy();
  }

  public function get($key, $default = null) {
    if(array_key_exists($key, $_SESSION)) {
      return $_SESSION[$key];
    }
    return $default;
  }

  public function set($key, $value) {
    $_SESSION[$key] = $value;
  }

  public function delete($key) {
    if(array_key_exists($key, $_SESSION)) {
      unset($_SESSION[$key]);
      $_SESSION[$key] = null;
    }
  }

  public function clearAll() {
    $_SESSION = [];
  }

  public function __set($key, $value) {
    $this->set($key, $value);
  }

  public function __get($key) {
    return $this->get($key);
  }

  public function __isset($key) {
    return array_key_exists($key, $_SESSION);
  }

  public function __unset($key) {
    $this->delete($key);
  }
}

?>
