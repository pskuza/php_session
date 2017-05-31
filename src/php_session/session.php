<?php
//declare(strict_types=1);

namespace php_session;

class session extends \SessionHandler
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier = "php_session_";

    protected $cachetime;

    protected $secure = true;

    protected $per_variable_locking = false;

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, $session_cache, int $cachetime = 3600, bool $secure = null, bool $per_variable_locking = null)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

        if (!empty($cachetime)) {
            $this->cachetime = $cachetime;
        }

        if (!is_null($secure)) {
            $this->secure = $secure;
        }

        if (!is_null($per_variable_locking)) {
            $this->per_variable_locking = $per_variable_locking;
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
                var_dump("in db");
                $this->session_cache->save($this->session_cache_identifier . $id, $data, $this->cachetime);
                return $data;
            }
        }
        return false;
    }

    public function write($id, $data)
    {
        //do a dumb write for now
        if ($this->db->row("SELECT id FROM sessions WHERE id = ?", $id)) {
            $this->db->update('sessions', ['data' => $data], ['id' => $id]);
        } else {
            $this->destroy($id);
            $this->db->insert('sessions', [
                'id' => $id,
                'data' => $data,
                'timestamp' => time()
            ]);
        }
        //update the cache

        return $this->session_cache->save($this->session_cache_identifier . $id, $data, $this->cachetime);
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

    public function startsession(int $lifetime = null, string $path = null, string $domain = null)
    {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params($cookieParams["lifetime"], "/", $cookieParams["domain"], $this->secure, TRUE);
        var_dump($cookieParams);
        session_name("id");
        session_start();
    }
}
