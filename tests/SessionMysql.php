<?php

require('../vendor/autoload.php');

use php_session\session;

$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);

$cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
$cacheDriver->setMemcached($memcached);

$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host=127.0.0.1;dbname=dev',
    'root',
    ''
);

$session = new php_session\session($db, $cacheDriver, 0, false);

session_set_save_handler($session, true);

$session->startsession();

switch ($_GET['tests']) {
    case 0:
        var_dump($_SESSION);
        break;
    case 1:
        $session->regenerate_id();
        break;
    case 2:
        die("some other stuff");
        break;
}