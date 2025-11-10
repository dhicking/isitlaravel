@extends('layouts.app')

@section('title', 'Is It Laravel? - Detect Laravel Websites')

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
            <span class="text-sm text-gray-500">Laravel Detection Tool</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-12">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Detect Laravel Websites
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Analyze any website to discover if it's built with Laravel. We check cookies, meta tags, build tools, and framework-specific patterns.
            </p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 mb-8">
            <form action="{{ route('detect') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter Website URL
                    </label>
                    <div class="flex gap-3">
                        <input 
                            type="text" 
                            name="url" 
                            id="url" 
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all text-base"
                            placeholder="https://example.com"
                            value="{{ old('url') }}"
                            required
                            autofocus
                        >
                        <button 
                            type="submit" 
                            class="bg-laravel-red hover-laravel-red text-white font-semibold px-8 py-3 rounded-lg transition-all duration-200 whitespace-nowrap"
                        >
                            Analyze
                        </button>
                    </div>
                    
                    @error('url')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            <!-- Example websites -->
            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-sm text-gray-500 mb-3">Try these examples:</p>
                <div class="flex flex-wrap gap-2">
                    <button onclick="document.getElementById('url').value='laravel.com'" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-all">
                        laravel.com
                    </button>
                    <button onclick="document.getElementById('url').value='cloud.laravel.com'" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-all">
                        cloud.laravel.com
                    </button>
                    <button onclick="document.getElementById('url').value='nightwatch.laravel.com'" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-all">
                        nightwatch.laravel.com
                    </button>
                    <button onclick="document.getElementById('url').value='forge.laravel.com'" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-all">
                        forge.laravel.com
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-20">
        <div class="max-w-6xl mx-auto px-4 py-8 text-center text-sm text-gray-500">
            <p>Built with <span class="laravel-red">❤</span> using Laravel</p>
        </div>
    </footer>
</div>
@endsection

