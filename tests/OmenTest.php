<?php

namespace Zazalt\Omen\Tests;

use Zazalt\Omen\Omen;

class OmenTest extends \Zazalt\Omen\Tests\ZazaltTest
{
    protected $that;

    public function __construct()
    {
        parent::loader(Omen::class, []);
    }
}