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
        return $this->db->insert('session', ['id' => $id, 'data' => NULL, 'timestamp' => time()]);
    }

    public function read($id)
    {
        //return $this->db->row("SELECT data FROM session WHERE id = ?", $id);
        //use cache
        return json_decode($this->session_cache->fetch($this->session_cache_identifier . $id));
    }

    public function write($id, $data)
    {
        //do a dumb write for now
        $data_json = json_encode($data);
        $this->db->update('session', ['data' => $data_json], ['id' => $id]);
        //update the cache

        $this->session_cache->save($this->session_cache_identifier . $id, $data_json);
        //debug
        return true;
    }

    public function destroy($id)
    {
        $this->db->delete('session', ['id' => $id]);
        $this->session_cache->delete($this->session_cache_identifier . $id);

        return true; // debug
    }

    public function gc($lifetime)
    {
        return true;
    }
}
