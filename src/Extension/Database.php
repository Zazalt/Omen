<?php

namespace Zazalt\Omen\Extension;

class Database extends Memcached
{
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    protected static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function init()
    {
        //if (null === static::$instance) {
            static::$instance = new static();
        //}

        return static::$instance;
    }
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    protected $modelName;
    protected $connection;
    protected $memcached;

    public function __construct(Array $configuration)
    {
        $dsn = "pgsql:host={$configuration['postgres']['host']};port={$configuration['postgres']['port']};dbname={$configuration['postgres']['database']};user={$configuration['postgres']['username']};password={$configuration['postgres']['password']}";
        try {
            // create a PostgreSQL database connection
            $this->connection = new \PDO($dsn);
            //$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // display a message if connected to the PostgreSQL successfully
            if ($this->connection) {
                //echo "Connected to the <strong>$db</strong> database successfully!";
            }
        } catch (\PDOException $e) {
			// TODO: warning
            die($e->getMessage());
            //Logs::error($e->getMessage(), __FILE__, __LINE__);
        }

        $this->memcached = parent::__construct();
    }
}