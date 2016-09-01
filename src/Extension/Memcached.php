<?php

namespace Zazalt\Omen\Extension;

if (class_exists('Memcache')) {
    class MiddleMemcached extends \Memcache { }
} else {
    class MiddleMemcached { }
}

class Memcached extends MiddleMemcached
{
    public function __construct()
    {
		global $config;

		if($config['memcached']['enable'] && class_exists('Memcache')) {
			$memcache = new \Memcache;

			$isMemcacheAvailable = @$memcache->connect($config['memcached']['host'], $config['memcached']['port']);
			if ($isMemcacheAvailable) {
				return $memcache;
			} else {
				// TODO: warning
			}
		}
    }
}