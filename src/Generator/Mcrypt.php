<?php

namespace Riimu\Kit\SecureRandom\Generator;

/**
 * Generates bytes using mcrypt extension.
 *
 * Mcrypt generator creates secure random byte using the mcrypt_create_iv
 * function. The generator can either use /dev/urandom or /dev/random as the
 * randomness source for the function. Note that on windows based systems, the
 * function resorts to windows specific random generator.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Mcrypt implements Generator
{
    /**
     * Random source for mcrypt_create_iv.
     * @var integer
     */
    private $mode;

    /**
     * Creates new insteance of Mcrypt generator.
     * @param bool $urandom True to use /dev/urandom and false for /dev/random
     */
    public function __construct($urandom = true)
    {
        $this->mode = $urandom ? MCRYPT_DEV_URANDOM : MCRYPT_DEV_RANDOM;
    }

    public function isSupported()
    {
        return function_exists('mcrypt_create_iv');
    }

    public function getBytes($count)
    {
        return mcrypt_create_iv($count, $this->mode);
    }
}
