<?php

require __DIR__ . '/../src/autoload.php';

// Creates SecureRandom with a blocking reader
$rng = new \Riimu\Kit\SecureRandom\SecureRandom(
    new \Riimu\Kit\SecureRandom\Generator\Mcrypt(false)
);

var_dump($rng->getFloat());