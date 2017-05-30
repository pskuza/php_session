<?php
//declare(strict_types=1);

namespace php_session;

class session
{
    protected $db = null;

    protected $session_cache = null;

    protected $session_cache_identifier = "php_session_";

    public function __construct(\ParagonIE\EasyDB\EasyDB $db, \Doctrine\Common\Cache $session_cache)
    {
        $this->db = $db;

        $this->session_cache = $session_cache;

        session_set_save_handler(array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc'));
        register_shutdown_function('session_write_close');
    }

    public function open(string $id)
    {
        return $this->db->insert('session', ['id' => $id, 'data' => NULL, 'timestamp' => time()]);
    }

    public function read(string $id)
    {
        //return $this->db->row("SELECT data FROM session WHERE id = ?", $id);
        //use cache
        return json_decode($this->session_cache->fetch($this->session_cache_identifier . $id));
    }

    public function write(string $id, $data)
    {
        //do a dumb write for now
        $data_json = json_encode($data);
        $this->db->update('session', ['data' => $data_json], ['id' => $id]);
        //update the cache

        $this->session_cache->save($this->session_cache_identifier . $id, $data_json);
        //debug
        return true;
    }

    public function destroy(string $id)
    {
        $this->db->delete('session', ['id' => $id]);
        $this->session_cache->delete($this->session_cache_identifier . $id);

        return true; // debug
    }

    public function gc()
    {
        return true;
    }
}
