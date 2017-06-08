<?php

use php_session\session;
use PHPUnit\Framework\TestCase;

class SessionMysqlRedisTest extends TestCase
{
    public function testSessions()
    {
        //run php web server in tests dir
        shell_exec('cd tests && php -S 127.0.0.1:8080 >/dev/null 2>/dev/null &');
        //give php some time for the webserver
        sleep(5);
        $client = new GuzzleHttp\Client(['cookies' => true]);

        $random_hex = bin2hex(random_bytes(8));

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=0&locking=false&random='.$random_hex);

        $headers = $r->getHeaders();

        //is the session getting created & set works
        $this->assertArrayHaskey('Set-Cookie', $headers, 'Session start failed.');

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=1&locking=false');

        $headers_new = $r->getHeaders();

        //does regenerate_id work
        $this->assertTrue($headers['Set-Cookie'] !== $headers_new['Set-Cookie'], 'Regenerate id failed.');

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=2&locking=false');

        //does get work
        $this->assertTrue($random_hex === $r->getBody()->getContents(), 'Session storage did not save or get.');

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=3&locking=false');

        $headers = $r->getHeaders();

        //does logout work
        $this->assertArrayNotHasKey('Cookie', $headers, 'Logout did not work.');

        //does locking work
        $output = shell_exec('bash tests/locked_increment_test_redis.sh');
        $this->assertEquals('20', $output, 'Session locking feature did not lock correctly.');

        //does remember_me work
        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=6&locking=false');
        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=7&locking=false');

        $this->assertEquals('1', $r->getBody()->getContents(), 'Remember me was not set to 1 in DB.');

        //does now setting remember me to 0 work
        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=8&locking=false');
        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysqlRedis.php?tests=7&locking=false');

        $this->assertEquals('0', $r->getBody()->getContents(), 'Remember me was not set to 0 in DB.');
    }
}
