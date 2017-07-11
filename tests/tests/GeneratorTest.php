<?php

namespace Riimu\Kit\SecureRandom;

use PHPUnit\Framework\TestCase;
use Riimu\Kit\SecureRandom\Generator\AbstractGenerator;
use Riimu\Kit\SecureRandom\Generator\ByteNumberGenerator;
use Riimu\Kit\SecureRandom\Generator\Generator;
use Riimu\Kit\SecureRandom\Generator\Internal;
use Riimu\Kit\SecureRandom\Generator\Mcrypt;
use Riimu\Kit\SecureRandom\Generator\OpenSSL;
use Riimu\Kit\SecureRandom\Generator\RandomReader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class GeneratorTest extends TestCase
{
    public function testInvalidTypeOfBytes()
    {
        $mock = $this->getMockBuilder(AbstractGenerator::class)
            ->setMethods(['isSupported', 'readBytes'])
            ->getMock();

        $mock->expects($this->once())->method('readBytes')->willReturn(true);

        $this->expectException(GeneratorException::class);
        $mock->getBytes(6);
    }

    public function testInvalidNumberOfBytes()
    {
        $mock = $this->getMockBuilder(AbstractGenerator::class)
            ->setMethods(['isSupported', 'readBytes'])
            ->getMock();

        $mock->expects($this->once())->method('readBytes')->will($this->returnValue('aa'));

        $this->expectException(GeneratorException::class);
        $mock->getBytes(6);
    }

    public function testRandomReader()
    {
        $this->assertGeneratorWorks(new RandomReader(true));
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
        $this->assertGeneratorWorks(new RandomReader(false));
    }

    public function testMcrypt()
    {
        $this->assertGeneratorWorks(new Mcrypt(true));
    }

    public function testBlockingMcrypt()
    {
        $this->assertGeneratorWorks(new Mcrypt(false));
    }

    public function testOpenSSL()
    {
        $this->assertGeneratorWorks(new OpenSSL());
    }

    public function testOpenSSLFail()
    {
        $generator = new OpenSSL();

        if (!$generator->isSupported()) {
            $this->markTestSkipped('Support for ' . get_class($generator) . ' is missing');
        }

        $method = new \ReflectionMethod($generator, 'readBytes');
        $method->setAccessible(true);

        $this->expectException(GeneratorException::class);
        $method->invoke($generator, 0);
    }

    public function testInternal()
    {
        $this->assertGeneratorWorks(new Internal());
    }

    public function testInternalNumberGenerator()
    {
        $generator = new Internal();

        if (!$generator->isSupported()) {
            $this->markTestSkipped('Support for ' . get_class($generator) . ' is missing');
        }

        $secure = new SecureRandom($generator);
        $this->assertInternalType('int', $secure->getInteger(0, 10));
    }

    public function testInternalFail()
    {
        $generator = new Internal();

        if (!$generator->isSupported()) {
            $this->markTestSkipped('Support for ' . get_class($generator) . ' is missing');
        }

        $this->expectException(GeneratorException::class);
        $generator->getNumber(10, 0);
    }

    public function testByteNumberGeneratorInvalidLimits()
    {
        $generator = new ByteNumberGenerator($this->createMock(Generator::class));

        $this->expectException(\InvalidArgumentException::class);
        $generator->getNumber(1, 0);
    }

    public function testByteNumberGeneratorSupportPass()
    {
        $generator = $this->createMock(Generator::class);
        $generator->expects($this->once())->method('isSupported')->willReturn(true);

        $byteNumberGenerator = new ByteNumberGenerator($generator);

        $this->assertTrue($byteNumberGenerator->isSupported());
    }

    public function assertGeneratorWorks(Generator $generator)
    {
        if (!$generator->isSupported()) {
            $this->markTestSkipped('Support for ' . get_class($generator) . ' is missing');
        }

        if ($generator instanceof Mcrypt && version_compare(PHP_VERSION, '7.1', '>=')) {
            $bytes = @$generator->getBytes(16);
        } else {
            $bytes = $generator->getBytes(16);
        }

        $this->assertSame(16, strlen($bytes));
    }
}
