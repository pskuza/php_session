<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../vendor/autoload.php';

$memcached = new Memcached();
$memcached->setOption(Memcached::OPT_COMPRESSION, false);
$memcached->addServer('127.0.0.1', 11211);

$cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
$cacheDriver->setMemcached($memcached);

$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host=localhost;dbname=dev_memcached',
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
        //force gc, should delete all sessions in db, who don't have remember_me === 1
        ini_set('session.gc_maxlifetime', '0')
        session_gc();
        break;
    case 5:
        //dead
        echo $db->cell('SELECT COUNT(*) FROM sessions');
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
