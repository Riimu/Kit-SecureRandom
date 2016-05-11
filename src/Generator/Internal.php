<?php

namespace Riimu\Kit\SecureRandom\Generator;

use Riimu\Kit\SecureRandom\GeneratorException;

/**
 * Generates bytes and numbers using PHP's built in CSPRNG.
 *
 * PHP7 offers a built in function for generating cryptographically secure
 * random bytes. This class simply wraps that method for supported PHP versions.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Internal extends AbstractGenerator implements NumberGenerator
{
    public function isSupported()
    {
        return version_compare(PHP_VERSION, '7.0', '>=');
    }

    protected function readBytes($count)
    {
        return random_bytes($count);
    }

    public function getNumber($min, $max)
    {
        $min = (int) $min;
        $max = (int) $max;
        $exception = null;

        try {
            $number = random_int($min, $max);
        } catch (\Throwable $exception) {
            $number = false;
        }

        if (!is_int($number) || $number < $min || $number > $max) {
            throw new GeneratorException('Error generating random number', 0, $exception);
        }

        return $number;
    }
}
