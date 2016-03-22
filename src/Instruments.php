<?php namespace Exolnet\Instruments;

use Closure;
use Exolnet\Instruments\Drivers\Driver;
use Exolnet\Instruments\Middleware\InstrumentsMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

class Instruments
{
	/**
	 * @var \Exolnet\Instruments\Drivers\Driver
	 */
	private $driver;

	/**
	 * Instruments constructor.
	 *
	 * @param \Exolnet\Instruments\Drivers\Driver $driver
	 */
	public function __construct(Driver $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * @return \Exolnet\Instruments\Drivers\Driver
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * @return void
	 */
	public function boot()
	{
		$this->listenHttp();
		$this->listenDatabase();
		$this->listenMail();
		$this->listenAuth();
	}

	/**
	 * @return void
	 */
	protected function listenHttp()
	{
		/** @var \Illuminate\Foundation\Http\Kernel $kernel */
		$kernel = app(Kernel::class);

		$kernel->pushMiddleware(InstrumentsMiddleware::class);
	}

	/**
	 * @return void
	 */
	protected function listenDatabase()
	{
		app('events')->listen('illuminate.query', function($query, $bindings, $time, $connection) {
			//$this->getInstruments()->increment('query');
		});
	}

	/**
	 * @return void
	 */
	protected function listenMail()
	{
		//$this->events->listen('mailer.sending', function($message) {
			//$this->getInstruments()->increment('mail.sent.sent');
		//});
	}

	/**
	 * @return void
	 */
	protected function listenAuth()
	{
		app('events')->listen('auth.attempt', function() {
			$this->driver->increment('authentication.login.attempted');
		});

		app('events')->listen('auth.login', function() {
			$this->driver->increment('authentication.login.succeeded');
		});

		app('events')->listen('auth.logout', function() {
			$this->driver->increment('authentication.logout.succeeded');
		});
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	public function getRequestContext(Request $request)
	{
		return implode('.', [
			$request->getScheme(),
			strtolower($request->getMethod()),
			str_replace('/', '.', trim($request->getPathInfo(), '/'))
		]);
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $responseBuilder
	 * @return mixed
	 */
	public function collectResponse(Request $request, Closure $responseBuilder)
	{
		$this->collectRequest($request);

		$timeMetric = $this->getRequestContext($request) .'.response_time';
		$response   = $this->driver->time($timeMetric, $responseBuilder);

		// Collect additional data (code and size)

		return $response;
	}

	public function collectRequest(Request $request)
	{
		// Collect additional request data
	}

	public function collectException($e)
	{
		// Collect exception data
	}
}
