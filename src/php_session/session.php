<?php
//declare(strict_types=1);

namespace php_session;

class session extends \SessionHandler
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier = "php_session_";

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, $session_cache)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

    }

    public function open($save_path = null, $id)
    {
        //var_dump($save_path, $id);
        //$this->db->insert('sessions', ['id' => $id, 'data' => NULL, 'timestamp' => time()]);

        return true;
    }

    public function read($id)
    {
        //return $this->db->row("SELECT data FROM sessions WHERE id = ?", $id);
        //use cache
        var_dump($id);
        var_dump(json_decode($this->session_cache->fetch($this->session_cache_identifier . $id)));

        return true;
    }

    public function close()
    {
        return true;
    }

    public function write($id, $data)
    {
        //do a dumb write for now
        var_dump($id);
        if (!empty($data)) {
            $data_json = json_encode($data);
        } else {
            $data_json = null;
        }
        if ($this->db->row("SELECT id FROM sessions WHERE id = ?", $id)) {
            var_dump("yes");
            $this->db->update('sessions', ['data' => $data_json], ['id' => $id]);
        } else {
            var_dump("no");
            $this->db->insert('sessions', [
                'id' => $id,
                'data' => $data_json,
                'timestamp' => time()
            ]);
        }
        //update the cache

        $this->session_cache->save($this->session_cache_identifier . $id, $data_json);
        //debug
        return true;
    }

    public function destroy($id)
    {
        $this->db->delete('sessions', ['id' => $id]);
        $this->session_cache->delete($this->session_cache_identifier . $id);

        return true; // debug
    }

    public function gc($lifetime)
    {
        return true;
    }

    public function create_sid()
    {
        return base64_encode(random_bytes(48));
    }

    public function startsession()
    {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params($cookieParams["lifetime"], "/", $cookieParams["domain"], FALSE, FALSE);
        session_name("id");
        session_start();
    }
}
