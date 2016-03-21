<?php namespace Exolnet\Instruments\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;

class InstrumentsMiddleware
{
	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\Response
	 * @throws \Exception
	 */
	public function handle(Request $request, Closure $next)
	{
		// Collect request stats
		$this->collectRequest($request);

		try {
			/** @var \Illuminate\Http\Response $response */
			$response = $next($request);
		} catch (Exception $e) {
			// Collect exception stats
			$this->collectException($e);

			throw $e;
		}

		// Collect response stats
		$this->collectResponse($response);

		return $response;
	}

	private function collectRequest(Request $request)
	{
		// Request HTTP Verb
	}

	private function collectResponse($response)
	{
		// Time, code and size
	}

	private function collectException($e)
	{
	}
}
