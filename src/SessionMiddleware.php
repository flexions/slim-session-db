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

/**
 * DB Session Middleware for Slim Framework
 * 
 * @author inlee <inlee@flexion.co.kr>
 */
final class SessionMiddleware implements \SessionHandlerInterface {

  /**
   * PDO object
   *
   * @var \PDO
   */
  private $_pdo = null;

  /**
   * Session data store table name
   *
   * @var string
   */
  private $_tableName = null;

  /**
   * Session Name
   *
   * @var string
   */
  private $_sessionName = null;

  /**
   * Constructor
   *
   * @param string $host          Database host
   * @param string $dbname        Database name
   * @param string $user          Database user
   * @param string $pass          Database password
   * @param string $tableName     Session data table name
   * @param string $sessionName   Session Name
   */
  public function __construct($host,
                              $dbname,
                              $user,
                              $pass,
                              $tableName,
                              $sessionName) {
    $this->_tableName = $tableName;
    $this->_sessionName = $sessionName;

    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
    $this->_pdo = new \PDO($dsn, $user, $pass);
  }

  /**
   * Middleware invokable class
   *
   * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
   * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
   * @param  callable                                 $next     Next middleware
   * @return \Psr\Http\Message\ResponseInterface                PSR7 response
   */
  public function __invoke($request, $response, $next) {
    // create session table if not exists
    $this->createTable();

    session_set_save_handler($this, true);
    session_name($this->_sessionName);
    session_start();

    return $next($request, $response);
  }

  /**
   * Re-initialize existing session, or creates a new one. 
   * Called when a session starts or when session_start() is invoked.
   *
   * @param string $save_path     The path where to store/retrieve the session.
   * @param string $session_name  The session name.
   * @return bool                 true: success, false: fail
   */
  public function open($save_path, $session_name): bool {
    return $this->_pdo ? true : false;
  }

  /**
   * Reads the session data from the session storage, and returns the results. 
   * Called right after the session starts or when session_start() is called. 
   *
   * @param string $session_id    The session id.
   * @return string               An encoded string of the read data.
   *                              If nothing was read, it must return an
   *                              empty string. 
   */
  public function read($session_id): string {
    try {
      $table = $this->_tableName;
      
      $sql = "SELECT `data` FROM {$table} WHERE id = :id";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':id', $session_id);
      $stmt->execute();
  
      $row = $stmt->fetch();

      $ret = '';
      if($row) {
        $ret = $row['data'];
      }

      return $ret;
    }
    catch(\Exception $e) {
      throw $e;
    }
  }

  /**
   * Writes the session data to the session storage. 
   * Called by session_write_close(), when session_register_shutdown() fails, 
   * or during a normal shutdown. 
   *
   * @param string $session_id    The session id.
   * @param string $session_data  The encoded session data. 
   *                              This data is the result of the PHP internally 
   *                              encoding the $_SESSION superglobal 
   *                              to a serialized string and passing it as this parameter. 
   * @return bool                 true: success, false: fail
   */
  public function write($session_id, $session_data): bool {
    try {
      $access = time();
      $table = $this->_tableName;
      
      $sql = "REPLACE INTO {$table} VALUES (:id, :access, :data)";
      $stmt = $this->_pdo->prepare($sql);
      
      $stmt->bindValue(':id', $session_id);
      $stmt->bindValue(':access', $access);
      $stmt->bindValue(':data', $session_data);
      
      $ret = $stmt->execute();

      return $ret;
    }
    catch(\Exception $e) {
      throw $e;
    }
  }

  /**
   * Closes the current session. 
   * This function is automatically executed when closing the session, 
   * or explicitly via session_write_close().
   *
   * @return bool   true: success, false: fail.
   */
  public function close(): bool {
    $this->_pdo = null;
    return true;
  }

  /**
   * Destroys a session. 
   * Called by session_regenerate_id() (with $destroy = TRUE), 
   * session_destroy() and when session_decode() fails.
   *
   * @param string $session_id  The session ID being destroyed.
   * @return bool               true: success, false: fail.
   */
  public function destroy($session_id): bool {
    try {
      $table = $this->_tableName;
      $sql = "DELETE FROM {$table} WHERE id = :id";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':id', $session_id);
      $stmt->execute();

      $ret = false;
      if($stmt->rowCount()) {
        $ret = true;
      }

      return $ret;
    }
    catch(\Exception $e) {
      throw $e;
    }
  }

  /**
   * Cleans up expired sessions. 
   * Called by session_start(), based on session.gc_divisor, 
   * session.gc_probability and session.gc_maxlifetime settings.
   *
   * @param int $maxlifetime    Sessions that have not updated for the last 
   *                            maxlifetime seconds will be removed.
   * @return int                The return value
   *                            (usually TRUE on success, FALSE on failure).
   *                            Note this value is returned internally
   *                            to PHP for processing.
   */
  public function gc($maxlifetime): int {
    try {
      // Calculate what is to be deemed old
      $old = time() - $maxlifetime;
      $table = $this->_tableName;
      
      $sql = "DELETE FROM {$table} WHERE access < :old";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':old', $old);
      $stmt->execute();
      
      $ret = $stmt->rowCount();
      return $ret;
    }
    catch(\Exception $e) {
      throw $e;
    }
  }

  /**
   * Create table for session data if not exists.
   *
   * @return void
   */
  private function createTable(): void {
    static $sessionTableCreated = false;

    try {
      if (!$sessionTableCreated) {
        $table = $this->_tableName;
        $sql = "CREATE TABLE IF NOT EXISTS ${$table} (
          `id` varchar(32) NOT NULL,
          `access` int(10) unsigned DEFAULT NULL,
          `data` text,
          PRIMARY KEY(`id`)
        );";

        $this->_pdo->exec($sql);
      }

      session_set_save_handler($this, true);
      session_name($this->_sessionName);
      session_start();
    }
    catch (\Exception $e) {
      throw $e;
    }

  }
}
