<?php

namespace Zazalt\Omen\Engine;

class PostgreSQL implements EngineInterface
{
    private static $connection;

    public static function config(array $configuration)
    {
        $dsn = "pgsql:host={$configuration['host']};port={$configuration['port']};dbname={$configuration['database']};user={$configuration['username']};password={$configuration['password']}";
        try {
            static::$connection = new \PDO($dsn);
        } catch (\PDOException $e) {
            // TODO: continue
        }

        return new static(self);
    }

    public static function connect()
    {
        return static::$connection;
    }
}