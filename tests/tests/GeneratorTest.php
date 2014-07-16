<?php

namespace Riimu\Kit\SecureRandom;

use Riimu\Kit\SecureRandom\Generator\RandomReader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class GeneratorsTest extends \PHPUnit_Framework_TestCase
{
    public function testRandomReader()
    {
        $this->assertGeneratorWorks(new Generator\RandomReader(true));
    }

    public function testRandomReaderShutdown()
    {
        $rng = new RandomReader();
        if (!$rng->isSupported()) {
            $this->markTestSkipped('/dev/urandom cannot be read');
        }

        $prop = (new \ReflectionClass($rng))->getProperty('pointer');
        $prop->setAccessible(true);

        $this->assertNull($prop->getValue($rng));
        $rng->getBytes(1);
        $this->assertNotNull($prop->getValue($rng));
        $rng->__destruct();
        $this->assertNull($prop->getValue($rng));
    }

    public function testBlockingRandomReader()
    {
        $this->assertGeneratorWorks(new Generator\RandomReader(false));
    }

    public function testMcrypt()
    {
        $this->assertGeneratorWorks(new Generator\Mcrypt(true));
    }

    public function testBlockingMcrypt()
    {
        $this->assertGeneratorWorks(new Generator\Mcrypt(false));
    }

    public function testOpenSSL()
    {
        $this->assertGeneratorWorks(new Generator\OpenSSL());
    }

    public function testOpenSSLFail()
    {
        $generator = new Generator\OpenSSL();

        if (!$generator->isSupported()) {
            $this->markTestSkipped('Support for ' . get_class($generator) . ' is missing');
        }

        $this->setExpectedException('Riimu\Kit\SecureRandom\GeneratorException');
        $generator->getBytes(0);
    }

    public function assertGeneratorWorks(Generator\Generator $generator)
    {
        if (!$generator->isSupported()) {
            $this->markTestSkipped('Support for ' . get_class($generator) . ' is missing');
        }

        $this->assertSame(1, strlen($generator->getBytes(1)));
    }
}
