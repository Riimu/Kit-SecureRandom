# Secure Random Generator #

This library has two main purposes:

  * to provide a common interface for different secure random generators
    available on the system
  * to normalize the bytes returned by the secure random generator for most
    common use cases, such as generating integers, floats or randomizing arrays

This library does not provide any additional secure random generators than what
is available in PHP extensions. The available sources for secure random bytes
in PHP are reading from `/dev/(u)random`, using `mcrypt_create_iv()` or calling
`openssl_random_pseudo_bytes()`.

The security of the random numbers generated by this library is entirely
dependant on the underlying random generator. No additional transformations or
modifications are done on the bytes returned by the random generator (other than
what is required to normalize it for the use case).

The idea is to simply provide a simple interface for different secure random
generators to make it easier to provide support for different kinds of
platforms.

[![Coverage Status](https://coveralls.io/repos/Riimu/Kit-SecureRandom/badge.png)](https://coveralls.io/r/Riimu/Kit-SecureRandom)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Riimu/Kit-SecureRandom/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Riimu/Kit-SecureRandom/?branch=master)

## Installation ##

This library can be easily installed using [Composer](http://getcomposer.org/)
by including the following dependency in your `composer.json`:

```json
{
    "require": {
        "riimu/kit-securerandom": "1.*"
    }
}
```

The library will be the installed by running `composer install` and the classes
can be loaded with simply including the `vendor/autoload.php` file.

## Usage ##

Usage of the library is very simple. Simply create an instance of the
`SecureRandom` and call any of the methods to get random values. For example:

```php
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
  * `getArray(array $array, $count)` returns $count random elements from the
    given array. The elements are in random order, but the keys are preserved.
  * `choose(array $array)` returns a random value chosen from the array
  * `shuffle(array $array)` returns the array in random order. The keys are
    preserved.
  * `getSequence($choices, $length)` returns a random sequence of length
    $length. The $choices can be a string, in which case a string is returned
    or an array, in which case an array is returned.

### Using /dev/random ###

Since most web applications require non blocking random generators, the
`SecureRandom` will attempt to use the available random generators using
`/dev/urandom` as the randomness source. However, if you do not consider this
to be secure enough for your purposes, you may also create a random generator
that uses `/dev/random` as the randomness source.

Both `Mcrypt` and `RandomReader` generators accept a boolean argument for the
constructor, which determines which randomness source they use. They default to
'true' which uses `/dev/urandom`. The generator can be provided as a
constructor argument for the `SecureRandom`. For example:

```php
$rng = new \Riimu\Kit\SecureRandom\SecureRandom(
    new \Riimu\Kit\SecureRandom\Generator\Mcrypt(false)
);

var_dump($rng->getFloat());
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

By extending the `Gererator\Generator` interface, you can easily also create
your own randomness sources, if you have other source available than the ones
listed above. The generator can be injected into the `SecureRandom` via the
constructor argument.

## Credits ##

This library is copyright 2014 to Riikka Kalliomäki
