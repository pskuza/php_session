<?php

use PHPUnit\Framework\TestCase;

use php_session\session;

class SessionMysqlTest extends TestCase
{
    public function testSessions()
    {
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


    }
}
