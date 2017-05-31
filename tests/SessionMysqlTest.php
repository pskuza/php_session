<?php

use PHPUnit\Framework\TestCase;

use php_session\session;
use GuzzleHttp\Client;

class SessionMysqlTest extends TestCase
{
    public function testSessions()
    {
        //run php web server in tests dir
        shell_exec('cd tests && php -S localhost:8000 >/dev/null 2>/dev/null &');

        $client = new GuzzleHttp\Client(['cookies' => true]);

        $r = $client->request('GET', 'http://localhost:8000/SessionMysql.php?tests=0');

        var_dump($r->getHeaders());
    }
}
