<?php
//declare(strict_types=1);

namespace php_session;

use SessionHandler;
use AESGCM\AESGCM;

class session extends SessionHandler
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier = "php_session_";

    protected $cachetime;

    protected $secure = true;

    protected $encryption = false;

    protected $encryption_key = null;

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, $session_cache, int $cachetime = 3600, bool $secure = null, bool $encryption = null, string $encryption_key = null)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

        $this->cachetime = $cachetime;

        if (!is_null($secure)) {
            $this->secure = $secure;
        }

        if (!is_null($encryption)) {
            //check key ...
            $this->encryption = $encryption;
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
        //use cache
        if ($this->session_cache->contains($this->session_cache_identifier . $id)) {
            return $this->session_cache->fetch($this->session_cache_identifier . $id);
        } else {
            //try reading from db
            if ($data = $this->db->cell("SELECT data FROM sessions WHERE id = ?", $id)) {
                $this->session_cache->save($this->session_cache_identifier . $id, $data, $this->cachetime);
                return $data;
            } else {
                //return session name for session_regenerate_id
                return session_name();
            }
        }
        return false;
    }

    public function write($id, $data)
    {
        //check if cached
        if ($this->session_cache->contains($this->session_cache_identifier . $id)) {
            $data_cache = $this->session_cache->fetch($this->session_cache_identifier . $id);
            if (!$this->equalstrings($data_cache, $data)) {
                //update
                $this->db->update('sessions', ['data' => $data], ['id' => $id]);
                return $this->session_cache->save($this->session_cache_identifier . $id, $data, $this->cachetime);
            }
        } else {
            //try reading from db
            if ($data_cache = $this->db->cell("SELECT data FROM sessions WHERE id = ?", $id)) {
                if (!$this->equalstrings($data_cache, $data)) {
                    //update
                    $this->db->update('sessions', ['data' => $data], ['id' => $id]);
                    return $this->session_cache->save($this->session_cache_identifier . $id, $data, $this->cachetime);
                }
            } else {
                //not in cache and not in db (first write)
                $this->db->insert('sessions', [
                    'id' => $id,
                    'data' => $data,
                    'timestamp' => time()
                ]);
                return $this->session_cache->save($this->session_cache_identifier . $id, $data, $this->cachetime);
            }
        }

        return true;
    }

    public function equalstrings(string $olddata, string $newdata)
    {
        return $olddata === $newdata;
    }

    public function destroy($id)
    {
        $this->db->delete('sessions', ['id' => $id]);
        return $this->session_cache->delete($this->session_cache_identifier . $id);
    }

    public function gc($max)
    {
        $rows = $this->db->run('SELECT id FROM sessions WHERE timestamp < ? AND remember_me = 0', time() - intval($max));
        foreach ($rows as $row) {
            //delete from cache and db
            $this->session_cache->delete($this->session_cache_identifier . $row['id']);
            $this->db->beginTransaction();
            $this->db->delete('sessions', [
                'id' => $row['id']
            ]);
            $this->db->commit();
        }
        return true;
    }

    public function create_sid()
    {
        return base64_encode(random_bytes(48));
    }

    public function start(int $lifetime = null, string $path = null, string $domain = null)
    {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params($cookieParams["lifetime"], "/", $cookieParams["domain"], $this->secure, true);
        session_name("id");
        session_start();
    }

    public function regenerate_id()
    {
        session_regenerate_id(true);
    }

    public function set(array $options, bool $lock_variables = false)
    {
        if ($lock_variables) {
            //lock the variable for any reads or writes until this operation is done
            die("not implemented");
        } else {
            //dont lock
            foreach ($options as $k => $v) {
                $_SESSION[$k] = $v;
            }
        }
    }

    public function remember_me(bool $enabled)
    {
        if ($enabled) {
            $enabled = 1;
        } else {
            $enabled = 0;
        }
        $this->db->update('sessions', ['remember_me' => $enabled], ['id' => session_id()]);
    }
}
