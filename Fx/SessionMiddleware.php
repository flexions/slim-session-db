<?php 
/**
 * Session for Fx Slim Framework
 * 
 * CREATE TABLE IF NOT EXISTS `fx_slim_sessions` (
 *  `id` varchar(32) NOT NULL,
 *  `access` int(10) unsigned DEFAULT NULL,
 *  `data` text,
 *   PRIMARY KEY (`id`)
 * );
 * 
 * @see http://culttt.com/2013/02/04/how-to-save-php-sessions-to-a-database/
 * @see https://github.com/kahwee/php-db-session-handler
 * @see http://blog.naver.com/PostView.nhn?blogId=archlord8674&logNo=110096701625
 * @see http://php.net/manual/kr/function.session-set-save-handler.php
 */
namespace Fx;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \PDO;


/**
 * b array object의 내용을 a에 깊은 복사(deep copy)를 수행한다.
 *
 * @param Array   $a 복사되는 Array
 * @param Array   $b 복사 대상 Array
 * @return Array  깊은 복사가 완료된 배열 $a
 * @see https://stackoverflow.com/questions/12725113
 */
function __deepExtend($a, $b) {
  foreach($b as $k=>$v) {
    if(is_array($v)) {
      if(!isset($a[$k]))  { $a[$k] = $v;  }
      else                { $a[$k] = __deepExtend($a[$k], $v);  }
    } 
    else {
      $a[$k] = $v;
    }
  }
  return $a;
}

/**
 * DB Session Middleware
 */
final class SessionMiddleware {
  // protected $options = [
  //   'name' => 'Fx',
  //   'lifetime' => 7200,
  //   'path' => null,
  //   'domain' => null,
  //   'secure' => false,
  //   'httponly' => true,
  //   'cache_limiter' => 'nocache',
  // ];

  /**
   * Session Options
   */
  protected $_options = [
    'name'          => 'fxsess', // 세션 이름
    'lifetime'      => 180,      // 세션 유지 시간(단위: minutes)
    'cache_limiter' => 'private', // cache limiter
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
   * 생성자
   *
   * @param array $options    세션 옵션
   */
  public function __construct($options = []) {
    $this->_options = __deepExtend($this->_options, $options);
  }

  /**
   * 소멸자
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
   * 세션을 시작한다.
   */
  private function _start() {
    // 1. database connection 생성
    $host   = $this->_options['db']['host'];
    $user   = $this->_options['db']['user'];
    $pass   = $this->_options['db']['pass'];
    $dbname = $this->_options['db']['dbname'];
   
    $this->_pdo = new \PDO("mysql:host={$host};dbname={$dbname};charset=utf8",
                           $user, $pass);

    // 2. 세션 설정
    // - 세션 expire 시간 설정
    // session_cache_expire($this->_options['lifetime']);
    // - cache limter 설정
    // session_cache_limiter($this->_options['cache_limiter']);

    // - Set handler to overide SESSION
    session_set_save_handler(
      array($this, "_open"),
      array($this, "_close"),
      array($this, "_read"),
      array($this, "_write"),
      array($this, "_destroy"),
      array($this, "_gc")
    );
    
    // - 세션 이름 지정
    if(!is_null($this->_options['name'])) {
      session_name($this->_options['name']);
    }
    
    // - Start the session
    @session_start();
  }

  /**
   * Session Open 함수
   * - 열기 함수, 클래스의 생성자처럼 작동하고 세션이 열릴 때 실행.
   *
   * @return bool   true: Session Open 성공 / false: Session Open 실패
   */
  public function _open() {   
    return $this->_pdo ? true : false;
  }

  /**
   * Session Close 함수
   * - 클래스의 소멸자처럼 작동하고 세션 연산이 끝났을 때 실행.
   * 
   * @return bool   true: close 성공 / false: otherwise
   */
  public function _close() {
    $this->_pdo = null;
    return true;
  }

  /**
   * Session Read 함수
   * - 장 핸들러가 정상적으로 작동하기 위해 항상 문자열 값을 반환. 
   * - 읽을 데이터가 없으면 빈 문자열을 반환합니다. 
   *
   * @param  string   $id   세션의 ID
   * @return string   세션 data 정보. 없을 경우 empty string
   */
  public function _read($id) {
    try {
      $table = $this->_options['db']['table'];
      
      $sql = "SELECT `data` FROM {$table} WHERE id = :id";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':id', $id);
      $stmt->execute();
  
      $row = $stmt->fetch();
      $ret = '';  // empty string
      // row가 존재할 경우, data 정보 반환
      if($row)
        $ret = $row['data'];
      
      return $ret;
    }
    catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  /**
   * Session Write 함수
   * 
   * @param string $id    세션 ID
   * @param object $data  세션 Data
   * @return bool         true: 쓰기 완료, false: 쓰기 실패
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
   * Session Destroy 함수
   * -  session_destroy()로 세션이 파괴될 때 실행되며, 세션 id를 인수로 받음.
   *
   * @param string $id  세션 ID
   * @return bool       true: 성공 / false: 실패
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
   * - 세션 쓰레기 수거가 실행될 때 실행되며, 최대 세션 수명을 인수로 받음
   * 
   * @param  string $max  최대 세션 수명
   * @return bool         true: 성공 / false: 실패
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
}

?>
