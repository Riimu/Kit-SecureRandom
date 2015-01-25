# Secure Random Generator #

SecureRandom is a PHP library for using available secure random byte generators
to generate secure random values. This library has two main purposes:

  * to provide a common interface for different secure random generators
    available on the system
  * to provide an even distribution from the randomized bytes for most common
    use cases such as generating integers or floats or randomizing arrays

This library does not provide any additional secure random byte generators. It
simply uses the byte generators that are available to PHP via extensions. The
three generators that are commonly available to PHP are:

  * reading from `/dev/(u)random`
  * calling `mcrypt_create_iv()`
  * calling `openssl_random_pseudo_bytes()`

The security of the randomness generated by this library entirely dependant on
the underlying random byte generator. The library does not do any additional
transformations on the bytes other than the normalization needed to generate an
even distribution of random numbers.

The API documentation, which can be generated using Apigen, can be read online
at: http://kit.riimu.net/api/securerandom/

[![Build Status](https://img.shields.io/travis/Riimu/Kit-SecureRandom.svg?style=flat)](https://travis-ci.org/Riimu/Kit-SecureRandom)
[![Coverage Status](https://img.shields.io/coveralls/Riimu/Kit-SecureRandom.svg?style=flat)](https://coveralls.io/r/Riimu/Kit-SecureRandom?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Riimu/Kit-SecureRandom.svg?style=flat)](https://scrutinizer-ci.com/g/Riimu/Kit-SecureRandom/?branch=master)

## Requirements ##

In order to use this library, the following requirements must be met:

  * PHP version 5.4
  * One of following secure random sources must be available:
    * `/dev/urandom` must be readable
    * `Mcrypt` extension must be enabled
    * `OpenSSL` extension must be enabled

## Installation ##

This library can be installed via [Composer](http://getcomposer.org/). To do
this, download the `composer.phar` and require this library as a dependency. For
example:

```
$ php -r "readfile('https://getcomposer.org/installer');" | php
$ php composer.phar require riimu/kit-securerandom:1.*
```

Alternatively, you can add the dependency to your `composer.json` and run
`composer install`. For example:

```json
{
    "require": {
        "riimu/kit-securerandom": "1.*"
    }
}
```

Any library that has been installed via Composer can be loaded by including the
`vendor/autoload.php` file that was generated by Composer.

It is also possible to install this library manually. To do this, download the
[latest release](https://github.com/Riimu/Kit-SecureRandom/releases/latest) and
extract the `src` folder to your project folder. To load the library, include
the provided `src/autoload.php` file.

## Usage ##

Usage of the library is very simple. Simply create an instance of the
`SecureRandom` and call any of the methods to generate random values. For
example:

```php
<?php

require 'vendor/autoload.php';
$rng = new \Riimu\Kit\SecureRandom\SecureRandom();

var_dump(base64_encode($rng->getBytes(32)));     // Returns a random byte string
var_dump($rng->getInteger(100, 1000));           // Returns a random integer between 100 and 1000
var_dump($rng->getFloat());                      // Returns a random float between 0 and 1
var_dump($rng->getArray(range(0, 100), 5));      // Returns 5 randomly selected elements from the array
var_dump($rng->choose(range(0, 100)));           // Returns one randomly chosen value from the array
var_dump($rng->shuffle(range(0, 9)));            // Returns the array in random order
var_dump($rng->getSequence('01', 32));           // Returns a random sequence of 0s and 1s with length of 32
var_dump($rng->getSequence(['a', 'b', 'c'], 5)); // Returns an array with 5 elements randomly chosen from 'a', 'b', and 'c'
```

### Random methods ###

The following methods are available in `SecureRandom` to retrieve randomness:

  * `getBytes($count)` returns a string of random bytes with length equal to
    $count.
    
  * `getInteger($min, $max)` returns an random integer between the two given
    positive integers (inclusive).
    
  * `getFloat()` returns a random float value between 0 and 1 (inclusive).
  
  * `getArray(array $array, $count)` returns a number of random elements from
    the given array. The elements are in random order, but the keys are
    preserved.
    
  * `choose(array $array)` returns a random value chosen from the array.
  
  * `shuffle(array $array)` returns the array in random order. The keys are
    preserved.
    
  * `getSequence($choices, $length)` returns a random sequence of values or
    characters. The choices can be provided as a string or an array. The type of
    the return value depends on the type of choices.

### Notes about /dev/random ###

The generators provided by this library use `/dev/urandom` as the randomness
source by default. Reading from `/dev/random` provides no additional security
to typical PHP web applications and its blocking nature would make it very
unsuitable for such purposes.

There are only few legitimate cases where you should read from `/dev/random`
instead. This is mostly if you are concerned that `/dev/urandom` has not yet
been seeded properly. However, this is typically not the case with web
applications, since this tends to be issue only on system startup.

If you know that you absolutely need to read from `/dev/random` it's possible
to set the `RandomReader` and `Mcrypt` to use it as the randomness source
instead by setting the constructor parameter to false and providing the
generator to `SecureRandom` in the constructor. For example:

```php

require 'vendor/autoload.php';
$rng = new \Riimu\Kit\SecureRandom\SecureRandom(
    new \Riimu\Kit\SecureRandom\Generator\RandomReader(false)
);
```

## Available random generators ##

When `SecureRandom` is created, it will attempt to use one of the available
secure random generators, depending on which one is supported by the current
platform. Following random sources are available and they will be tried in the
following order:

  * `Generator\RandomReader` simply reads bytes from the random device
    `/dev/urandom`
  * `Generator\Mcrypt` uses `mcrypt_create_iv` to generate random bytes using
    `MCRYPT_DEV_URANDOM` as the source.
  * `Generator\OpenSSL` uses `openssl_random_pseudo_bytes` to generate random
    bytes.

There has been some debate on whether the algorithm used by OpenSSL is actually
cryptographically strong or not. However, due to lack of concrete evidence
against it and due to implications of it's strength in the PHP manual, this
library will use OpenSSL as the last fallback by default to achieve better
compatibility across different platforms.

If you wish to explicitly define the byte generator, you may provide it as the
constructor parameter for the `SecureRandom`. For example:

```php
$rng = new \Riimu\Kit\SecureRandom\SecureRandom(
    new \Riimu\Kit\SecureRandom\Generator\Mcrypt()
);
```

## Credits ##

This library is copyright 2014 - 2015 to Riikka Kalliomäki.

See LICENSE for license and copying information.
