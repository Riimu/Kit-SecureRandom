<?php

namespace Riimu\Kit\SecureRandom\Generator;

/**
 * Secure random byte generator interface.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Generator
{
    /**
     * Tells if the generator is supported on the system.
     * @return bool True if the generator is supported, false if not
     */
    public function isSupported();

    /**
     * Get securely generated random bytes.
     * @param int $count Number of bytes to return
     * @return string Bytes generated by the secure random generator
     * @throws \Riimu\Kit\SecureRandom\GeneratorException If error occurs in byte generation
     */
    public function getBytes($count);
}
