<?php

namespace Zazalt\Omen\Tests;

use Zazalt\Omen\Omen;

class OmenTest extends \Zazalt\Omen\Tests\ZazaltTest
{
    /** @var Omen */
    protected $that;

    public function __construct()
    {
        parent::loader(Omen::class, []);
    }

    public function testContentToString(): void
    {
        $batteryTest = [
            "test = 'test'" => ['test', '=', 'test'],
            "test > 'test'" => ['test', '>', 'test']
        ];

        foreach ($batteryTest as [$expected, $given]) {
            $this->assertEquals($expected, $this->that->contentToString($given));
        }
    }
}