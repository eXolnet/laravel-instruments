<?php namespace Exolnet\Instruments\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;

class InstrumentsMiddleware
{
	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 * @throws \Exception
	 */
	public function handle(Request $request, Closure $next)
	{
		/** @var \Exolnet\Instruments\Instruments $instruments */
		$instruments = app('instruments');

		try {
			return $instruments->collectResponse($request, function() use ($request, $next) {
				return $next($request);
			});
		} catch (Exception $e) {
			$instruments->collectException($e);

			throw $e;
		}
	}
}
