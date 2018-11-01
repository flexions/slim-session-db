# flexions\slim-session-db

Simple Middleware for Slim Framework that session with Database.

## Create a database table

Create a database table first for storing session data.

```
CREATE TABLE IF NOT EXISTS `fx_slim_sessions` (
  `id` varchar(32) NOT NULL,
  `access` int(10) unsigned DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
);
```

## Installation

It's recommended that you use composer to install this package.

```
$ composer require flexions/slim-session-db
```

## Usage

Create an index.php file with the following contents:

```
<?php
require 'vendor/autoload.php';

$app = new Slim\App();
$container = $app->getContainer();

$app->add(new \Flexion\SessionMiddleware([
  'name' => ${YOUR_SESSION_NAME},                 // default: __fxsess
  'db' => [                                       
    'host'   => ${YOUR_DATABASE_HOST},            
    'user'   => ${YOUR_DATABASE_ID},              
    'pass'   => ${YOUR_DATABASE_PASSWORD},        
    'dbname' => ${YOUR_DATABASE_DB_NAME},         
    'table'  => ${YOUR_SESSION_STORE_TABLE_NAME}  // default: fx_slim_sessions
  ]
]));

// dependency injection of session module
$container['session'] = function($c) {
  return new \Flexion\SessionUtil();
};

```

### Log In

You can set session below when user login.

```
$app->post('/login', function($request, $response, $args) {
  // blahblahblah ...

  // Set session values
  $this->session->set('signed', true);
  $this->session->set('nickname', 'JohnDoe');

  // Set session values
  $signed = $this->session->get('signed');
  $nickname = $this->session->get('nickname');

  return $responst->withRedirect('/');
});

```

### Log Out

You can destroy below when user logout.

```
$app->post('/logout', function($request, $response, $args) {
  // blahblahblah ...

  // Destroy session
  \Flexion\SessionUtil::destroy();
  return $res->withRedirect('/');
});

```

## Methods

### regenerate()

Update the current session id with a newly generated one.

```
\Flexion\SessionUtil::regenerate()
```

### destroy()

Destroy the current session.

```
\Flexion\SessionUtil::destroy()
```

### get(), set()

Get or Set the session value like below.

```
$this->session->set('nickname', 'JohnDoe');
$this->session->get('nickname'); // return JohnDoe
```

### delete()

Delete the session value like below.

```
$this->session->delete('nickname');
```

### clearAll()

Clear all current session key and values not destroy the session.

```
$this->session->clearAll();
```

## License

MIT
