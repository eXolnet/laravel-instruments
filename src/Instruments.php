<?php namespace Exolnet\Instruments;

use Auth;
use Closure;
use Exception;
use Exolnet\Instruments\Drivers\Driver;
use Exolnet\Instruments\Middleware\InstrumentsMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Swift_Message;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
		if ( ! preg_match('/\s+'. preg_quote($afterKeyword) .'\s(?:\S+\.)?(\S+)/i', $query, $match)) {
			return 'null';
		}

		return trim($match[1], '`');
	}

	/**
	 * @return void
	 */
	protected function listenMail()
	{
		app('events')->listen('mailer.sending', function(Swift_Message $message) {
			$this->driver->increment('authentication.mail.sent');

			$recipients = [
				'to' => count($message->getTo()),
				'cc' => count($message->getCc()),
				'bcc' => count($message->getBcc()),
			];

			foreach ($recipients as $recipient => $recipientCount) {
				if ($recipientCount > 0) {
					$this->driver->increment('authentication.mail.recipients.'. $recipient, $recipientCount);
				}
			}
		});
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

		app('events')->listen('auth.fail', function() {
			$this->driver->increment('authentication.login.failed');
		});

		app('events')->listen('auth.logout', function() {
			$this->driver->increment('authentication.logout.succeeded');
		});
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	public function guessRequestType(Request $request)
	{
		$userAgent    = strtolower($request->header('User-Agent'));
		$contentType  = array_get($request->getAcceptableContentTypes(), 0);

		if (strpos($userAgent, 'googlebot') !== false) {
			return 'googlebot';
		} elseif ($request->ajax()) {
			return 'ajax';
		} elseif ($request->pjax()) {
			return 'pjax';
		} elseif (Str::contains($contentType, ['application/rss+xml', 'application/rdf+xml', 'application/atom+xml'])) {
			return 'feed';
		} elseif ($request->wantsJson() || Str::contains($contentType, ['application/xml', 'text/xml'])) {
			return 'api';
		} elseif ( ! $request->acceptsHtml()) {
			return 'other';
		} elseif (Auth::check()) {
			return 'user';
		}

		return 'public';
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	public function getRequestContext(Request $request)
	{
		return preg_replace('/\.{2,}/', '.', trim(implode('.', [
			$request->getScheme(),
			strtolower($request->getMethod()),
			$this->guessRequestType($request),
			str_replace('/', '.', $request->getPathInfo())
		]), '.'));
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $responseBuilder
	 * @return mixed
	 */
	public function collectResponse(Request $request, Closure $responseBuilder)
	{
		$this->collectRequest($request);

		// Collect response time
		$timeMetric = 'response.'. $this->getRequestContext($request) .'.response_time';
		$response   = $this->driver->time($timeMetric, $responseBuilder);

		// Collect response code
		$responseCode = $response instanceof Response ? $response->getStatusCode() : 200;
		$codeMetric   = 'response.'. $this->getRequestContext($request) .'.code.'. $responseCode;

		$this->driver->increment($codeMetric);

		return $response;
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	public function collectRequest(Request $request)
	{
		$requestMetric = 'request.'. $this->getRequestContext($request) .'.requested';
		$this->driver->increment($requestMetric);
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Exception $e
	 * @return void
	 */
	public function collectException(Request $request, Exception $e)
	{
		// Collect status code
		$responseCode = $e instanceof HttpException ? $e->getStatusCode() : 500;
		$codeMetric   = 'response.'. $this->getRequestContext($request) .'.code.'. $responseCode;

		$this->driver->increment($codeMetric);

		// Collection exception type
		$exceptionPath   = str_replace('\\', '.', get_class($e));
		$exceptionMetric = 'exceptions'. $this->getRequestContext($request) .'.exception.'. $exceptionPath .'thrown';

		$this->driver->increment($exceptionMetric);
	}
}
