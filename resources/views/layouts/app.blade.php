<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Is It Laravel?')</title>
    
    <!-- OpenGraph Meta Tags -->
    <meta property="og:title" content="@yield('og_title', 'Is It Laravel?')">
    <meta property="og:description" content="@yield('og_description', 'Detect if any website is built with Laravel. We check cookies, CSRF tokens, build tools, and more.')">
    <meta property="og:image" content="{{ asset('og-image.svg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Is It Laravel?')">
    <meta name="twitter:description" content="@yield('og_description', 'Detect if any website is built with Laravel. We check cookies, CSRF tokens, build tools, and more.')">
    <meta name="twitter:image" content="{{ asset('og-image.svg') }}">
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
    </style>
</head>
<body class="bg-white min-h-screen">
    <div class="min-h-screen flex flex-col">
        @yield('content')
    </div>
</body>
</html>

