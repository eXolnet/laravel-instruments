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
			$query = trim($query);

			if (preg_match('/^(SELECT|UPDATE|DELETE)/i', $query, $match)) {
				$type  = strtolower($match[1]);
				$table = $this->extractTableName($query, 'FROM');
			} elseif (preg_match('/^(INSERT|REPLACE)/i', $query, $match)) {
				$type  = strtolower($match[1]);
				$table = $this->extractTableName($query, 'INTO');
			} else {
				$type  = 'questions';
				$table = 'null';
			}

			$this->driver->timing('sql.'. $connection .'.'. $table .'.'. $type .'.query_time', $time);
		});
	}

	/**
	 * @param string $query
	 * @param string $afterKeyword
	 * @return string
	 */
	protected function extractTableName($query, $afterKeyword)
	{
		if ( ! preg_match('/\s+'. preg_quote($afterKeyword) .'\s(\S+)/i', $query, $match)) {
			return 'null';
		}

		return trim($match[1], '`');
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
			$this->driver->increment('authentication.login.attempted.count');
		});

		app('events')->listen('auth.login', function() {
			$this->driver->increment('authentication.login.succeeded.count');
		});

		app('events')->listen('auth.logout', function() {
			$this->driver->increment('authentication.logout.succeeded.count');
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
