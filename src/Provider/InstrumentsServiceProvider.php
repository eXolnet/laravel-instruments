<?php namespace Exolnet\Instruments\Provider;

use Exolnet\Instruments\Instruments;
use Exolnet\Instruments\Middleware\InstrumentsMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class InstrumentsServiceProvider extends ServiceProvider
{
	/**
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected $events;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../../config/instruments.php' => config_path('instruments.php'),
		]);

		/** @var \Illuminate\Contracts\Events\Dispatcher $events */
		$this->events = $this->app['events'];

		$this->listenHttp();
		$this->listenDatabase();
		$this->listenMail();
		$this->listenAuth();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	/**
	 * @return \League\StatsD\Client
	 */
	protected function getClient()
	{
		return $this->app['statsd'];
	}

	/**
	 * @return void
	 */
	protected function listenHttp()
	{
		/** @var \Illuminate\Foundation\Http\Kernel $kernel */
		$kernel = $this->app[Kernel::class];

		$kernel->pushMiddleware(InstrumentsMiddleware::class);
	}

	/**
	 * @return void
	 */
	protected function listenDatabase()
	{
		$this->events->listen('illuminate.query', function($query, $bindings, $time, $connection) {
			//$this->getClient()->increment('mailer.sending');
		});
	}

	/**
	 * @return void
	 */
	protected function listenMail()
	{
		$this->events->listen('mailer.sending', function($message) {
			//$this->getClient()->increment('mailer.sending');
		});
	}

	/**
	 * @return void
	 */
	protected function listenAuth()
	{
		foreach (['auth.attempt', 'auth.login', 'auth.logout', 'register'] as $eventName) {
			$this->events->listen($eventName, function () use ($eventName) {
				$this->getClient()->increment($eventName);
			});
		}
	}
}
