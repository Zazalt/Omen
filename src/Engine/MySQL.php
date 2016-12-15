<?php

namespace Zazalt\Omen\Engine;

class MySQL implements EngineInterface
{
    private static $connection;

    public static function config(array $configuration)
    {
        $dsn = "mysql:host={$configuration['host']};port={$configuration['port']};dbname={$configuration['database']}";
        try {
            static::$connection = new \PDO($dsn, $configuration['username'], $configuration['password']);
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