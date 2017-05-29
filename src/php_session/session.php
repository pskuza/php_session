<?php
declare(strict_types=1);

namespace php_session;


class session
{

    protected $pdo = null;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}