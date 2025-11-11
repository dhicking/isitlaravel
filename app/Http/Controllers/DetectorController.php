<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetectLaravelRequest;
use App\Services\LaravelDetectorService;
use Illuminate\View\View;

class DetectorController extends Controller
{
    public function __construct(
        private LaravelDetectorService $detectorService
    ) {}

    public function index(): View
    {
        return view('detector.index');
    }

    public function detect(DetectLaravelRequest $request): View
    {
        $url = $request->input('url');
        $forceRefresh = $request->boolean('refresh', false);
        $result = $this->detectorService->detect($url, $forceRefresh);

        return view('detector.results', [
            'result' => $result,
        ]);
    }
}
