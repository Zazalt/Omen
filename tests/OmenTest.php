<?php

namespace Zazalt\Omen\Tests;

class OmenTest extends \PHPUnit_Framework_TestCase
{
    private $that;

    public function setUp()
    {
        $testedClassName    = str_replace('Test', '', substr(strrchr(__CLASS__, "\\"), 1));
        $testedClassPath    = 'Zazalt\\'.$testedClassName .'\\'. $testedClassName;
        require_once getcwd() . '/src/'. $testedClassName .'.php';

        // Load the rest of files
        $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(getcwd() . '/src/'),
                \RecursiveIteratorIterator::SELF_FIRST);
        foreach($iterator as $file) {
            if($file->isFile()) {
                require_once $file->getRealpath();
            }
        }

        $this->that = new $testedClassPath([]);
    }

    public function testFake()
    {
        $this->assertTrue(true);
        $this->assertFalse(false);
    }
}