@extends('layouts.app')

@section('title', 'Is It Laravel? - Detect Laravel Websites')

@section('content')
<div class="min-h-screen bg-white flex items-center justify-center px-4 py-12">
    <div class="max-w-2xl w-full">
        <!-- Hero Title -->
        <div class="text-center mb-12">
            <h1 class="font-serif text-6xl md:text-7xl lg:text-8xl font-normal italic text-gray-900 mb-6 tracking-loose">
                Is It Laravel?
            </h1>
            <p class="text-lg md:text-xl text-gray-600 mb-2">
                Detect if any website is built with Laravel
            </p>
            <p class="text-sm text-gray-500">
                We check cookies, CSRF tokens, build tools, and more
            </p>
        </div>

        <!-- Search Form -->
        <div class="mb-12">
            <form action="{{ route('detect') }}" method="POST">
                @csrf
                
                <div class="flex gap-3 mb-4 w-full">
                    <input 
                        type="text" 
                        name="url" 
                        id="url" 
                        class="flex-1 min-w-0 px-5 py-4 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all text-lg"
                        placeholder="laravel.com"
                        value="{{ old('url') }}"
                        spellcheck="false"
                        autocorrect="off"
                        autocapitalize="off"
                        inputmode="url"
                        required
                        autofocus
                    >
                    <button 
                        type="submit" 
                        class="bg-laravel-red hover-laravel-red text-white font-semibold px-6 sm:px-10 py-4 rounded-lg transition-all duration-200 whitespace-nowrap flex-shrink-0"
                    >
                        Analyze
                    </button>
                </div>
                
                @error('url')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </form>

            <!-- Example websites -->
            <div class="text-center mt-6">
                <p class="text-xs text-gray-400 mb-3 uppercase tracking-wide">Try these examples</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <button onclick="document.getElementById('url').value='laravel.com'" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-all">
                        laravel.com
                    </button>
                    <button onclick="document.getElementById('url').value='cloud.laravel.com'" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-all">
                        cloud.laravel.com
                    </button>
                    <button onclick="document.getElementById('url').value='nightwatch.laravel.com'" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-all">
                        nightwatch.laravel.com
                    </button>
                    <button onclick="document.getElementById('url').value='forge.laravel.com'" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-all">
                        forge.laravel.com
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-400">
            <p>Built with <span class="laravel-red">❤</span> using Laravel by <a href="https://davehicking.com" target="_blank" rel="noopener noreferrer" class="text-gray-600 hover:text-gray-900 transition-colors">Dave Hicking</a></p>
        </div>
    </div>
</div>
@endsection

