<?php

declare(strict_types=1);

namespace php_session;

use SessionHandler;

class session extends SessionHandler
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier = 'php_session_';

    protected $cachetime;

    protected $secure = true;

    protected $csrf_random_bytes_count = 32;

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, \Doctrine\Common\Cache\CacheProvider $session_cache, int $cachetime = 3600, bool $secure = null)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

        $this->cachetime = $cachetime;

        if (!is_null($secure)) {
            $this->secure = $secure;
        }
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
            if ($data = $this->db->cell('SELECT data FROM sessions WHERE id = ?', $id)) {
                $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);

                return $data;
            } else {
                //return session name for session_regenerate_id
                return session_name();
            }
        }
    }

    private function _parseremember_me(string $data) : int
    {
        return (int) ((bool) strpos($data, 'php_session_remember_me|i:1'));
    }

    public function write($id, $data) : bool
    {
        //check if cached
        if ($this->session_cache->contains($this->session_cache_identifier.$id)) {
            $data_cache = $this->session_cache->fetch($this->session_cache_identifier.$id);
            if ($data_cache !== $data) {
                //update
                $remember_me = $this->_parseremember_me($data);
                $this->db->update('sessions', ['data' => $data, 'remember_me' => $remember_me], ['id' => $id]);

                return $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);
            }
        } else {
            //try reading from db
            $remember_me = $this->_parseremember_me($data);
            if ($data_cache = $this->db->cell('SELECT data FROM sessions WHERE id = ?', $id)) {
                if ($data_cache !== $data) {
                    //update
                    $this->db->update('sessions', ['data' => $data, 'remember_me' => $remember_me], ['id' => $id]);

                    return $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);
                }
            } else {
                //not in cache and not in db (first write)
                $this->db->insert('sessions', [
                    'id'          => $id,
                    'data'        => $data,
                    'timestamp'   => time(),
                    'remember_me' => $remember_me,
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

    public function gc($max) : bool
    {
        $rows = $this->db->run('SELECT id FROM sessions WHERE timestamp < ? AND remember_me = 0', time() - intval($max));
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

    public function set(array $options, bool $lock_session = false) : bool
    {
        $id = session_id();
        if ($lock_session) {
            //lock the session for any reads or writes until this operation is done
            if (!$this->session_locking) {
                throw new Exception('Class was not initiated with session_locking as true.');
            } else {
                //lock for session_lock_time seconds
                $this->session_cache->save($this->session_cache_identifier.$id.'_lock', true, $this->session_lock_time);
                foreach ($options as $k => $v) {
                    $_SESSION[$k] = $v;
                }

                return $this->session_cache->delete($this->session_cache_identifier.$id.'_lock');
            }
        } else {
            //dont lock
            foreach ($options as $k => $v) {
                $_SESSION[$k] = $v;
            }

            return true;
        }
    }

    public function get(string $value)
    {
        if (array_key_exists($value, $_SESSION)) {
            return $_SESSION[$value];
        }

        return false;
    }

    public function remember_me(bool $enabled) : bool
    {
        return $this->set(['php_session_remember_me' => (int) $enabled]);
    }

    public function logout() : bool
    {
        session_unset();
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );

        return session_destroy();
    }

    public function generate_csrf() : bool
    {
        return $this->set(['php_session_csrf' => base64_encode(random_bytes($this->csrf_random_bytes_count))]);
    }

    public function check_csrf(string $token) : bool
    {
        //we should also check for verify that the request is same origin
        return hash_equals($this->get('php_session_csrf'), $token);
    }
}
