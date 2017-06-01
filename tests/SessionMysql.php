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
        if (!empty($count = $session->get('increment'))) {
            $session->set(['increment' => $count++], true);
        } else {
            $session->set(['increment' => 0], true);
        }
        break;
    case 5:
        echo $session->get('increment');
        break;
}