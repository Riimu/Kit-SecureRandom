<?php

namespace Riimu\Kit\SecureRandom;

use PHPUnit\Framework\TestCase;
use Riimu\Kit\SecureRandom\Generator\Generator;
use Riimu\Kit\SecureRandom\Generator\NumberGenerator;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class SecureRandomTest extends TestCase
{
    public function testEvenDistribution()
    {
        $count = 0;

        $mock = $this->getMockBuilder(Generator::class)
            ->setMethods(['getBytes', 'isSupported'])
            ->getMock();

        $mock->expects($this->once())->method('isSupported')->will($this->returnValue(true));
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
        $mock = $this->createMock(Generator::class);
        $mock->expects($this->once())->method('isSupported')->will($this->returnValue(false));

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
        $rng = $this->createWithList();
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
        $genarator = $this->createMock(NumberGenerator::class);
        $genarator->method('isSupported')->willReturn(true);
        $genarator->expects($this->once())->method('getNumber')->willReturn(7);

        $secure = new SecureRandom($genarator);
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
        $this->assertSame([0x0100 => 0x0100, 0x80 => 0x80, 0xFF => 0xFF], $rng->getArray(range(0, 256), 3));
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
            ['a' => '0', 'b' => '1', 'c' => '2'],
            $rng->shuffle(['a' => '0', 'b' => '1', 'c' => '2'])
        );
        $this->assertSame(
            [0 => 'a', 2 => 'c', 1 => 'b'],
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

        $mock = $this->createMock(Generator::class);
        $mock->method('isSupported')->willReturn(true);
        $with = $mock->expects($this->exactly(count($list)))->method('getBytes');
        $will = call_user_func_array([$with, 'withConsecutive'], array_map(function ($value) {
            return [$this->equalTo(strlen($value))];
        }, $strings));
        $will->will(call_user_func_array([$this, 'onConsecutiveCalls'], $strings));

        return new SecureRandom($mock);
    }
}
