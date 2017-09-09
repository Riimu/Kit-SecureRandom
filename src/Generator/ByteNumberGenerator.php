<?php

namespace Riimu\Kit\SecureRandom\Generator;

use Riimu\Kit\SecureRandom\GeneratorException;

/**
 * A random number generator that wraps the given byte generator for generating integers.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ByteNumberGenerator implements NumberGenerator
{
    /** @var \Closure[] Closures for reading bytes */
    private $byteReaders;

    /** @var Generator The underlying byte generator */
    private $byteGenerator;

    /**
     * NumberByteGenerator constructor.
     * @param Generator $generator The underlying byte generator used to generate random bytes
     */
    public function __construct(Generator $generator)
    {
        $this->initializeByteReaders();
        $this->byteGenerator = $generator;
    }

    /**
     * Tells if the underlying byte generator is supported by the system.
     * @return bool True if the generator is supported, false if not
     */
    public function isSupported()
    {
        return $this->byteGenerator->isSupported();
    }

    /**
     * Returns bytes read from the provided byte generator.
     * @param int $count The number of bytes to read
     * @return string A string of bytes
     * @throws GeneratorException If there was an error generating the bytes
     */
    public function getBytes($count)
    {
        return $this->byteGenerator->getBytes($count);
    }

    /**
     * Returns a random integer between given minimum and maximum.
     * @param int $min The minimum possible value to return
     * @param int $max The maximum possible value to return
     * @return int A random number between the lower and upper limit (inclusive)
     * @throws \InvalidArgumentException If the provided values are invalid
     * @throws GeneratorException If an error occurs generating the number
     */
    public function getNumber($min, $max)
    {
        $min = (int) $min;
        $max = (int) $max;

        if ($min > $max) {
            throw new \InvalidArgumentException('Invalid minimum and maximum value');
        }

        if ($min === $max) {
            return $min;
        }

        $difference = $max - $min;

        if (!is_int($difference)) {
            throw new GeneratorException('Too large difference between minimum and maximum');
        }

        return $min + $this->getByteNumber($difference);
    }

    /**
     * Returns a random number generated using the random byte generator.
     * @param int $limit Maximum value for the random number
     * @return int The generated random number between 0 and the limit
     * @throws GeneratorException If error occurs generating the random number
     */
    private function getByteNumber($limit)
    {
        $bits = 1;
        $mask = 1;

        while ($limit >> $bits > 0) {
            $mask |= 1 << $bits;
            $bits++;
        }

        $readBytes = $this->byteReaders[(int) ceil($bits / 8)];

        do {
            $result = $readBytes() & $mask;
        } while ($result > $limit);

        return $result;
    }

    /**
     * Initializes the callbacks used to generate different numbers of bytes.
     */
    private function initializeByteReaders()
    {
        $this->byteReaders = [
            1 => function () {
                $bytes = unpack('C', $this->byteGenerator->getBytes(1));
                return $bytes[1];
            },
            2 => function () {
                $bytes = unpack('n', $this->byteGenerator->getBytes(2));
                return $bytes[1];
            },
            3 => function () {
                $bytes = unpack('Ca/nb', $this->byteGenerator->getBytes(3));
                return $bytes['a'] << 16 | $bytes['b'];
            },
            4 => function () {
                $bytes = unpack('N', $this->byteGenerator->getBytes(4));
                return $bytes[1];
            },
            5 => function () {
                $bytes = unpack('Ca/Nb', $this->byteGenerator->getBytes(5));
                return $bytes['a'] << 32 | $bytes['b'];
            },
            6 => function () {
                $bytes = unpack('na/Nb', $this->byteGenerator->getBytes(6));
                return $bytes['a'] << 32 | $bytes['b'];
            },
            7 => function () {
                $bytes = unpack('Ca/nb/Nc', $this->byteGenerator->getBytes(7));
                return $bytes['a'] << 48 | $bytes['b'] << 32 | $bytes['c'];
            },
            8 => function () {
                $bytes = unpack('J', $this->byteGenerator->getBytes(8));
                return $bytes[1];
            },
        ];
    }
}
