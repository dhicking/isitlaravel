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
        $result = $this->detectorService->detect($url);

        return view('detector.results', [
            'result' => $result,
        ]);
    }
}
