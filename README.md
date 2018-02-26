# Slim Session DB
Slim Framework에서 DB기반 Session을 위한 Middleware 입니다. PHP의 Session을 DB에 저장하는 예제를 기반으로 사용방법은 rka-slim-session-middleware와 동일하게 구현하였습니다. 

## 사용방법

### 1. Composer 환경설정
본 프로젝트는 Composer Package에 등록되어 있지 않기 때문에, composer.json에 아래와 같이 repositories 환경설정을 추가하여 설치가 필요합니다.

composer.json 예시는 아래와 같습니다.
```
{
"repositories": [
      {
          "type" : "package",
          "package" : {
              "name" : "flexion/slim-session-db",
              "version": "1.0",
              "source": {
                  "url": "https://gitlab.flexion.co.kr/flexion/slim-session-db.git",
                  "type":"git",
                  "reference":"master"
              }
          }
      }
  ],
  "require": {
      "slim/slim": "^3.0",
      "zguillez/slim-mobile-detect": "^1.0",
      "slim/flash": "^0.2.0",
      "flexion/slim-session-db": "1.0"
  }
}

```
- 주의: 본 Repository는 Private이기 때문에, composer install / composer update 시 gitlab.flexion.co.kr에 등록된 ID/PW 입력이 필요합니다.


### 2. 세션 정보를 저장할 DB 생성
아래와 같이 사용할 DB에 세션 정보를 저장할 DB를 생성합니다.
```
CREATE TABLE IF NOT EXISTS `fx_slim_sessions` (
  `id` varchar(32) NOT NULL,
  `access` int(10) unsigned DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
);
```

### 3. index.php에 세션 미들웨어 추가
세션 미들웨어를 사용하기 위해 아래와 같이 index.php에 항목을 추가합니다.
```
require_once 'vendor/autoload.php';

$app->add(new \Fx\SessionMiddleware([
  'name' => 'fxsess',
  'db' => [
    'host'   => getenv('DB_PORT_3306_TCP_ADDR'),
    'dbname' => 'YOUR_DB_NAME'
    'user'   => 'YOUR_DB_USER_NAME',
    'pass'   => getenv('DB_ENV_MYSQL_ROOT_PASSWORD'),
  ]
]));
```

## References
[http://culttt.com/2013/02/04/how-to-save-php-sessions-to-a-database/](http://culttt.com/2013/02/04/how-to-save-php-sessions-to-a-database/)
[https://github.com/akrabat/rka-slim-session-middleware](https://github.com/akrabat/rka-slim-session-middleware)