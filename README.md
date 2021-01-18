# GidxSDK

[![Build Status](https://github.com/Saritasa/php-gidx-sdk/workflows/build/badge.svg)](https://github.com/Saritasa/php-gidx-sdk/actions)
[![CodeCov](https://codecov.io/gh/Saritasa/php-gidx-sdk/branch/master/graph/badge.svg)](https://codecov.io/gh/Saritasa/php-gidx-sdk)
[![Release](https://img.shields.io/github/release/Saritasa/php-gidx-sdk.svg)](https://github.com/Saritasa/php-gidx-sdk/releases)
[![PHPv](https://img.shields.io/packagist/php-v/saritasa/gidx-sdk.svg)](http://www.php.net)
[![Downloads](https://img.shields.io/packagist/dt/saritasa/gidx-sdk.svg)](https://packagist.org/packages/saritasa/gidx-sdk)

PHP Wrapper for API of [GIDX plagform](http://www.tsevo.com/Docs/Integration).
Intended to use with applications basedo on Laravel 6.x+ framework

## Usage

Install the ```saritasa/gidx-sdk``` package:

```bash
$ composer require saritasa/gidx-sdk
```

Copy config to application: 
```bash
$ artisan vendor:publish --provider='GidxSDK\GidxServiceProvider' --tag=config
```


## Contributing
See [CONTRIBUTING](CONTRIBUTING.md) and [Code of Conduct](CONDUCT.md),
if you want to make contribution (pull request)
or just build and test project on your own.

## Resources

* [Changes History](CHANGES.md)
* [Bug Tracker](https://github.com/Saritasa/php-gidx-sdk/issues)
* [Authors](https://github.com/Saritasa/php-gidx-sdk/contributors)
