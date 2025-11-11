# Is It Laravel? 🔍

A beautiful Laravel application that detects if any website is built with Laravel by analyzing various framework indicators.

## Features

✨ **Comprehensive Detection** - Checks for 17 different Laravel indicators:
- **Core Laravel**: XSRF-TOKEN cookies, laravel_session cookies, CSRF meta tags, _token inputs, X-Powered-By headers
- **Ecosystem**: Vite assets, Mix manifest, Inertia.js, Livewire, Laravel Echo, Blade comments, Breeze/Jetstream layouts
- **Platform**: Laravel 404 pages, /up health check endpoint, Laravel tools (Telescope, Horizon, Nova, Pulse)
- **Admin Panels**: Filament, Statamic

🎨 **Beautiful UI** - Clean, modern design with Instrument Sans/Serif fonts and responsive layout

📊 **Detailed Results** - Shows detection confidence (up to 100% for definitive indicators), score breakdown, and component counts

🔒 **Security** - SSRF protection, security headers (CSP, HSTS, X-Frame-Options), and safe URL validation

⚡ **Performance** - Response caching (15 min), parallel HTTP requests, Laravel Octane with FrankenPHP

📤 **Social Sharing** - Easy sharing to X/Twitter, Bluesky, or copy-to-clipboard for any platform

🚀 **Modern Stack** - Built with Laravel 12, Tailwind CSS, and modern PHP practices

## Detection Logic

This application was inspired by a Chrome extension and implements the same detection logic in pure Laravel. It analyzes:

### Core Laravel Indicators
- **Cookies**: Looks for `XSRF-TOKEN` and `laravel_session` cookies
- **CSRF Protection**: Checks for CSRF meta tags and token inputs
- **404 Pages**: Analyzes 404 error pages for Laravel-specific patterns

### Build Tools
- **Vite**: Detects Vite module scripts and build asset paths

### Modern Frameworks
- **Inertia.js**: Looks for data-page attributes and Inertia components
- **Livewire**: Detects wire:id attributes and Livewire components

### Confidence Scoring
- **100% Confidence** (Definitive): Detected Filament, Telescope, Horizon, Nova, Pulse, Livewire, Echo, or Breeze/Jetstream - "Definitely Laravel!"
- **High Confidence** (3+ indicators): Strong likelihood of Laravel - "Highly likely Laravel!"
- **Medium Confidence** (1-2 indicators): Possibly Laravel - "Possibly Laravel"
- **Low Confidence** (0 indicators): Unlikely to be Laravel - "Unlikely to be Laravel"

## Installation

1. **Clone or navigate to the project directory:**
```bash
cd is-it-laravel
```

2. **Install PHP dependencies:**
```bash
composer install
```

3. **Set up environment file:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Run database migrations (optional):**
```bash
php artisan migrate
```

5. **Start the development server:**
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Usage

1. Open the application in your browser
2. Enter any website URL (e.g., `laravel.com`, `forge.laravel.com`)
3. Click "Analyze"
4. View the detailed detection results with:
   - Overall confidence score (0-100%)
   - Indicator breakdown by category (Core Laravel, Ecosystem, Platform & Endpoints)
   - Detected components (Inertia/Livewire/Tools)
   - Visual progress indicators
   - Share results on social media or copy to clipboard

## Example URLs to Test

- `laravel.com` - Official Laravel website
- `forge.laravel.com` - Laravel Forge
- `nova.laravel.com` - Laravel Nova
- Any Laravel application you know!

## How It Works

The `LaravelDetectorService` class fetches the target website and analyzes:

1. **HTTP Response**: Checks cookies and headers
2. **HTML Content**: Parses the page for meta tags, scripts, and framework signatures
3. **404 Page**: Makes a request to a random path to analyze the error page
4. **Scoring**: Calculates confidence based on indicators found

## Project Structure

```
app/
├── Http/Controllers/
│   └── DetectorController.php     # Main controller
├── Services/
│   └── LaravelDetectorService.php # Detection logic
resources/views/
├── layouts/
│   └── app.blade.php              # Base layout
└── detector/
    ├── index.blade.php            # Landing page
    └── results.blade.php          # Results page
routes/
└── web.php                        # Routes
```

## Requirements

- PHP 8.2 or higher
- Composer
- Laravel 12.x

## Security & Performance

- **SSRF Protection**: Blocks private IPs and dangerous hostnames to prevent server-side request forgery
- **Response Caching**: Results cached for 15 minutes to reduce load and improve performance
- **Security Headers**: CSP, HSTS, X-Frame-Options, and more for secure browsing
- **Parallel Requests**: Multiple checks run simultaneously for faster detection

## Notes

- Some Laravel sites may have custom configurations that hide indicators
- Network requests may fail for sites with strict security policies
- False negatives are possible but false positives are rare
- The tool respects rate limits and uses reasonable timeouts
- Results are cached to avoid hammering the same sites repeatedly

## Credits

Inspired by the Laravel Detector Chrome extension. Rebuilt in Laravel to demonstrate the framework's capabilities in a meta way - using Laravel to detect Laravel! 🎯

## License

This is an open-source project created for educational purposes.
