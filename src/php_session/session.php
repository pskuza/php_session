<?php

declare(strict_types=1);

namespace php_session;

use SessionHandler;

class session extends SessionHandler
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier;

    protected $cachetime;

    protected $secure;

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, \Doctrine\Common\Cache\CacheProvider $session_cache, string $session_cache_identifier = 'php_session_', int $cachetime = 3600, bool $secure = true)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

        $this->cachetime = $cachetime;

        $this->secure = $secure;

        $this->session_cache_identifier = $session_cache_identifier;
    }

    public function open($save_path, $id) : bool
    {
        return true;
    }

    public function close() : bool
    {
        return true;
    }

    public function read($id) : string
    {
        //use cache
        if ($this->session_cache->contains($this->session_cache_identifier.$id)) {
            return $this->session_cache->fetch($this->session_cache_identifier.$id);
        } else {
            //try reading from db
            if ($data = $this->db->cell('SELECT session_data FROM sessions WHERE id = ?', $id)) {
                $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);

                return $data;
            }
        }

        return session_name();
    }

    public function write($id, $data) : bool
    {
        //check if cached
        if ($this->session_cache->contains($this->session_cache_identifier.$id)) {
            $data_cache = $this->session_cache->fetch($this->session_cache_identifier.$id);
            if ($data_cache !== $data) {
                //update
                $this->db->update('sessions', ['session_data' => $data, ['id' => $id]);

                return $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);
            }
        } else {
            //try reading from db
            if ($data_cache = $this->db->cell('SELECT session_data FROM sessions WHERE id = ?', $id)) {
                if ($data_cache !== $data) {
                    //update
                    $this->db->update('sessions', ['session_data' => $data, ['id' => $id]);

                    return $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);
                }
            } else {
                //not in cache and not in db (first write)
                $this->db->insert('sessions', [
                    'id'                  => $id,
                    'session_data'        => $data,
                ]);

                return $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);
            }
        }

        return true;
    }

    public function destroy($id) : bool
    {
        $this->db->delete('sessions', ['id' => $id]);

        return $this->session_cache->delete($this->session_cache_identifier.$id);
    }

    public function gc($maxlifetime) : bool
    {
        $rows = $this->db->run('SELECT id FROM sessions WHERE created_at < ADDDATE(NOW(), INTERVAL -? SECOND) AND remember_me = 0', $maxlifetime);
        $this->db->beginTransaction();
        foreach ($rows as $row) {
            //delete from cache and db
            $this->session_cache->delete($this->session_cache_identifier.$row['id']);
            $this->db->delete('sessions', [
                'id' => $row['id'],
            ]);
        }
        $this->db->commit();

        return true;
    }

    public function create_sid() : string
    {
        return base64_encode(random_bytes(48));
    }

    public function start(int $lifetime = null, string $path = null, string $domain = null) : bool
    {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params($cookieParams['lifetime'], '/', $cookieParams['domain'], $this->secure, true);
        session_name('id');

        return session_start();
    }

    public function regenerate_id()
    {
        session_regenerate_id(true);

        return session_write_close();
    }

    public function set(string $key, $value) : bool
    {
        $_SESSION[$key] = $value;
        if ($this->get($key) === $value) {
            return true;
        }

        return false;
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }

        return false;
    }

    public function remember_me(bool $enabled) : bool
    {
        return $this->db->update('sessions', ['remember_me' => (int) $enabled], ['id' => session_id()]);
    }

    public function logout() : bool
    {
        session_unset();
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        return session_destroy();
    }
}
