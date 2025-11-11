# Laravel Detection Logic

This document explains how the application detects Laravel websites, based on the logic extracted from the Chrome extension.

## Detection Indicators (17 Total)

### 1. XSRF Token Cookie
**Pattern**: Cookie name contains `XSRF-TOKEN`

Laravel uses XSRF-TOKEN cookies for CSRF protection. This is one of the most reliable indicators.

### 2. Laravel Session Cookie
**Pattern**: Cookie name contains `laravel_session`

Laravel's default session cookie name. Can be customized but most sites use the default.

### 3. CSRF Meta Tag
**Pattern**: `<meta name="csrf-token" content="...">`

Laravel apps often include this meta tag in the HTML head for JavaScript frameworks to access the CSRF token.

### 4. Hidden Token Input
**Pattern**: `<input name="_token" ...>`

Laravel forms include a hidden `_token` field for CSRF protection.

### 5. Vite Client
**Pattern**: Any of the following in script/link tags:
- `@vite`
- `@vite/client`
- `/build/`
- `/build/assets/`

Laravel 9+ uses Vite as the default frontend build tool. The presence of Vite-specific paths indicates a modern Laravel application.

### 6. Inertia.js
**Pattern**: 
- `data-page` attribute on elements
- `#app[data-page]`
- `window.Inertia` global variable

Inertia.js is a popular way to build modern monolithic applications with Laravel. The data-page attribute contains component information.

### 7. Livewire
**Pattern**:
- `wire:id` attribute
- `wire:initial-data` attribute  
- `window.Livewire` global variable

Laravel Livewire is a full-stack framework for building dynamic interfaces without leaving Laravel. Wire attributes indicate Livewire components.

### 8. Laravel 404 Page
**Pattern**: Request a random URL and check 404 response for:
- Title contains "404" or "Not Found"
- Tailwind CSS classes: `min-h-screen`, `bg-gray-100`, `text-gray-500`, `antialiased`
- Laravel-specific text: "PAGE NOT FOUND", "laravel", "Laravel", "Oops!", "We could not find the page"
- Layout classes: `max-w-xl`, `mx-auto`
- SVG with "404" text

Laravel's default 404 error pages have a distinctive style using Tailwind CSS.

### 9. Laravel Mix Manifest
**Pattern**: `/mix-manifest.json` returns valid JSON with asset paths

Laravel Mix (pre-Vite build tool) generates a manifest file mapping source files to compiled assets.

### 10. Blade Comments
**Pattern**: `{{--` in HTML source

Blade template comments are stripped in production but may appear in some configurations.

### 11. Laravel Echo
**Pattern**: 
- `laravel-echo` in script tags
- `window.Echo` or `new Echo(` in JavaScript

Laravel Echo is used for WebSocket/event broadcasting in Laravel applications.

### 12. Breeze / Jetstream Layout
**Pattern**: Default Laravel Breeze/Jetstream CSS classes and structure

Laravel's official authentication starter kits have distinctive styling patterns.

### 13. Laravel Tools (Telescope, Horizon, Nova, Pulse)
**Pattern**: HTTP 401/403 or 200 response from `/telescope`, `/horizon`, `/nova`, or `/pulse`

These are Laravel-specific packages that can only exist in Laravel applications. Detection of any of these is a 100% confidence indicator.

### 14. Filament
**Pattern**: 
- `/filament/login` or `/admin/login` pages
- Filament asset paths (`/vendor/filament/`, `/filament/assets/`)
- Filament-specific HTML identifiers and CSS classes

Filament is a Laravel admin panel. Detection is a 100% confidence indicator.

### 15. Statamic
**Pattern**:
- `/cp` control panel
- Statamic generator meta tag
- Statamic asset paths

Statamic is a Laravel-based CMS. Detection is a 100% confidence indicator.

### 16. /up Health Check Endpoint
**Pattern**: `/up` returns HTTP 200 with minimal content (< 100 bytes)

Laravel 11+ includes a built-in health check endpoint that returns a simple response.

### 17. X-Powered-By Header
**Pattern**: HTTP header `X-Powered-By: Laravel`

Some Laravel configurations expose this header (though it's often removed in production).

## Confidence Scoring

### 100% Confidence (Definitive Indicators)
When any of these are detected, the site is **definitely** Laravel:
- Filament
- Telescope, Horizon, Nova, or Pulse
- Livewire
- Laravel Echo
- Breeze/Jetstream layout

**Result**: "🎯 Definitely Laravel!" (100% confidence)

### High Confidence (3+ indicators)
When 3 or more indicators are found, there's a very high likelihood the site is built with Laravel.

**Result**: "🎯 Highly likely Laravel!" (75-95% confidence)

### Medium Confidence (1-2 indicators)
Some indicators found but not enough for high confidence. Could be Laravel or a site using some Laravel conventions.

**Result**: "🤔 Possibly Laravel" (35-65% confidence)

### Low Confidence (0 indicators)
No Laravel indicators found. Very unlikely to be Laravel.

**Result**: "❓ Unlikely to be Laravel" (0-35% confidence)

## Implementation Notes

### From JavaScript to PHP

The original Chrome extension detection logic was in JavaScript and ran in the browser:

```javascript
// JavaScript (Chrome Extension)
document.cookie.includes("XSRF-TOKEN")
document.querySelector('meta[name="csrf-token"]')
```

The Laravel version uses HTTP requests and HTML parsing:

```php
// PHP (Laravel)
$response->cookies()->contains('XSRF-TOKEN')
preg_match('/<meta\s+name="csrf-token"/', $html)
```

### Key Differences

1. **Execution Context**: Browser DOM vs Server-side HTTP requests
2. **Cookie Access**: Direct cookie API vs HTTP response cookies
3. **JavaScript Detection**: Can't check `window.Livewire` or `window.Inertia` from server
4. **Performance**: Server-side requires full page fetch

### Limitations

- Cannot detect JavaScript-added cookies or dynamically loaded content
- Some sites may block automated requests (returns friendly error messages)
- Custom Laravel configurations might hide indicators
- Rate limiting may affect 404 page checks
- Results are cached for 15 minutes (errors cached for 5 minutes)

## Why These Indicators?

Each indicator was chosen because:

1. **Specificity**: Unique or highly correlated with Laravel
2. **Persistence**: Present across different Laravel versions
3. **Accessibility**: Can be detected via HTTP requests
4. **Reliability**: Not easily spoofed or accidentally present

## False Positives/Negatives

### False Positives (Rare)
A non-Laravel site would need to:
- Use Laravel's cookie naming conventions
- Implement CSRF with `_token` fields
- Use Vite with `/build/assets/` paths
- Have a 404 page styled like Laravel's default

This combination is extremely unlikely without actually using Laravel.

### False Negatives (Possible)
Laravel sites may not be detected if they:
- Customize all cookie names
- Disable CSRF protection
- Use custom build tools
- Have heavily customized error pages
- Block automated requests

Most production Laravel apps will trigger at least 2-3 indicators.

