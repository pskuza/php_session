<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../vendor/autoload.php';

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$cacheDriver = new \Doctrine\Common\Cache\RedisCache();
$cacheDriver->setRedis($redis);

$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host=localhost;dbname=dev_redis',
    'root',
    ''
);

$session = new php_session\session($db, $cacheDriver, 'test', 0, false);

session_set_save_handler($session, true);

$session->start();

switch ($_GET['tests']) {
    case 0:
        $session->set('tests', $_GET['random']);
        break;
    case 1:
        $session->regenerate_id();
        break;
    case 2:
        echo $session->get('tests');
        break;
    case 3:
        $session->logout();
        break;
    case 4:
        //dead
        break;
    case 5:
        //dead
        break;
    case 6:
        $session->remember_me(true);
        break;
    case 7:
        echo $db->cell('SELECT remember_me FROM sessions WHERE id = ?', session_id());
        break;
    case 8:
        $session->remember_me(false);
        break;
}
