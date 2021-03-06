<?php

namespace Riimu\Kit\SecureRandom;

use PHPUnit\Framework\TestCase;
use Riimu\Kit\SecureRandom\Generator\AbstractGenerator;
use Riimu\Kit\SecureRandom\Generator\ByteNumberGenerator;
use Riimu\Kit\SecureRandom\Generator\Generator;
use Riimu\Kit\SecureRandom\Generator\Internal;
use Riimu\Kit\SecureRandom\Generator\NumberGenerator;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class SecureRandomTest extends TestCase
{
    public function testEvenDistribution()
    {
        $count = 0;

        /** @var \PHPUnit_Framework_MockObject_MockObject|Generator $mock */
        $mock = $this->getMockBuilder(Generator::class)
            ->setMethods(['getBytes', 'isSupported'])
            ->getMock();

        $mock->expects($this->once())->method('isSupported')->willReturn(true);
        $mock->expects($this->any())->method('getBytes')->with($this->equalTo(1))->will(
            $this->returnCallback(function () use (& $count) {
                return chr($count++ & 255);
            })
        );

        $random = new SecureRandom($mock);
        $counts = array_fill(0, 18, 0);

        for ($i = 0; $i < 20 * 18; $i++) {
            $counts[$random->getInteger(0, 17)]++;
        }

        $this->assertSame(array_fill(0, 18, 20), $counts);
    }

    public function testCreatingWithDefaultGenerator()
    {
        $rng = new SecureRandom();
        $reflection = new \ReflectionProperty($rng, 'generator');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(Generator::class, $reflection->getValue($rng));
    }

    public function testInvalidGenerator()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Generator $mock */
        $mock = $this->createMock(Generator::class);
        $mock->expects($this->once())->method('isSupported')->willReturn(false);

        $this->expectException(GeneratorException::class);
        new SecureRandom($mock);
    }

    public function testUnsupportedDefaultGenerators()
    {
        $list = new \ReflectionProperty(SecureRandom::class, 'defaultGenerators');
        $list->setAccessible(true);
        $defaults = $list->getValue();
        $list->setValue([]);

        try {
            $this->expectException(GeneratorException::class);
            new SecureRandom();
        } finally {
            $list->setValue($defaults);
        }
    }

    public function testInvalidByteCount()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getBytes(-1);
    }

    public function testZeroByteCount()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractGenerator $generator */
        $generator = $this->getMockBuilder(AbstractGenerator::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSupported', 'readBytes'])
            ->getMock();

        $generator->expects($this->any())->method('isSupported')->willReturn(true);
        $generator->expects($this->never())->method('readBytes');

        $rng = new SecureRandom($generator);
        $this->assertSame('', $rng->getBytes(0));
    }

    public function testNegativeMinimumValue()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getInteger(-1, 1);
    }

    public function testSmallerMaximumValue()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getInteger(1, 0);
    }

    public function testTooHighMaximum()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getInteger(0, PHP_INT_MAX + 1);
    }

    public function testSameMinimumAndMaximum()
    {
        $rng = $this->createWithList();
        $this->assertSame(123, $rng->getInteger(123, 123));
    }

    public function testInvalidNumberOfElements()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getArray([], 1);
    }

    public function testZeroElements()
    {
        $rng = $this->createWithList();
        $this->assertSame([], $rng->getArray([1, 2, 3], 0));
    }

    public function testZeroElementsFromEmptyArray()
    {
        $rng = $this->createWithList();
        $this->assertSame([], $rng->getArray([], 0));
    }

    public function testShufflingEmptyArray()
    {
        $rng = $this->createWithList();
        $this->assertSame([], $rng->shuffle([]));
    }

    public function testChoosingFromEmptyArray()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->choose([]);
    }

    public function testChoosingFromSingleValue()
    {
        $rng = $this->createWithList();
        $this->assertSame('foo', $rng->choose(['foo']));
    }

    public function testInvalidSequenceLength()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getSequence('abc', -1);
    }

    public function testInvalidSequenceChoiceCount()
    {
        $rng = $this->createWithList();
        $this->expectException(\InvalidArgumentException::class);
        $rng->getSequence([], 1);
    }

    public function testEmptySequenceFromNoChoices()
    {
        $rng = $this->createWithList();
        $this->assertSame([], $rng->getSequence([], 0));
        $this->assertSame('', $rng->getSequence('', 0));
    }

    public function testOneChoiceSequence()
    {
        $rng = $this->createWithList();
        $this->assertSame('aaaa', $rng->getSequence('a', 4));
        $this->assertSame(['a', 'a', 'a', 'a'], $rng->getSequence(['a'], 4));
    }

    public function testZeroLengthSequence()
    {
        $rng = $this->createWithList();
        $this->assertSame('', $rng->getSequence('123', 0));
        $this->assertSame([], $rng->getSequence([1, 2, 3], 0));
    }

    public function testGetBytes()
    {
        $string = 'kkl;..++';
        $rng = $this->createWithList([32, $string]);
        $this->assertSame(chr(32), $rng->getBytes(1));
        $this->assertSame($string, $rng->getBytes(strlen($string)));
    }

    public function testGetInteger()
    {
        $rng = $this->createWithList([0b101, 0b1111, 0, 1, [3, 203], [3, 30022], [3, 411233], 3, 2]);
        $this->assertSame(0b101, $rng->getInteger(0, 0b111));
        $this->assertSame(0b111, $rng->getInteger(0, 0b111));
        $this->assertSame(500000, $rng->getInteger(500000, 500001));
        $this->assertSame(500001, $rng->getInteger(500000, 500001));
        $this->assertSame(203, $rng->getInteger(0, 500000));
        $this->assertSame(30022, $rng->getInteger(0, 500000));
        $this->assertSame(411233, $rng->getInteger(0, 500000));
        $this->assertSame(2, $rng->getInteger(0, 2));
    }

    public function testUsingNumberGenerator()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|NumberGenerator $generator */
        $generator = $this->createMock(NumberGenerator::class);
        $generator->method('isSupported')->willReturn(true);
        $generator->expects($this->once())->method('getNumber')->willReturn(7);

        $secure = new SecureRandom($generator);
        $this->assertSame(7, $secure->getInteger(0, 100));
    }

    public function testGetFloat()
    {
        $rng = $this->createWithList([[PHP_INT_SIZE, 9090], [PHP_INT_SIZE, 0], [PHP_INT_SIZE, PHP_INT_MAX]]);
        $this->assertSame(9090 / PHP_INT_MAX, $rng->getFloat());
        $this->assertSame(0.0, $rng->getFloat());
        $this->assertSame(1.0, $rng->getFloat());
    }

    public function testGetArray()
    {
        $rng = $this->createWithList([2, 1, 1, 2, 1, 1]);

        $associative = ['a' => '0', 'b' => '1', 'c' => '2'];
        $numeric = ['a', 'b', 'c'];

        $this->assertSame(
            ['c' => '2'],
            $rng->getArray($associative, 1)
        );

        $this->assertSame(
            ['b' => '1', 'c' => '2'],
            $rng->getArray($associative, 2)
        );

        $this->assertSame([2 => 'c'], $rng->getArray($numeric, 1));
        $this->assertSame([1 => 'b', 2 => 'c'], $rng->getArray($numeric, 2));
    }

    public function testMinimalBytes()
    {
        $rng = $this->createWithList([0x0100, 0x80, 0xFF, 0x80]);
        $this->assertSame([0x0100 => 0x0100, 0x81 => 0x81, 0x82 => 0x82], $rng->getArray(range(0, 256), 3));
    }

    public function testChoose()
    {
        $rng = $this->createWithList([1]);
        $this->assertSame('1', $rng->choose([
            'a' => '0', 'b' => '1', 'c' => '2',
        ]));
    }

    public function testShuffle()
    {
        $rng = $this->createWithList([0, 1, 0, 0]);
        $this->assertSame(
            ['a' => '0', 'c' => '2', 'b' => '1'],
            $rng->shuffle(['a' => '0', 'b' => '1', 'c' => '2'])
        );
        $this->assertSame(
            [0 => 'a', 1 => 'b', 2 => 'c'],
            $rng->shuffle(['a', 'b', 'c'])
        );
    }

    public function testSequence()
    {
        $rng = $this->createWithList([0, 3, 2, 3, 1]);
        $this->assertSame('adcdb', $rng->getSequence('abcd', 5));
    }

    public function testRandomBounds()
    {
        $rng = $this->createWithList([str_repeat("\x00", 7), str_repeat("\xFF", 7)]);
        $this->assertSame(0.0, $rng->getRandom());
        $this->assertTrue($rng->getRandom() < 1);
    }

    public function testUuid()
    {
        $rng = $this->createWithList([
            str_repeat("\x00", 16),
            str_repeat("\xFF", 16),
            "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xAA\xBB\xCC\xDD\xEE\xFF",
        ]);

        $this->assertSame('00000000-0000-4000-8000-000000000000', $rng->getUuid());
        $this->assertSame('ffffffff-ffff-4fff-bfff-ffffffffffff', $rng->getUuid());
        $this->assertSame('00112233-4455-4677-8899-aabbccddeeff', $rng->getUuid());
    }

    public function testBitCounts()
    {
        $number = 0;

        /** @var \PHPUnit_Framework_MockObject_MockObject|Generator $generator */
        $generator = $this->createMock(Generator::class);
        $generator->method('isSupported')->willReturn(true);
        $generator->method('getBytes')->willReturnCallback(function ($count) use (& $number) {
            $pad = 2 * (1 + (int) log($number, 256));
            $bytes = hex2bin(sprintf("%0{$pad}x", $number));
            $this->assertSame(strlen($bytes), $count);
            return $bytes;
        });

        $random = new SecureRandom($generator);

        for ($i = 1; $i < PHP_INT_SIZE * 8; $i++) {
            $maximum = (1 << $i) < 0 ? PHP_INT_MAX : (1 << $i) - 1;
            $number = mt_rand(1 << ($i - 1), $maximum);
            $this->assertSame($number, $random->getInteger(0, $maximum));
        }
    }

    public function testInvalidDifference()
    {
        $generator = new ByteNumberGenerator(new Internal());

        $this->expectException(GeneratorException::class);
        $generator->getNumber(~PHP_INT_MAX, PHP_INT_MAX);
    }

    private function createWithList(array $list = [])
    {
        $strings = [];

        foreach ($list as $int) {
            $string = '';

            if (is_int($int)) {
                do {
                    $string = chr($int & 255) . $string;
                    $int >>= 8;
                } while ($int);
            } elseif (is_array($int)) {
                for ($i = 0; $i < $int[0]; $i++) {
                    $string = chr(($int[1] >> ($i * 8)) & 255) . $string;
                }
            } else {
                $string = (string) $int;
            }

            $strings[] = $string;
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|Generator $mock */
        $mock = $this->createMock(Generator::class);
        $mock->method('isSupported')->willReturn(true);
        $with = $mock->expects($this->exactly(count($list)))->method('getBytes');
        $will = call_user_func_array([$with, 'withConsecutive'], array_map(function ($value) {
            return [$this->equalTo(strlen($value))];
        }, $strings));
        $will->will($this->onConsecutiveCalls(... $strings));

        return new SecureRandom($mock);
    }
}
