<?php namespace Exolnet\Instruments\Controllers;

use Cache;
use Exolnet\Instruments\Instruments;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Session;
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

		$sessionRequest = Session::get('instruments.request');

		if ( ! is_array($sessionRequest) || $requestId !== $sessionRequest['id']) {
			throw new NotFoundHttpException;
		}

		Session::remove('instruments.request');

		$this->instruments->collectBrowserStats($sessionRequest['context'], $timing);

		return response()->make();
	}
}
