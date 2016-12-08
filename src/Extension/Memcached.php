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
        /*
		if(isset($configuration['memcached']) && $configuration['memcached']['enable'] && class_exists('Memcache')) {
			$memcache = new \Memcache;

			$isMemcacheAvailable = @$memcache->connect($configuration['memcached']['host'], $configuration['memcached']['port']);
			if ($isMemcacheAvailable) {
				return $memcache;
			} else {
				// TODO: warning
			}
		}
        */
    }
}