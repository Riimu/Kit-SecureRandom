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
    /** @var Generator The underlying byte generator */
    private $byteGenerator;

    /**
     * NumberByteGenerator constructor.
     * @param Generator $generator The underlying byte generator used to generate random bytes
     */
    public function __construct(Generator $generator)
    {
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

        return $min + $this->getByteNumber($max - $min);
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

        $bytes = (int) ceil($bits / 8);

        do {
            $result = hexdec(bin2hex($this->byteGenerator->getBytes($bytes))) & $mask;
        } while ($result > $limit);

        return $result;
    }
}
