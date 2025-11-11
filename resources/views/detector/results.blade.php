@extends('layouts.app')

@section('title', 'Detection Results - Is It Laravel?')

@section('content')
<div class="min-h-screen bg-white px-4 py-8">
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-laravel-red focus:text-white focus:rounded-lg focus:ring-4 focus:ring-red-500 focus:ring-offset-2">
        Skip to main content
    </a>
    
    <!-- Back Link -->
    <div class="max-w-5xl mx-auto mb-6">
        <a href="{{ route('home') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded transition-colors outline-none">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </a>
    </div>

    <main id="main-content" class="max-w-5xl mx-auto">
        @if(!$result['success'])
            <!-- Error State -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12" role="alert">
                <div class="text-center max-w-lg mx-auto">
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4" aria-hidden="true">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Error Analyzing Website</h2>
                    <p class="text-gray-600 mb-1">{{ $result['error'] }}</p>
                    <p class="text-sm text-gray-500 mb-6">URL: <span class="break-all">{{ $result['url'] }}</span></p>
                    <a href="{{ route('home') }}" class="bg-laravel-red hover:bg-red-600 focus:ring-4 focus:ring-red-500 focus:ring-offset-2 text-white font-semibold px-6 py-3 rounded-lg transition-all inline-block outline-none">
                        Try Another URL
                    </a>
                </div>
            </div>
        @else
            <!-- Results Header -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 mb-6">
                <div class="text-center">
                    <div class="text-6xl mb-4" role="img" aria-label="Confidence level: {{ $result['confidence']['message'] }}">{{ $result['confidence']['emoji'] }}</div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        {{ $result['confidence']['message'] }}
                    </h1>
                    <div class="flex items-center justify-center gap-2 mb-6">
                        @if(!empty($result['faviconUrl']))
                            <img 
                                src="{{ $result['faviconUrl'] }}" 
                                alt="" 
                                class="w-5 h-5 flex-shrink-0"
                                onerror="this.style.display='none'"
                                aria-hidden="true"
                            >
                        @endif
                        <p class="text-gray-600 break-all">{{ $result['url'] }}</p>
                    </div>

                    <!-- Score Bar -->
                    <div class="max-w-md mx-auto">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-medium text-gray-700">Detection Score</span>
                            <span class="font-bold text-gray-900" aria-label="Score: {{ $result['score'] }} out of {{ $result['totalIndicators'] }} indicators">{{ $result['score'] }} / {{ $result['totalIndicators'] }}</span>
                        </div>
                        <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $result['percentage'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Detection confidence: {{ $result['percentage'] }} percent">
                            <div 
                                class="h-full bg-laravel-red transition-all duration-1000"
                                style="width: {{ $result['percentage'] }}%"
                            ></div>
                        </div>
                        <div class="text-center mt-2 text-sm text-gray-600 font-medium">
                            {{ $result['percentage'] }}% confidence
                        </div>
                    </div>
                </div>
            </div>

            <!-- Special Features Found -->
            @if($result['inertiaComponent'] || $result['livewireCount'] > 0 || !empty($result['detectedTools']))
                <div class="grid gap-4 mb-6">
                    @if($result['inertiaComponent'])
                        <div class="bg-white rounded-xl border border-blue-200 p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0" aria-hidden="true">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm mb-1">Inertia.js Component Detected</div>
                                    <code class="text-xs bg-blue-50 text-blue-800 px-2 py-1 rounded">{{ $result['inertiaComponent'] }}</code>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($result['livewireCount'] > 0)
                        <div class="bg-white rounded-xl border border-purple-200 p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0" aria-hidden="true">
                                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M13 2.05v3.03c3.39.49 6 3.39 6 6.92 0 .9-.18 1.75-.48 2.54l2.6 1.53c.56-1.24.88-2.62.88-4.07 0-5.18-3.95-9.45-9-9.95zM12 19c-3.87 0-7-3.13-7-7 0-3.53 2.61-6.43 6-6.92V2.05c-5.06.5-9 4.76-9 9.95 0 5.52 4.47 10 9.99 10 3.31 0 6.24-1.61 8.06-4.09l-2.6-1.53C16.17 17.98 14.21 19 12 19z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm mb-1">Livewire Components Detected</div>
                                    <span class="text-xs text-purple-700">{{ $result['livewireCount'] }} component{{ $result['livewireCount'] !== 1 ? 's' : '' }} found on page</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!empty($result['detectedTools']))
                        <div class="bg-white rounded-xl border border-orange-200 p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0" aria-hidden="true">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm mb-2">Laravel Tools Detected</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($result['detectedTools'] as $tool)
                                            <span class="text-xs bg-orange-50 text-orange-800 px-3 py-1 rounded-full font-medium">{{ $tool }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Detection Indicators -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Detection Indicators</h3>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Core Laravel -->
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm mb-3 flex items-center gap-2">
                            <span class="text-lg" aria-hidden="true">📦</span>
                            Core Laravel
                        </h4>
                        <div class="space-y-2">
                            @php
                                $coreIndicators = [
                                    ['XSRF-TOKEN cookie', $result['indicators']['xsrfToken']],
                                    ['laravel_session cookie', $result['indicators']['laravelSession']],
                                    ['CSRF meta tag', $result['indicators']['csrfMeta']],
                                    ['_token input', $result['indicators']['tokenInput']],
                                    ['X-Powered-By header', $result['indicators']['poweredByHeader']],
                                ];
                            @endphp
                            
                            @foreach($coreIndicators as [$label, $found])
                                <div class="flex items-center text-sm px-3 py-2 rounded-lg {{ $found ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-500' }}" role="listitem">
                                    <span class="mr-2" aria-hidden="true">{{ $found ? '✓' : '✗' }}</span>
                                    <span class="{{ $found ? 'font-medium' : '' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Laravel Ecosystem -->
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm mb-3 flex items-center gap-2">
                            <span class="text-lg" aria-hidden="true">🌐</span>
                            Laravel Ecosystem
                        </h4>
                        <div class="space-y-2">
                            @php
                                $ecosystemIndicators = [
                                    ['Vite assets', $result['indicators']['viteClient']],
                                    ['Mix manifest', $result['indicators']['mixManifest']],
                                    ['Inertia.js', $result['indicators']['inertia']],
                                    ['Livewire', $result['indicators']['livewire']],
                                    ['Laravel Echo', $result['indicators']['laravelEcho']],
                                    ['Blade comments', $result['indicators']['bladeComments']],
                                    ['Breeze / Jetstream layout', $result['indicators']['breezeJetstream']],
                                ];
                            @endphp
                            
                            @foreach($ecosystemIndicators as [$label, $found])
                                <div class="flex items-center text-sm px-3 py-2 rounded-lg {{ $found ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-500' }}" role="listitem">
                                    <span class="mr-2" aria-hidden="true">{{ $found ? '✓' : '✗' }}</span>
                                    <span class="{{ $found ? 'font-medium' : '' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Platform & Endpoints -->
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm mb-3 flex items-center gap-2">
                            <span class="text-lg" aria-hidden="true">🛠️</span>
                            Platform & Endpoints
                        </h4>
                        <div class="space-y-2">
                            @php
                                $platformIndicators = [
                                    ['Laravel 404 page', $result['indicators']['laravel404']],
                                    ['/up health check', $result['indicators']['upEndpoint']],
                                    ['Telescope / Horizon / Nova / Pulse', $result['indicators']['laravelTools']],
                                    ['Filament', $result['indicators']['filament']],
                                    ['Statamic', $result['indicators']['statamic']],
                                ];
                            @endphp
                            
                            @foreach($platformIndicators as [$label, $found])
                                <div class="flex items-center text-sm px-3 py-2 rounded-lg {{ $found ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-500' }}" role="listitem">
                                    <span class="mr-2" aria-hidden="true">{{ $found ? '✓' : '✗' }}</span>
                                    <span class="{{ $found ? 'font-medium' : '' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-6 mb-6">
                <div class="flex gap-3">
                    <div class="flex-shrink-0" aria-hidden="true">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 text-sm mb-2">About This Detection</h3>
                        <p class="text-gray-700 text-sm leading-relaxed mb-2">
                            This tool analyzes various indicators to detect if a website is built with Laravel. 
                            We check for Laravel-specific cookies, CSRF tokens, build tools like Vite, modern 
                            frameworks like Inertia.js and Livewire, Laravel tools (Telescope, Horizon, Nova, Pulse), 
                            admin panels (Filament, Statamic), and the <code class="bg-blue-100 px-1 rounded">/up</code> health check endpoint.
                        </p>
                        <p class="text-gray-700 text-sm leading-relaxed">
                            <strong>Note:</strong> Some Laravel sites may use custom configurations that hide these indicators, 
                            which could result in false negatives. A low score doesn't always mean the site isn't Laravel.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Share Result -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">Share Your Result</h3>
                
                @php
                    // Generate confidence meter using emoji blocks
                    $filledBlocks = round($result['percentage'] / 20);
                    $emptyBlocks = 5 - $filledBlocks;
                    $confidenceMeter = str_repeat('🟥', $filledBlocks) . str_repeat('⬜', $emptyBlocks);
                    
                    // Generate share text using confidence message
                    $domain = parse_url($result['url'], PHP_URL_HOST) ?: $result['url'];
                    $confidenceMessage = $result['confidence']['message'];
                    $shareText = "{$result['confidence']['emoji']} {$domain}: {$confidenceMessage}\n{$confidenceMeter}\n\nFind out other sites built with Laravel at isit.laravel.cloud\n\n#laravel";
                    
                    // Twitter/X share URL
                    $twitterUrl = 'https://twitter.com/intent/tweet?text=' . urlencode($shareText);
                    
                    // Bluesky share URL
                    $blueskyUrl = 'https://bsky.app/intent/compose?text=' . urlencode($shareText);
                @endphp
                
                <div class="max-w-lg mx-auto">
                    <!-- Preview -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                        <div class="text-sm text-gray-700 whitespace-pre-line font-mono leading-relaxed">{{ $result['confidence']['emoji'] }} {{ $domain }}: {{ $confidenceMessage }}
{{ $confidenceMeter }}

Find out other sites built with Laravel at isit.laravel.cloud

#laravel</div>
                    </div>
                    
                    <!-- Share Buttons -->
                    <div class="grid grid-cols-2 gap-3">
                        <a 
                            href="{{ $twitterUrl }}" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="flex items-center justify-center gap-2 px-6 py-3 bg-black hover:bg-gray-800 focus:ring-4 focus:ring-gray-500 focus:ring-offset-2 text-white font-semibold rounded-lg transition-all outline-none"
                            aria-label="Share on X (formerly Twitter)"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            Share on 𝕏
                        </a>
                        
                        <a 
                            href="{{ $blueskyUrl }}" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="flex items-center justify-center gap-2 px-6 py-3 bg-blue-500 hover:bg-blue-600 focus:ring-4 focus:ring-blue-500 focus:ring-offset-2 text-white font-semibold rounded-lg transition-all outline-none"
                            aria-label="Share on Bluesky"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 0 1-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.206-.659-.298-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8Z"/>
                            </svg>
                            Share on Bluesky
                        </a>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 justify-center">
                <a href="{{ route('home') }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:ring-gray-500 focus:ring-offset-2 text-gray-700 font-semibold rounded-lg transition-all outline-none">
                    Analyze Another URL
                </a>
                <form method="POST" action="{{ route('detect') }}" class="inline">
                    @csrf
                    <input type="hidden" name="url" value="{{ $result['url'] }}">
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-laravel-red hover:bg-red-600 focus:ring-4 focus:ring-red-500 focus:ring-offset-2 text-white font-semibold rounded-lg transition-all outline-none"
                        aria-label="Re-scan this URL"
                    >
                        <span aria-hidden="true">🔄</span> Re-scan
                    </button>
                </form>
            </div>
        @endif
    </main>
</div>
@endsection
