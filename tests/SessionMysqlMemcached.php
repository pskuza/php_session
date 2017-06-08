<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../vendor/autoload.php';

use php_session\session;

$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);

$cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
$cacheDriver->setMemcached($memcached);

$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host=localhost;dbname=dev_memcached',
    'root',
    ''
);

switch ($_GET['locking']) {
    case 'true':
        $session = new php_session\session($db, $cacheDriver, 0, false, true);
        break;
    case 'false':
        $session = new php_session\session($db, $cacheDriver, 0, false);
        break;
}

session_set_save_handler($session, true);

$session->start();
$session->generate_csrf();

switch ($_GET['tests']) {
    case 0:
        $session->set(['tests' => $_GET['random']]);
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
        //increment a session variable
        $count = $session->get('increment');
        if (empty($count)) {
            $session->set(['increment' => 1], true);
        } else {
            $count++;
            $session->set(['increment' => $count], true);
        }
        break;
    case 5:
        echo $session->get('increment');
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
