<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetectLaravelRequest;
use App\Rules\SafeUrl;
use App\Services\LaravelDetectorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DetectorController extends Controller
{
    public function __construct(
        private LaravelDetectorService $detectorService
    ) {}

    public function index(Request $request): View
    {
        // Pre-populate form with URL from query parameter if provided
        $url = $request->query('url');

        return view('detector.index', [
            'prefillUrl' => $url,
        ]);
    }

    public function detect(DetectLaravelRequest $request): RedirectResponse
    {
        $url = $request->input('url');
        $forceRefresh = $request->boolean('refresh', false);

        // Run detection
        $result = $this->detectorService->detect($url, $forceRefresh);

        // Extract domain for cleaner shareable URL (no protocol)
        $domain = parse_url($result['url'], PHP_URL_HOST) ?: str_replace(['https://', 'http://'], '', $url);

        // Redirect to results page with domain as query parameter
        return redirect()->route('results', [
            'url' => $domain,
            'refresh' => $forceRefresh ? '1' : null,
        ])->with('detection_result', $result);
    }

    public function results(Request $request): View
    {
        $url = $request->query('url');
        $forceRefresh = $request->boolean('refresh', false);

        // If URL is missing, redirect to home
        if (! $url) {
            return redirect()->route('home');
        }

        // Validate URL using SafeUrl rule
        $validator = validator(['url' => $url], [
            'url' => ['required', 'string', 'max:500', new SafeUrl],
        ]);

        if ($validator->fails()) {
            // If validation fails, redirect to home with error
            return redirect()->route('home')
                ->withErrors($validator)
                ->withInput(['url' => $url]);
        }

        // Check if we have a cached result from the POST redirect
        $result = $request->session()->get('detection_result');

        // If no cached result or forcing refresh, run detection
        if (! $result || $forceRefresh) {
            $result = $this->detectorService->detect($url, $forceRefresh);
        }

        return view('detector.results', [
            'result' => $result,
        ]);
    }
}
