#php_session

[![Build Status](https://travis-ci.org/pskuza/php_session.svg?branch=master)](https://travis-ci.org/pskuza/php_session)

* Uses PDO for the session database either Mysql, Postgres, Sqlite. (https://github.com/paragonie/easydb) 
* Caches everything in either APC, Memcache, Memcached, Xcache or Redis. (https://github.com/doctrine/cache)
* Session locking, if you want to guarantee session data consistency (A counter that does not revert with multiple async requests). 
* Remember me future for sessions that do not expire. 
* 48 bytes of entropy for the session id.


## Install

``` sh
php composer.phar require "pskuza/php_session"
```

### Basic usage and what works
``` php
<?php

require('vendor/autoload.php');

use php_session\session;

//for memcached as cache
//check doctrine/cache on how to use the others
$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);
$cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
$cacheDriver->setMemcached($memcached);

//for mysql session storage
//check pdo for other connection handlers
$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host=127.0.0.1;dbname=notdev',
    'notroot',
    'averysecurerandompassword'
);

$session = new php_session\session($db, $cacheDriver);

session_set_save_handler($session, true);

//we have a valid session
$session->start();

//write someting to it
$session->set(['somesessiondata' => 'test']);

//print it
var_dump($_SESSION);

//regenrate session id
//you should do this when the user privilege changes (not logged in => logged in or otherwise)
$session->regenerate_id();

//terminate the session (logout)
$session->logout();

```
