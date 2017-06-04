<?php

//declare(strict_types=1);

namespace php_session;

use SessionHandler;

class session extends SessionHandler
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier = 'php_session_';

    protected $cachetime;

    protected $secure = true;

    protected $session_locking = false;

    protected $session_lock_time = 3;

    protected $csrf_random_bytes_count = 32;

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, $session_cache, int $cachetime = 3600, bool $secure = null, bool $session_locking = null)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

        $this->cachetime = $cachetime;

        if (!is_null($secure)) {
            $this->secure = $secure;
        }

        //need to be true for $this->set(['test' => 1], true)
        if (!is_null($session_locking)) {
            $this->session_locking = $session_locking;
        }
    }

    public function open($save_path = null, $id = null)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $this->waitforlock($id);
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

        return false;
    }

    public function waitforlock($id)
    {
        //check if we have locking enabled
        if ($this->session_locking) {
            //check if the session is locked
            if ($this->session_cache->fetch($this->session_cache_identifier.$id.'_lock')) {
                //session is locked and something is writing to it, wait till release or session_lock_time
                $i_t = 0;
                while ($this->session_cache->fetch($this->session_cache_identifier.$id.'_lock') || $i_t >= $this->session_lock_time) {
                    //break out once we reached $session_lock_time
                    sleep(0.1);
                    $i_t = $i_t + 0.1;
                }
            }
        }
    }

    public function parseremember_me($data)
    {
        return (int)((bool)strpos($data, 'php_session_remember_me|i:1'));
    }

    public function write($id, $data)
    {
        $this->waitforlock($id);
        //check if cached
        if ($this->session_cache->contains($this->session_cache_identifier.$id)) {
            $data_cache = $this->session_cache->fetch($this->session_cache_identifier.$id);
            if ($data_cache !== $data) {
                //update
                $remember_me = $this->parseremember_me($data);
                $this->db->update('sessions', ['data' => $data, 'remember_me' => $remember_me], ['id' => $id]);

                return $this->session_cache->save($this->session_cache_identifier.$id, $data, $this->cachetime);
            }
        } else {
            //try reading from db
            $remember_me = $this->parseremember_me($data);
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

    public function destroy($id)
    {
        $this->db->delete('sessions', ['id' => $id]);

        return $this->session_cache->delete($this->session_cache_identifier.$id);
    }

    public function gc($max)
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

    public function create_sid()
    {
        return base64_encode(random_bytes(48));
    }

    public function start(int $lifetime = null, string $path = null, string $domain = null)
    {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params($cookieParams['lifetime'], '/', $cookieParams['domain'], $this->secure, true);
        session_name('id');

        return session_start();
    }

    public function regenerate_id()
    {
        return session_regenerate_id(true);
    }

    public function set(array $options, bool $lock_session = false)
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
                return $this->session_cache->delete($this->session_cache_identifier . $id . '_lock');
            }
        } else {
            //dont lock
            foreach ($options as $k => $v) {
                $_SESSION[$k] = $v;
            }

            return true;
        }

        return false;
    }

    public function get($value = null)
    {
        if (!is_null($value)) {
            if (array_key_exists($value, $_SESSION)) {
                return $_SESSION[$value];
            }

            return false;
        }

        return $_SESSION;
    }

    public function remember_me(bool $enabled)
    {
        return $this->set(['php_session_remember_me' => (int)$enabled]);
    }

    public function logout()
    {
        session_unset();
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );

        return session_destroy();
    }

    public function generate_csrf()
    {
        return $this->set(['php_session_csrf' => base64_encode(random_bytes($this->csrf_random_bytes_count))]);
    }

    public function check_csrf(string $token)
    {
        //we should also check for verify that the request is same origin
        return hash_equals($this->get('php_session_csrf'), $token);
    }
}
