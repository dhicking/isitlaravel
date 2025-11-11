<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Is It Laravel?')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- OpenGraph Meta Tags -->
    <meta property="og:title" content="@yield('og_title', 'Is It Laravel?')">
    <meta property="og:description" content="@yield('og_description', 'Detect if any website is built with Laravel. We check cookies, CSRF tokens, build tools, and more.')">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Is It Laravel?')">
    <meta name="twitter:description" content="@yield('og_description', 'Detect if any website is built with Laravel. We check cookies, CSRF tokens, build tools, and more.')">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'Is It Laravel?',
        'description' => 'Detect if any website is built with Laravel. We check cookies, CSRF tokens, build tools, and more.',
        'url' => url('/'),
        'applicationCategory' => 'DeveloperApplication',
        'operatingSystem' => 'Web',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'USD',
        ],
        'author' => [
            '@type' => 'Person',
            'name' => 'Dave Hicking',
            'url' => 'https://davehicking.com',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Instrument+Serif:ital,wght@0,400;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        serif: ['Instrument Serif', 'ui-serif', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --color-laravel-red: #FF2D20;
        }
        
        * {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }
        
        .laravel-red {
            color: var(--color-laravel-red);
        }
        
        .bg-laravel-red {
            background-color: var(--color-laravel-red);
        }
        
        .border-laravel-red {
            border-color: var(--color-laravel-red);
        }
        
        .hover-laravel-red:hover {
            background-color: #e62915;
        }
        
        /* Screen reader only - visually hidden but accessible to assistive tech */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        
        /* Show on focus for skip links */
        .sr-only:focus {
            position: fixed;
            width: auto;
            height: auto;
            padding: 0.5rem 1rem;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
            z-index: 9999;
        }
        
        /* Ensure focus rings are visible */
        *:focus-visible {
            outline: 2px solid var(--color-laravel-red);
            outline-offset: 2px;
        }
        
        /* Remove default outline for elements with custom focus styles */
        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: none;
        }
    </style>
</head>
<body class="bg-white min-h-screen">
    <div class="min-h-screen flex flex-col">
        @yield('content')
    </div>
</body>
</html>

