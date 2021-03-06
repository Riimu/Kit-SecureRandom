<?php

namespace Riimu\Kit\SecureRandom;

use Riimu\Kit\SecureRandom\Generator\ByteNumberGenerator;
use Riimu\Kit\SecureRandom\Generator\NumberGenerator;

/**
 * Library for normalizing bytes returned by secure random byte generators.
 *
 * SecureRandom takes bytes generated by secure random byte generators and
 * normalizes (i.e. provides even distribution) for other common usages, such
 * as generation of integers, floats and randomizing arrays.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class SecureRandom
{
    /** @var NumberGenerator The secure random generator used to generate bytes and numbers */
    private $generator;

    /** @var string[] List of default generators */
    private static $defaultGenerators = [
        Generator\Internal::class,
        Generator\RandomReader::class,
        Generator\Mcrypt::class,
        Generator\OpenSSL::class,
    ];

    /**
     * Creates a new instance of SecureRandom.
     *
     * You can either provide a generator to use for generating random bytes or
     * give null as the argument to use default generators. If null is provided,
     * the constructor will attempt to create the random byte generators in the
     * following order until it finds one that is supported:
     *
     * - Internal
     * - RandomReader
     * - Mcrypt
     * - OpenSSL
     *
     * Note that since most cases require non-blocking random generation, the
     * default generators use /dev/urandom as the random source. If you do not
     * think this provides enough security, create the desired random generator
     * using /dev/random as the source.
     *
     * @param Generator\Generator|null $generator Random byte generator or null for default
     * @throws GeneratorException If the provided or default generators are not supported
     */
    public function __construct(Generator\Generator $generator = null)
    {
        if ($generator === null) {
            $generator = $this->getDefaultGenerator();
        } elseif (!$generator->isSupported()) {
            throw new GeneratorException('The provided secure random byte generator is not supported by the system');
        }

        if (!$generator instanceof NumberGenerator) {
            $generator = new ByteNumberGenerator($generator);
        }

        $this->generator = $generator;
    }

    /**
     * Returns the first supported default secure random byte generator.
     * @return Generator\Generator Supported secure random byte generator
     * @throws GeneratorException If none of the default generators are supported
     */
    private function getDefaultGenerator()
    {
        foreach (self::$defaultGenerators as $generator) {
            /** @var Generator\Generator $generator */
            $generator = new $generator();

            if ($generator->isSupported()) {
                return $generator;
            }
        }

        throw new GeneratorException('Default secure random byte generators are not supported by the system');
    }

    /**
     * Returns a number of random bytes.
     * @param int $count Number of random bytes to return
     * @return string Randomly generated bytes
     * @throws \InvalidArgumentException If the count is invalid
     */
    public function getBytes($count)
    {
        $count = (int) $count;

        if ($count < 0) {
            throw new \InvalidArgumentException('Number of bytes must be 0 or more');
        }

        return $this->generator->getBytes($count);
    }

    /**
     * Returns a random integer between two positive integers (inclusive).
     * @param int $min Minimum limit
     * @param int $max Maximum limit
     * @return int Random integer between minimum and maximum limit
     * @throws \InvalidArgumentException If the limits are invalid
     */
    public function getInteger($min, $max)
    {
        $min = (int) $min;
        $max = (int) $max;

        if ($this->isOutOfBounds($min, 0, $max)) {
            throw new \InvalidArgumentException('Invalid minimum or maximum value');
        }

        return $this->generator->getNumber($min, $max);
    }

    /**
     * Tells if the given number is not within given limits (inclusive).
     * @param int $number The number to test
     * @param int $min The minimum allowed limit
     * @param int $max The maximum allowed limit
     * @return bool True if the number is out of bounds, false if within bounds
     */
    private function isOutOfBounds($number, $min, $max)
    {
        return $number < $min || $max < $number;
    }

    /**
     * Returns a random float between 0 and 1 (excluding the number 1).
     * @return float Random float between 0 and 1 (excluding 1)
     */
    public function getRandom()
    {
        $bytes = unpack('C7', $this->generator->getBytes(7));
        $lastByte = array_pop($bytes) & 0b00011111;
        $result = 0.0;

        foreach ($bytes as $byte) {
            $result = ($byte + $result) / 256;
        }

        $result = ($lastByte + $result) / 32;

        return $result;
    }

    /**
     * Returns a random float between 0 and 1 (inclusive).
     * @return float Random float between 0 and 1 (inclusive)
     */
    public function getFloat()
    {
        return (float) ($this->generator->getNumber(0, PHP_INT_MAX) / PHP_INT_MAX);
    }

    /**
     * Returns a number of randomly selected elements from the array.
     *
     * This method returns randomly selected elements from the array. The number
     * of elements is determined by by the second argument. The elements are
     * returned in random order but the keys are preserved.
     *
     * @param array $array Array of elements
     * @param int $count Number of elements to return from the array
     * @return array Randomly selected elements in random order
     * @throws \InvalidArgumentException If the count is invalid
     */
    public function getArray(array $array, $count)
    {
        $count = (int) $count;
        $size = count($array);

        if ($this->isOutOfBounds($count, 0, $size)) {
            throw new \InvalidArgumentException('Invalid number of elements');
        }

        $result = [];
        $keys = array_keys($array);

        for ($i = 0; $i < $count; $i++) {
            $index = $this->generator->getNumber($i, $size - 1);
            $result[$keys[$index]] = $array[$keys[$index]];
            $keys[$index] = $keys[$i];
        }

        return $result;
    }

    /**
     * Returns one randomly selected value from the array.
     * @param array $array The array to choose from
     * @return mixed One randomly selected value from the array
     * @throws \InvalidArgumentException If the array is empty
     */
    public function choose(array $array)
    {
        if (count($array) < 1) {
            throw new \InvalidArgumentException('Array must have at least one value');
        }

        $result = array_slice($array, $this->generator->getNumber(0, count($array) - 1), 1);

        return current($result);
    }

    /**
     * Returns the array with the elements reordered in a random order.
     * @param array $array The array to shuffle
     * @return array The provided array with elements in a random order
     */
    public function shuffle(array $array)
    {
        return $this->getArray($array, count($array));
    }

    /**
     * Returns a random sequence of values.
     *
     * If a string is provided as the first argument, the method returns a
     * string with characters selected from the provided string. The length of
     * the returned string is determined by the second argument.
     *
     * If an array is provided as the first argument, the method returns an
     * array with elements selected from the provided array. The size of the
     * returned array is determined by the second argument.
     *
     * The functionality is similar to getArray(), except for the fact that the
     * returned value can contain the same character or element multiple times.
     * If the same character or element appears multiple times in the provided
     * argument, it will increase the relative chance of it appearing in the
     * returned value.
     *
     * @param string|array $choices Values to choose from
     * @param int $length Length of the sequence
     * @return array|string The generated random sequence
     * @throws \InvalidArgumentException If the choices or length is invalid
     */
    public function getSequence($choices, $length)
    {
        $length = (int) $length;

        if ($length < 0) {
            throw new \InvalidArgumentException('Invalid sequence length');
        }

        if (is_array($choices)) {
            return $this->getSequenceValues(array_values($choices), $length);
        }

        return implode($this->getSequenceValues(str_split((string) $choices), $length));
    }

    /**
     * Returns the selected list of values for the sequence.
     * @param array $values List of possible values
     * @param int $length Number of values to return
     * @return array Selected list of values for the sequence
     * @throws \InvalidArgumentException If the value set is empty
     */
    private function getSequenceValues(array $values, $length)
    {
        if ($length < 1) {
            return [];
        }

        if (count($values) < 1) {
            throw new \InvalidArgumentException('Cannot generate sequence from empty value set');
        }

        $size = count($values);
        $result = [];

        for ($i = 0; $i < $length; $i++) {
            $result[] = $values[$this->generator->getNumber(0, $size - 1)];
        }

        return $result;
    }

    /**
     * Returns a random UUID version 4 identifier.
     * @return string A random UUID identifier
     */
    public function getUuid()
    {
        $integers = array_values(unpack('n8', $this->generator->getBytes(16)));

        $integers[3] &= 0x0FFF;
        $integers[4] = $integers[4] & 0x3FFF | 0x8000;

        return vsprintf('%04x%04x-%04x-4%03x-%04x-%04x%04x%04x', $integers);
    }
}
