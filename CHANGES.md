# Changelog #

## v1.1.1 (2015-01-25) ##

  * Improvements in code quality and documentation
  * Added a simple test for even distribution
  * composer.json now lists openssl and mcrypt as suggested packages instead of
    being listed as requirements

## v1.1.0 (2014-07-17) ##

  * Reading from /dev/urandom now uses buffered reads instead of custom buffer
  * Generators now throw GeneratorException instead of returning false on error
  * Made corrections to some parts of the documentation
  * Zero length sequence from empty choices now returns an empty sequence

## v1.0.0 (2014-07-10) ##

  * Initial release
