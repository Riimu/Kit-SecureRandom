<?php

namespace Riimu\Kit\SecureRandom\Generator;

/**
 * Generates bytes reading directly from random device.
 *
 * RandomReader generator creates bytes by reading directly from either
 * /dev/urandom or /dev/random.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RandomReader implements Generator
{
    /**
     * Path to read from.
     * @var string
     */
    private $source;

    /**
     * Creates new instance of RandomReader.
     * @param bool $urandom True to read from /dev/urandom, false to read from /dev/random
     */
    public function __construct($urandom = true)
    {
        $this->source = $urandom ? '/dev/urandom' : '/dev/random';
    }

    public function isSupported()
    {
        return is_readable($this->source);
    }

    public function getBytes($count)
    {
        return file_get_contents($this->source, false, null, -1, $count);
    }
}
