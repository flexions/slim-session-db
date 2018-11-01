<?php 
/**
 * Database Session for Slim Framework
 * 
 * - Session table schema
 * -----------------------------------------------------------------------------
 * CREATE TABLE IF NOT EXISTS `fx_slim_sessions` (
 *  `id` varchar(32) NOT NULL,
 *  `access` int(10) unsigned DEFAULT NULL,
 *  `data` text,
 *   PRIMARY KEY (`id`)
 * );
 * -----------------------------------------------------------------------------
 */
namespace Flexion;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \PDO;

/**
 * DB Session Middleware for Slim Framework
 */
final class SessionMiddleware {

  /**
   * Session Options
   */
  protected $_options = [
    'name'          => '__fxsess',  // Session name
    'lifetime'      => 180,         // Session life time for minutes
    'cache_limiter' => 'private',   // cache limiter
    'path' => null,
    'domain' => null,
    'secure' => false,
    'httponly' => true,
    // database
    'db' => [
      'host'   => null,   // host
      'user'   => null,   // user
      'pass'   => null,   // password
      'dbname' => null,   // dbname
      'table'  => 'fx_slim_sessions' // default table name
    ]
  ];

  /**
   * PDO Object
   */
  private $_pdo;

  /**
   * constructor
   *
   * @param array $options    session option
   */
  public function __construct($options = []) {
    $this->_options = $this->_deepExtend($this->_options, $options);
  }

  /**
   * destructor
   */
  public function __destruct() {
    @session_write_close();
  }


  /**
   * Invoke middleware
   *
   * @param  RequestInterface  $request  PSR7 request object
   * @param  ResponseInterface $response PSR7 response object
   * @param  callable          $next     Next middleware callable
   *
   * @return ResponseInterface PSR7 response object
   * 
   * @see https://www.slimframework.com/docs/concepts/middleware.html
   */
  public function __invoke(RequestInterface $request, 
                           ResponseInterface $response, 
                           callable $next) {
    $this->_start();
    return $next($request, $response);
  }

  /**
   * Start a session
   */
  private function _start() {
    // 1. Create database connection
    $host   = $this->_options['db']['host'];
    $user   = $this->_options['db']['user'];
    $pass   = $this->_options['db']['pass'];
    $dbname = $this->_options['db']['dbname'];
   
    $this->_pdo = new \PDO("mysql:host={$host};dbname={$dbname};charset=utf8",
                           $user, $pass);

    // 2. Configure Session
    // - Set session expire time
    session_cache_expire($this->_options['lifetime']);
    // - Set cache limter
    session_cache_limiter($this->_options['cache_limiter']);

    // - Set handler to overide SESSION
    session_set_save_handler(
      array($this, "_open"),
      array($this, "_close"),
      array($this, "_read"),
      array($this, "_write"),
      array($this, "_destroy"),
      array($this, "_gc")
    );
    
    // - Set session name
    if(!is_null($this->_options['name'])) {
      session_name($this->_options['name']);
    }
    
    // - Start the session
    @session_start();
  }

  /**
   * Session open
  *
   * @return bool   true: success of session Open / false: otherwise
   */
  public function _open() {   
    return $this->_pdo ? true : false;
  }

  /**
   * Session close
   * 
   * @return bool   true: success of session close / false: otherwise
   */
  public function _close() {
    $this->_pdo = null;
    return true;
  }

  /**
   * Session read 
   *
   * @param  [string] $id   session ID
   * @return [string]       Session data information. 
   *                        - If empty, return empty string
   */
  public function _read($id) {
    try {
      $table = $this->_options['db']['table'];
      
      $sql = "SELECT `data` FROM {$table} WHERE id = :id";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':id', $id);
      $stmt->execute();
  
      $row = $stmt->fetch();

      $ret = '';
      if($row) {
        $ret = $row['data'];
      }
      
      return $ret;
    }
    catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  /**
   * Session write
   * 
   * @param  [string] $id    Session ID
   * @param  [object] $data  Session Data
   * @return [bool]          true: Success of Session Write / false: otherwise
   */
  public function _write($id, $data) {
    try {
      $access = time(); // Create time stamp
      $table = $this->_options['db']['table'];
      
      $sql = "REPLACE INTO {$table} VALUES (:id, :access, :data)";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':id',     $id);
      $stmt->bindValue(':access', $access);
      $stmt->bindValue(':data',   $data);
      $stmt->execute();

      $lastInsertId = $this->_pdo->lastInsertId();

      $ret = false;
      if($lastInsertId)
        $ret = true;

      return $ret;
    }
    catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  /**
   * Session destroy 
   * 
   * @param  [string] $id  Session ID
   * @return [bool]        true: Success of session destory / false: otherwise
   */
  public function _destroy($id) {
    try {
      $table = $this->_options['db']['table'];
      $sql = "DELETE FROM {$table} WHERE id = :id";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':id', $id);
      $stmt->execute();

      $ret = false;
      if($stmt->rowCount())
        $ret = true;

      return $ret;
    }
    catch(Exception $e) {
      echo $e->getMessage();
      exit;
    }
  }

  /**
   * Garbage Collection
   * 
   * @param  [string] $max  maximum session lifetime
   * @return [bool]         true: Success of gc / false: otherwise
   */
  public function _gc($max) {
    try {
      // Calculate what is to be deemed old
      $old = time() - $max;
      $table = $this->_options['db']['table'];
      
      $sql = "DELETE FROM {$table} WHERE access < :old";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':old', $old);
      $stmt->execute();
      
      $ret = false;
      if($stmt->rowCount())
        $ret = true;

      return $ret;
    }
    catch(Exception $e) {
      echo $e->getMessage();
    }
  }


  /**
   * Deep copy from b array to a array
   *
   * @param  [Array]  $a  Copy target array
   * @param  [Array]  $b  Copy source array
   * @return [Array]  Array of combined a with b
   */
  private function _deepExtend($a, $b) {
    foreach($b as $k=>$v) {
      if(is_array($v)) {
        if(!isset($a[$k])) { 
          $a[$k] = $v;
        }
        else { 
          $a[$k] = $this->_deepExtend($a[$k], $v);
        }
      } 
      else {
        $a[$k] = $v;
      }
    }
    return $a;
  }
}

?>
