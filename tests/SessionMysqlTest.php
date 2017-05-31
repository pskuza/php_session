<?php

use PHPUnit\Framework\TestCase;

use php_session\session;
use GuzzleHttp\Client;

class SessionMysqlTest extends TestCase
{
    public function testSessions()
    {
        //run php web server in tests dir
        shell_exec('cd tests && php -S 127.0.0.1:8080 >/dev/null 2>/dev/null &');

        $client = new GuzzleHttp\Client(['cookies' => true]);

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysql.php?tests=0');

        $headers = $r->getHeaders();

        $this->assertArrayHaskey('Set-Cookie', $headers);

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysql.php?tests=1');

        $headers_new = $r->getHeaders();

        $this->assertTrue($headers['Set-Cookie'] !== $headers_new['Set-Cookie']);

    }
}
