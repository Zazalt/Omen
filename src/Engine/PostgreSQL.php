<?php

namespace Zazalt\Omen\Engine;

class PostgreSQL implements EngineInterface
{
    private static $connection;

    public static function config(array $configuration)
    {
        $host = (isset($configuration['host']) ? $configuration['host'] : null);
        $port = (isset($configuration['port']) ? $configuration['port'] : null);
        $database = (isset($configuration['database']) ? $configuration['database'] : null);
        $username = (isset($configuration['username']) ? $configuration['username'] : null);
        $password = (isset($configuration['password']) ? $configuration['password'] : null);

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        try {
            static::$connection = new \PDO($dsn, $username, $password);
        } catch (\PDOException $e) {
            die($e->getMessage());
            // TODO: continue
        }

        return new static(self);
    }

    public static function connect()
    {
        return static::$connection;
    }
}