<?php namespace Exolnet\Instruments;

use Auth;
use Cache;
use Closure;
use Exception;
use Exolnet\Instruments\Drivers\Driver;
use Exolnet\Instruments\Middleware\InstrumentsMiddleware;
use Illuminate\Container\Container;
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
	 * @var \Illuminate\Container\Container
	 */
	private $app;

	/**
	 * Instruments constructor.
	 *
	 * @param \Illuminate\Container\Container $app
	 */
	public function __construct(Container $app)
	{
		$this->app = $app;
		$this->driver = $app['instruments.driver'];
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
		$this->listenCache();
		$this->listenQueue();
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
			$this->driver->increment('mail.send.count');

			$recipients = [
				'to' => count($message->getTo()),
				'cc' => count($message->getCc()),
				'bcc' => count($message->getBcc()),
			];

			foreach ($recipients as $recipient => $recipientCount) {
				if ($recipientCount > 0) {
					$this->driver->increment('mail.recipients.'. $recipient .'.count', $recipientCount);
				}
			}
		});
	}

	/**
	 * @return void
	 */
	protected function listenAuth()
	{
		$events = app('events');

		$events->listen('auth.attempt', function() {
			$this->driver->increment('authentication.login.attempt.count');
		});

		$events->listen('auth.login', function() {
			$this->driver->increment('authentication.login.success.count');
		});

		$events->listen('auth.fail', function() {
			$this->driver->increment('authentication.login.fail.count');
		});

		$events->listen('auth.logout', function() {
			$this->driver->increment('authentication.logout.success.count');
		});
	}

	/**
	 * @return void
	 */
	protected function listenCache()
	{
		$events = app('events');

		$events->listen('cache.write', function($key) {
			$this->driver->increment('cache.write.'. $this->quotePath($key) .'.count');
		});

		$events->listen('cache.delete', function($key) {
			$this->driver->increment('cache.delete.'. $this->quotePath($key) .'.count');
		});

		$events->listen('cache.hit', function($key) {
			$this->driver->increment('cache.hit.'. $this->quotePath($key) .'.count');
		});

		$events->listen('cache.missed', function($key) {
			$this->driver->increment('cache.missed.'. $this->quotePath($key) .'.count');
		});
	}

	/**
	 * @return void
	 */
	protected function listenQueue()
	{
		$events = app('events');

		$queueId = $this->quotePath(uniqid());

		$events->listen('illuminate.queue.looping', function() use ($queueId) {
			$this->driver->increment('queue.worker.'. $queueId .'.loop.count');
		});

		$events->listen('illuminate.queue.after', function($connection, $job) {
			$this->driver->increment('queue.job.'. $connection .'.'. $this->quotePath(get_class($job)) .'.success.count');
		});

		$events->listen('illuminate.queue.failed', function($connection, $job) {
			$this->driver->increment('queue.job.'. $connection .'.'. $this->quotePath(get_class($job)) .'.fail.count');
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
		} elseif ($contentType === null) {
			return 'raw';
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
		return implode('.', [
			$request->getScheme(),
			strtolower($request->getMethod()),
			$this->guessRequestType($request),
			'_'. $this->quotePath($request->getPathInfo()),
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

		// Collect response time
		$timeMetric = 'response.'. $this->getRequestContext($request) .'.response_time';
		$response   = $this->driver->time($timeMetric, $responseBuilder);

		// Collect response code
		$responseCode = $response instanceof Response ? $response->getStatusCode() : 200;
		$codeMetric   = 'response.'. $this->getRequestContext($request) .'.'. $responseCode .'.count';

		$this->driver->increment($codeMetric);

		$shouldInjectStatsCollector = $response->headers->has('Content-Type')
			&& strpos($response->headers->get('Content-Type'), 'html') !== false
			&& strpos($response->getContent(), '</head>') !== false;

		if ($shouldInjectStatsCollector) {
			$this->injectStatsCollector($request, $response);
		}

		return $response;
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	public function collectRequest(Request $request)
	{
		$requestMetric = 'request.'. $this->getRequestContext($request) .'.count';
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
		$codeMetric   = 'response.'. $this->getRequestContext($request) .'.'. $responseCode .'.count';

		$this->driver->increment($codeMetric);

		// Collection exception type
		$exceptionPath   = $this->quotePath(get_class($e));
		$exceptionMetric = 'exceptions.'. $this->getRequestContext($request) .'.exception.'. $exceptionPath .'.count';

		$this->driver->increment($exceptionMetric);
	}

	/**
	 * @param $requestContext
	 * @param array $timing
	 * @return void
	 */
	public function collectBrowserStats($requestContext, array $timing)
	{
		if ( ! isset($timing['navigationStart'])) {
			return;
		}

		$metrics = [
			'first_byte' => 'responseStart',
			'ready'      => 'domContentLoadedEventStart',
			'load'        => 'loadEventEnd',
		];

		foreach ($metrics as $metric => $event) {
			if ( ! isset($timing[$event]) || $timing[$event] < $timing['navigationStart']) {
				continue;
			}

			$time = ($timing[$event] - $timing['navigationStart']) / 1000;
			$this->driver->timing('response.' . $requestContext . '.'. $metric .'_time', $time);
		}
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 */
	public function injectStatsCollector(Request $request, Response $response)
	{
		$content      = $response->getContent();
		$headPosition = strpos($content, '</head>');

		if ($headPosition === false) {
			return;
		}

		$requestId      = md5(uniqid());
		$requestContext = $this->getRequestContext($request);

		Cache::put('instruments.requests.'. $requestId, $requestContext, 1);

		$statsCollectorHtml = str_replace(["\n", "\r", "\t"], '', '<script>
			if(window.addEventListener && window.fetch && window.performance) {
				window.addEventListener("load", function() {
					setTimeout(function() {
						window.fetch("'. route('instruments.browser.stats.store') .'", {
							method: "post",
							body: JSON.stringify({
								requestId: "'. $requestId .'",
								timing: window.performance.timing
							}),
							headers: {
								"Content-Type": "application/json"
							}
						});
					}, 500);
				});
			}
		</script>');

		// Build the content with our stats collector
		$content = substr($content, 0, $headPosition) . $statsCollectorHtml . substr($content, $headPosition);

		$response->setContent($content);
	}

	/**
	 * @param string $value
	 * @param bool $trimUnderscores
	 * @return string
	 */
	public function quotePath($value, $trimUnderscores = true)
	{
		$value = str_replace(['\\', '/', '.'], '_', $value);

		if ($trimUnderscores) {
			$value = trim($value, '_');
		}

		return $value;
	}
}
