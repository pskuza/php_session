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
        //give php some time for the webserver
        sleep(5);
        $client = new GuzzleHttp\Client(['cookies' => true]);

        $random_hex = bin2hex(random_bytes(8));

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysql.php?tests=0&locking=false&random=' . $random_hex);

        $headers = $r->getHeaders();

        //is the session getting created & set works
        $this->assertArrayHaskey('Set-Cookie', $headers, 'Session start failed.');

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysql.php?tests=1&locking=false');

        $headers_new = $r->getHeaders();

        //does regenerate_id work
        $this->assertTrue($headers['Set-Cookie'] !== $headers_new['Set-Cookie'], 'Regenerate id failed.');

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysql.php?tests=2&locking=false');

        //does get work
        $this->assertTrue($random_hex === $r->getBody()->getContents(), 'Session storage did not save or get.');

        $r = $client->request('GET', 'http://127.0.0.1:8080/SessionMysql.php?tests=3&locking=false');

        $headers = $r->getHeaders();

        //does logout work
        $this->assertArrayNotHasKey('Cookie', $headers, 'Logout did not work.');

        $output = shell_exec('bash tests/locked_increment_test.sh');

        $this->assertEquals($output, "20", 'Session locking feature did not lock correctly.');
    }
}
