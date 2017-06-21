<?php namespace Exolnet\Instruments;

use Illuminate\Support\ServiceProvider;

class InstrumentsServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->setupConfig();
		$this->setupRoutes();

		$this->app['instruments']->boot();
	}

	/**
	 * @return void
	 */
	protected function setupConfig()
	{
		$source = realpath(__DIR__ . '/../config/instruments.php');

		$this->publishes([
			$source => config_path('instruments.php')
		]);

		$this->mergeConfigFrom($source, 'instruments');
	}

	/**
	 * @return void
	 */
	protected function setupRoutes()
	{
		$routeConfig = [
			'namespace' => 'Exolnet\\Instruments\\Controllers',
			'prefix' => '_private'
		];

		$this->app['router']->group($routeConfig, function($router) {
			$router->post('browser/stats', [
				'uses'       => 'BrowserStatsController@store',
				'as'         => 'instruments.browser.stats.store',
				'middleware' => 'web',
			]);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/../config/instruments.php', 'instruments');

		$this->app->singleton('instruments.factory', function($app) {
			return new InstrumentsManager($app);
		});

		$this->app->singleton('instruments.driver', function ($app) {
			return $app['instruments.factory']->driver();
		});

		$this->app->singleton('instruments', function ($app) {
			return new Instruments($app);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['instruments', 'instruments.factory', 'instruments.store'];
	}
}
