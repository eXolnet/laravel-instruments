<?php namespace Exolnet\Instruments\Provider;

use Exolnet\Instruments\Instruments;
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
		$this->publishes([
			__DIR__.'/../../config/instruments.php' => config_path('instruments.php'),
		]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerInstruments();
	}

	/**
	 * @return void
	 */
	protected function registerInstruments()
	{
		$this->app->singleton('instruments', function() {
			return new Instruments();
		});
	}
}
