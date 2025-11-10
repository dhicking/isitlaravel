@extends('layouts.app')

@section('title', 'Detection Results - Is It Laravel?')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 py-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                <svg class="w-10 h-10 laravel-red" fill="currentColor" viewBox="0 0 50 52" xmlns="http://www.w3.org/2000/svg">
                    <path d="M49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1-.402.694l-9.209 5.302V39.25c0 .286-.152.55-.4.694L20.42 51.01c-.044.025-.092.041-.14.058-.018.006-.035.017-.054.022a.805.805 0 0 1-.41 0c-.022-.006-.042-.018-.063-.026-.044-.016-.09-.03-.132-.054L.402 39.944A.801.801 0 0 1 0 39.25V6.334c0-.072.01-.142.028-.21.006-.023.02-.044.028-.067.015-.042.029-.085.051-.124.015-.026.037-.047.055-.071.023-.032.044-.065.071-.093.023-.023.053-.04.079-.06.029-.024.055-.05.088-.069h.001l9.61-5.533a.802.802 0 0 1 .8 0l9.61 5.533h.002c.032.02.059.045.088.068.026.02.055.038.078.06.028.029.048.062.072.094.017.024.04.045.054.071.023.04.036.082.052.124.008.023.022.044.028.068a.809.809 0 0 1 .028.209v20.559l8.008-4.611v-10.51c0-.07.01-.141.028-.208.007-.024.02-.045.028-.068.016-.042.03-.085.052-.124.015-.026.037-.047.054-.071.024-.032.044-.065.072-.093.023-.023.052-.04.078-.06.03-.024.056-.05.088-.069h.001l9.611-5.533a.801.801 0 0 1 .8 0l9.61 5.533c.034.02.06.045.09.068.025.02.054.038.077.06.028.029.048.062.072.094.018.024.04.045.054.071.023.039.036.082.052.124.009.023.022.044.028.068zm-1.574 10.718v-9.124l-3.363 1.936-4.646 2.675v9.124l8.01-4.611zm-9.61 16.505v-9.13l-4.57 2.61-13.05 7.448v9.216l17.62-10.144zM1.602 7.719v31.068L19.22 48.93v-9.214l-9.204-5.209-.003-.002-.004-.002c-.031-.018-.057-.044-.086-.066-.025-.02-.054-.036-.076-.058l-.002-.003c-.026-.025-.044-.056-.066-.084-.02-.027-.044-.05-.06-.078l-.001-.003c-.018-.03-.029-.066-.042-.1-.013-.03-.03-.058-.038-.09v-.001c-.01-.038-.012-.078-.016-.117-.004-.03-.012-.06-.012-.09v-.002-21.481L4.965 9.654 1.602 7.72zm8.81-5.994L2.405 6.334l8.005 4.609 8.006-4.61-8.006-4.608zm4.164 28.764l4.645-2.674V7.719l-3.363 1.936-4.646 2.675v20.096l3.364-1.937zM39.243 7.164l-8.006 4.609 8.006 4.609 8.005-4.61-8.005-4.608zm-.801 10.605l-4.646-2.675-3.363-1.936v9.124l4.645 2.674 3.364 1.937v-9.124zM20.02 38.33l11.743-6.704 5.87-3.35-8-4.606-9.211 5.303-8.395 4.833 7.993 4.524z"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900">Is It Laravel?</h1>
            </a>
            <a href="{{ route('home') }}" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                ← New Analysis
            </a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if(!$result['success'])
            <!-- Error State -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12">
                <div class="text-center max-w-lg mx-auto">
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Error Analyzing Website</h2>
                    <p class="text-gray-600 mb-1">{{ $result['error'] }}</p>
                    <p class="text-sm text-gray-500 mb-6">URL: {{ $result['url'] }}</p>
                    <a href="{{ route('home') }}" class="bg-laravel-red hover-laravel-red text-white font-semibold px-6 py-3 rounded-lg transition-all inline-block">
                        Try Another URL
                    </a>
                </div>
            </div>
        @else
            <!-- Results Header -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 mb-6">
                <div class="text-center">
                    <div class="text-6xl mb-4">{{ $result['confidence']['emoji'] }}</div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        {{ $result['confidence']['message'] }}
                    </h2>
                    <p class="text-gray-500 break-all mb-6">{{ $result['url'] }}</p>

                    <!-- Score Bar -->
                    <div class="max-w-md mx-auto">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-medium text-gray-700">Detection Score</span>
                            <span class="font-bold text-gray-900">{{ $result['score'] }} / {{ $result['totalIndicators'] }}</span>
                        </div>
                        <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
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
                                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
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
                                <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0">
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
                                <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
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
                            <span class="text-lg">📦</span>
                            Core Laravel
                        </h4>
                        <div class="space-y-2">
                            @php
                                $coreIndicators = [
                                    ['XSRF-TOKEN cookie', $result['indicators']['xsrfToken']],
                                    ['laravel_session cookie', $result['indicators']['laravelSession']],
                                    ['CSRF meta tag', $result['indicators']['csrfMeta']],
                                    ['_token input', $result['indicators']['tokenInput']],
                                    ['Laravel 404 page', $result['indicators']['laravel404']],
                                    ['X-Powered-By header', $result['indicators']['poweredByHeader']],
                                ];
                            @endphp
                            
                            @foreach($coreIndicators as [$label, $found])
                                <div class="flex items-center text-sm px-3 py-2 rounded-lg {{ $found ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-400' }}">
                                    <span class="mr-2">{{ $found ? '✓' : '✗' }}</span>
                                    <span class="{{ $found ? 'font-medium' : '' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Build & Endpoints -->
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm mb-3 flex items-center gap-2">
                            <span class="text-lg">⚡</span>
                            Build & Endpoints
                        </h4>
                        <div class="space-y-2">
                            @php
                                $buildIndicators = [
                                    ['Vite', $result['indicators']['viteClient']],
                                    ['index.php routing', $result['indicators']['indexPhp']],
                                    ['/up health check', $result['indicators']['upEndpoint']],
                                    ['Laravel tools', $result['indicators']['laravelTools']],
                                ];
                            @endphp
                            
                            @foreach($buildIndicators as [$label, $found])
                                <div class="flex items-center text-sm px-3 py-2 rounded-lg {{ $found ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-400' }}">
                                    <span class="mr-2">{{ $found ? '✓' : '✗' }}</span>
                                    <span class="{{ $found ? 'font-medium' : '' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modern Stacks -->
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm mb-3 flex items-center gap-2">
                            <span class="text-lg">🚀</span>
                            Modern Stacks
                        </h4>
                        <div class="space-y-2">
                            @php
                                $modernIndicators = [
                                    ['Inertia.js', $result['indicators']['inertia']],
                                    ['Livewire', $result['indicators']['livewire']],
                                ];
                            @endphp
                            
                            @foreach($modernIndicators as [$label, $found])
                                <div class="flex items-center text-sm px-3 py-2 rounded-lg {{ $found ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-400' }}">
                                    <span class="mr-2">{{ $found ? '✓' : '✗' }}</span>
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
                    <div class="flex-shrink-0">
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
                            the <code class="bg-blue-100 px-1 rounded">/up</code> health check endpoint, and 
                            <code class="bg-blue-100 px-1 rounded">index.php</code> routing support.
                        </p>
                        <p class="text-gray-700 text-sm leading-relaxed">
                            <strong>Note:</strong> Some Laravel sites may use custom configurations that hide these indicators, 
                            which could result in false negatives. A low score doesn't always mean the site isn't Laravel.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 justify-center">
                <a href="{{ route('home') }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-all">
                    Analyze Another URL
                </a>
                <form method="POST" action="{{ route('detect') }}" class="inline">
                    @csrf
                    <input type="hidden" name="url" value="{{ $result['url'] }}">
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-laravel-red hover-laravel-red text-white font-semibold rounded-lg transition-all"
                    >
                        🔄 Re-scan
                    </button>
                </form>
            </div>
        @endif
    </main>
</div>
@endsection
