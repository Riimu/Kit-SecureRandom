<?php

namespace Riimu\Kit\SecureRandom\Generator;

use Riimu\Kit\SecureRandom\GeneratorException;

/**
 * Abstract generator for handling byte generator errors.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractGenerator implements Generator
{
    public function getBytes($count)
    {
        $bytes = $this->readBytes($count);

        if ($bytes === false || strlen($bytes) !== $count) {
            throw new GeneratorException('Random source returned invalid number of bytes');
        }

        return $bytes;
    }

    /**
     * Reads bytes from the randomness source.
     * @param integer $count number of bytes to read
     * @return string|false The bytes read from the randomness source or false on error
     * @throws \Riimu\Kit\SecureRandom\GeneratorException If error occurs in byte generation
     */
    abstract protected function readBytes($count);
}
