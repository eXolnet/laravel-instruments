# Laravel Instruments

[![Latest Stable Version](https://poser.pugx.org/eXolnet/laravel-instruments/v/stable?format=flat-square)](https://packagist.org/packages/eXolnet/laravel-instruments)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/eXolnet/laravel-instruments/tests.yml?label=tests&style=flat-square)](https://github.com/eXolnet/laravel-instruments/actions?query=workflow%3Atests)
[![Total Downloads](https://img.shields.io/packagist/dt/eXolnet/laravel-instruments.svg?style=flat-square)](https://packagist.org/packages/eXolnet/laravel-instruments)

This project aim to easily add metric tracking to your Laravel 5 applications. Three drivers are currently supported: StatsD, Log and Null. The following metrics are currently tracked:

* Request
* Response
* Browser timing (first byte, ready and load)
* SQL Queries
* Authentifications
* Mail
* Queue
* Cache

## Installation

Require this package with composer:

```
composer require eXolnet/laravel-instruments
```

After updating composer, add the ServiceProvider to the providers array in `config/app.php`:

```
Exolnet\Instruments\InstrumentsServiceProvider::class
```

Configure the library through your `.env` file:

```
INSTRUMENTS_DRIVER=statsd
STATSD_HOST=127.0.0.1
STATSD_PORT=8125
```

Or publish the package configuration with the following command:

```
$ php artisan vendor:publish --provider="Exolnet\Instruments\InstrumentsServiceProvider"
```


## Testing

To run the phpUnit tests, please use:

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE OF CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email security@exolnet.com instead of using the issue tracker.

## Credits

- [Alexandre D'Eschambeault](https://github.com/xel1045)
- [All Contributors](../../contributors)

## License

This code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). Please see the [license file](LICENSE) for more information.
