@extends('layouts.app')

@section('title', 'Is It Laravel? - Detect Laravel Websites')

@section('content')
<div class="min-h-screen bg-white flex items-center justify-center px-4 py-12">
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-laravel-red focus:text-white focus:rounded-lg focus:ring-4 focus:ring-red-500 focus:ring-offset-2">
        Skip to main content
    </a>
    
    <div id="main-content" class="max-w-2xl w-full">
        <!-- Hero Title -->
        <div class="text-center mb-12">
            <h1 class="font-serif text-6xl md:text-7xl lg:text-8xl font-normal italic text-gray-900 mb-6 tracking-loose">
                Is It Laravel?
            </h1>
            <p class="text-lg md:text-xl text-gray-600 mb-2">
                Detect if any website is built with Laravel
            </p>
        </div>

        <!-- Search Form -->
        <div class="mb-12">
            <form action="{{ route('detect') }}" method="POST" id="detect-form" onsubmit="showLoading()">
                @csrf
                
                <div class="flex gap-3 mb-4 w-full">
                    <div class="flex-1 min-w-0">
                        <label for="url" class="sr-only">Website URL to analyze</label>
                        <input 
                            type="text" 
                            name="url" 
                            id="url" 
                            class="w-full px-5 py-4 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:border-transparent transition-all text-lg outline-none"
                            placeholder="laravel.com"
                            value="{{ old('url', $prefillUrl ?? '') }}"
                            spellcheck="false"
                            autocorrect="off"
                            autocapitalize="off"
                            inputmode="url"
                            required
                            autofocus
                            @error('url') aria-describedby="url-error" aria-invalid="true" @enderror
                        >
                    </div>
                    <button 
                        type="submit" 
                        id="submit-button"
                        class="bg-laravel-red hover:bg-red-600 focus:ring-4 focus:ring-red-500 focus:ring-offset-2 text-white font-semibold px-6 sm:px-10 py-4 rounded-lg transition-all duration-200 whitespace-nowrap flex-shrink-0 outline-none relative"
                    >
                        <span id="button-text">Analyze</span>
                        <span id="loading-spinner" class="hidden absolute inset-0 flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
                
                @error('url')
                    <p id="url-error" class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </form>

            <!-- Example websites -->
            <div class="text-center mt-6">
                <p class="text-xs text-gray-500 mb-3 uppercase tracking-wide">Try these examples</p>
                <div class="flex flex-wrap justify-center gap-2" role="group" aria-label="Example websites">
                    <button 
                        onclick="document.getElementById('url').value='laravel.com'; document.getElementById('url').focus();" 
                        class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded-lg transition-all outline-none"
                        aria-label="Use laravel.com as example URL"
                    >
                        laravel.com
                    </button>
                    <button 
                        onclick="document.getElementById('url').value='cloud.laravel.com'; document.getElementById('url').focus();" 
                        class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded-lg transition-all outline-none"
                        aria-label="Use cloud.laravel.com as example URL"
                    >
                        cloud.laravel.com
                    </button>
                    <button 
                        onclick="document.getElementById('url').value='nightwatch.laravel.com'; document.getElementById('url').focus();" 
                        class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded-lg transition-all outline-none"
                        aria-label="Use nightwatch.laravel.com as example URL"
                    >
                        nightwatch.laravel.com
                    </button>
                    <button 
                        onclick="document.getElementById('url').value='forge.laravel.com'; document.getElementById('url').focus();" 
                        class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded-lg transition-all outline-none"
                        aria-label="Use forge.laravel.com as example URL"
                    >
                        forge.laravel.com
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center text-sm text-gray-500">
            <p>Built with <span class="laravel-red" aria-label="love">❤</span> using Laravel by <a href="https://davehicking.com" target="_blank" rel="noopener noreferrer" class="text-gray-600 hover:text-gray-900 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded transition-colors outline-none">Dave Hicking</a></p>
        </footer>
    </div>
</div>

<script>
    function showLoading() {
        const button = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const loadingSpinner = document.getElementById('loading-spinner');
        
        if (button && buttonText && loadingSpinner) {
            button.disabled = true;
            buttonText.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
        }
    }
</script>
@endsection

