<?php

require_once './../vendor/autoload.php';

$Omen = new \Zazalt\Omen\Omen([
    'engine'    => Zazalt\Omen\Omen::ENGINE_MYSQL,
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'username'  => 'root',
    'password'  => '',
    'database'  => 'my_data_base'
]);

$x = $Omen
    ->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'my_data_base'")
    ->fetch()
;

var_dump($x);