<?php namespace Exolnet\Instruments\Controllers;

use Cache;
use Exolnet\Instruments\Instruments;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BrowserStatsController extends Controller
{
	/**
	 * @var \Exolnet\Instruments\Instruments
	 */
	private $instruments;

	/**
	 * BrowserStatsController constructor.
	 *
	 * @param \Exolnet\Instruments\Instruments $instruments
	 */
	public function __construct(Instruments $instruments)
	{
		$this->instruments = $instruments;
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$requestId = $request->get('requestId');
		$timing    = $request->get('timing');

		if ( ! $requestId || ! is_array($timing)) {
			throw new NotFoundHttpException;
		}

		$requestContext = Cache::pull('instruments.requests.'. $requestId);

		if ( ! $requestContext) {
			throw new NotFoundHttpException;
		}

		$this->instruments->collectBrowserStats($requestContext, $timing);

		return response()->make();
	}
}
