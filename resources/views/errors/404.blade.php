@extends('layouts.app')

@section('title', 'Page Not Found - Is It Laravel?')

@section('content')
<div class="min-h-screen bg-white flex items-center justify-center px-4 py-12">
    <div class="max-w-2xl w-full text-center">
        <div class="mb-8">
            <h1 class="font-serif text-8xl md:text-9xl font-normal italic text-gray-200 mb-4 tracking-loose">404</h1>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Page Not Found</h2>
            <p class="text-lg text-gray-600 mb-8">
                The page you're looking for doesn't exist or has been moved.
            </p>
        </div>
        
        <div class="space-y-4">
            <a 
                href="{{ route('home') }}" 
                class="inline-block bg-laravel-red hover:bg-red-600 focus:ring-4 focus:ring-red-500 focus:ring-offset-2 text-white font-semibold px-8 py-4 rounded-lg transition-all outline-none"
            >
                Go to Homepage
            </a>
            <div class="text-sm text-gray-500">
                <p>Looking to detect if a website uses Laravel?</p>
                <p class="mt-1">Enter a URL on our <a href="{{ route('home') }}" class="text-laravel-red hover:underline focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded outline-none">homepage</a> to get started.</p>
            </div>
        </div>
    </div>
</div>
@endsection

