<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Instruments Driver
	|--------------------------------------------------------------------------
	|
	| Supported: "statsd", "log" and "null".
	|
	*/

	'driver' => env('INSTRUMENTS_DRIVER', 'null'),

	'application' => null,

	'listeners' => ['http', 'database', 'mail', 'auth', 'cache', 'queue'],

	'statsd' => [
		'host' => env('STATSD_HOST', '127.0.0.1'),

		'port' => env('STATSD_PORT', 8125),

		'timeout' => null,

		'throwConnectionExceptions' => env('APP_DEBUG'),

		'stripTagKeys' => true,
	],
];
