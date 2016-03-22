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

	'options' => [
		'host' => env('INSTRUMENTS_HOST', '127.0.0.1'),

		'post' => env('INSTRUMENTS_PORT', 8125),

		'timeout' => 1,

		'throwConnectionExceptions' => env('APP_DEBUG'),
	],
];
