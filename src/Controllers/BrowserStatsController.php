<?php

namespace Exolnet\Instruments\Controllers;

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

        if (! $requestId || ! is_array($timing)) {
            return response()->make('', 404);
        }

        $sessionRequest = Session::get('instruments.request');

        if (! is_array($sessionRequest) || $requestId !== $sessionRequest['id']) {
            return response()->make('', 404);
        }

        Session::remove('instruments.request');

        $this->instruments->collectBrowserStats($sessionRequest['context'], $timing);

        return response()->make();
    }
}
