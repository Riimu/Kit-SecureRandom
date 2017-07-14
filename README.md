# Secure Random Generator #

*SecureRandom* is a PHP library for generating secure random numbers and using
them for common randomization operations such as shuffling arrays or generating
string sequences like passwords. Prior to version 7.0, PHP did not have built in
secure random functions, but it was still possible to use different sources of
randomness for generating secure random values. Thus, this library has two main
purposes:

  * To provide a common interface for different sources of secure randomness
    across different platforms and PHP versions
  * To make it easier to properly use the sources of randomness to generate
    random values and to perform common random array operations.

This library does not provide any additional secure random byte generators. It
simply uses the byte generators that are available to PHP via extensions or
internally. The four generators that are commonly available to PHP are:

  * CSPRNG functions provided by PHP 7.0
  * reading from the random device `/dev/(u)random`
  * calling the `mcrypt_create_iv()` function
  * calling the `openssl_random_pseudo_bytes()` function

The security of the randomness generated by this library is entirely dependant
on the underlying random byte generator. The library does not do any additional
transformations on the bytes other than the normalization needed to generate
even distributions of random numbers.

The API documentation, which can be generated using Apigen, can be read online
at: http://kit.riimu.net/api/securerandom/

[![Travis](https://img.shields.io/travis/Riimu/Kit-SecureRandom.svg?style=flat-square)](https://travis-ci.org/Riimu/Kit-SecureRandom)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/Riimu/Kit-SecureRandom.svg?style=flat-square)](https://scrutinizer-ci.com/g/Riimu/Kit-SecureRandom/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/Riimu/Kit-SecureRandom.svg?style=flat-square)](https://scrutinizer-ci.com/g/Riimu/Kit-SecureRandom/)
[![Packagist](https://img.shields.io/packagist/v/riimu/kit-securerandom.svg?style=flat-square)](https://packagist.org/packages/riimu/kit-securerandom)

## Requirements ##

In order to use this library, the following requirements must be met:

  * PHP version 5.6
  * If using PHP version prior to 7.0, one of the following must be available:
    * `/dev/urandom` must be readable
    * `Mcrypt` extension must be enabled
    * `OpenSSL` extension must be enabled

## Installation ##

This library can be installed by using [Composer](http://getcomposer.org/). In
order to do this, you must download the latest Composer version and run the
`require` command to add this library as a dependency to your project. The
easiest way to complete these two tasks is to run the following two commands
in your terminal:

```
php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar require "riimu/kit-securerandom:1.*"
```

If you already have Composer installed on your system and you know how to use
it, you can also install this library by adding it as a dependency to your
`composer.json` file and running the `composer install` command. Here is an
example of what your `composer.json` file could look like:

```json
{
    "require": {
        "riimu/kit-securerandom": "1.*"
    }
}
```

After installing this library via Composer, you can load the library by
including the `vendor/autoload.php` file that was generated by Composer during
the installation.

### Manual installation ###

You can also install this library manually without using Composer. In order to
do this, you must download the [latest release](https://github.com/Riimu/Kit-SecureRandom/releases/latest)
and extract the `src` folder from the archive to your project folder. To load
the library, you can simply include the `src/autoload.php` file that was
provided in the archive.

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
<?php

require 'vendor/autoload.php';
$generator = new \Riimu\Kit\SecureRandom\Generator\RandomReader(false);
$rng = new \Riimu\Kit\SecureRandom\SecureRandom($generator);
```

## Available random generators ##

When `SecureRandom` is created, it will attempt to use one of the available
secure random generators, depending on which one is supported by the current
platform. Following random sources are available and they will be tried in the
following order:

  * `Generator\Internal` uses the internal functions available in PHP 7.0
  * `Generator\RandomReader` simply reads bytes from the random device
    `/dev/urandom`
  * `Generator\Mcrypt` uses `mcrypt_create_iv()` to generate random bytes using
    `MCRYPT_DEV_URANDOM` as the source.
  * `Generator\OpenSSL` uses `openssl_random_pseudo_bytes()` to generate random
    bytes.

There has been some debate on whether the algorithm used by OpenSSL is actually
cryptographically strong or not. However, due to lack of concrete evidence
against it and due to implications of it's strength in the PHP manual, this
library will use OpenSSL as the last fallback by default to achieve better
compatibility across different platforms.

If you wish to explicitly define the byte generator, you may provide it as the
constructor parameter for the `SecureRandom`. For example:

```php
<?php

require 'vendor/autoload.php';
$rng = new \Riimu\Kit\SecureRandom\SecureRandom(
    new \Riimu\Kit\SecureRandom\Generator\Mcrypt()
);
```

## Credits ##

This library is Copyright (c) 2014-2017 Riikka Kalliomäki.

See LICENSE for license and copying information.
