<?php

namespace Zazalt\Omen\Extension;

use Zazalt\Omen\Engine\MySQL;
use Zazalt\Omen\Engine\PostgreSQL;

class Database extends Memcached
{
    const ENGINE_POSTGRESQL = 'postgresql'; // Default port: 5432
    const ENGINE_MYSQL      = 'mysql';      // Default port: 3306

    protected $engine = self::ENGINE_POSTGRESQL;
    protected $modelName;
    protected $connection;
    protected $memcached;

    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        if(isset($configuration['engine'])) {
            $this->engine = $configuration['engine'];
        }

        switch($this->engine) {
            case self::ENGINE_POSTGRESQL:
                $this->connection = PostgreSQL::config($configuration);// ->connect();
                break;

            case self::ENGINE_MYSQL:
                $this->connection = MySQL::config($configuration)->connect();
                break;
        }

        $this->memcached = parent::__construct();
    }
}