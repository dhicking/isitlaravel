# JavaScript to Laravel Implementation Guide

This document shows how the Chrome extension's JavaScript detection logic was translated to Laravel/PHP.

## Original Chrome Extension Logic (JavaScript)

```javascript
// From the Chrome extension popup
const indicators = {
    xsrfToken: document.cookie.includes("XSRF-TOKEN"),
    laravelSession: document.cookie.includes("laravel_session"),
    csrfMeta: !!document.querySelector('meta[name="csrf-token"]'),
    tokenInput: !!document.querySelector('input[name="_token"]'),
    viteClient: (
        !!document.querySelector('script[type="module"][src*="@vite"]') ||
        !!document.querySelector('script[src*="@vite/client"]') ||
        !!document.querySelector('script[type="module"][src*="/build/"]') ||
        !!document.querySelector('script[type="module"][src*="/build/assets/"]') ||
        !!document.querySelector('link[href*="/build/assets/"]')
    ),
    inertia: (
        !!document.querySelector("[data-page]") ||
        !!document.querySelector("#app[data-page]") ||
        !!window.Inertia
    ),
    livewire: (
        !!document.querySelector("[wire\\:id]") ||
        !!document.querySelector("[wire\\:initial-data]") ||
        !!window.Livewire
    ),
    laravel404: false
};

// 404 Check
const randomPath = "/laravel-detector-check-" + Math.random().toString(36).substring(7);
const testUrl = window.location.origin + randomPath;
const response = await fetch(testUrl, {
    method: "GET",
    credentials: "same-origin",
    headers: { Accept: "text/html" }
});

if (response.status === 404) {
    const html = await response.text();
    const isLaravel404 = (
        (html.includes("<title>404") || html.includes("<title>Not Found")) &&
        (html.includes("min-h-screen") || html.includes("bg-gray-100") ||
         html.includes("text-gray-500") || html.includes("antialiased") ||
         html.includes("PAGE NOT FOUND") || html.includes("laravel") ||
         html.includes("Laravel") || html.includes("max-w-xl") ||
         html.includes("mx-auto") || html.includes("Oops!") ||
         html.includes("We could not find the page") ||
         (html.includes("<svg") && html.includes("404")))
    );
    indicators.laravel404 = isLaravel404;
}
```

## Laravel/PHP Implementation

```php
// LaravelDetectorService.php
public function detect(string $url): array
{
    $url = $this->normalizeUrl($url);
    
    $indicators = [
        'xsrfToken' => false,
        'laravelSession' => false,
        'csrfMeta' => false,
        'tokenInput' => false,
        'viteClient' => false,
        'inertia' => false,
        'livewire' => false,
        'laravel404' => false,
    ];

    // Fetch the main page
    $response = Http::timeout(10)
        ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; LaravelDetector/1.0)',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ])
        ->get($url);

    $html = $response->body();
    $cookies = $response->cookies();

    // Check cookies
    foreach ($cookies as $cookie) {
        $cookieName = $cookie->getName();
        if (str_contains(strtolower($cookieName), 'xsrf-token')) {
            $indicators['xsrfToken'] = true;
        }
        if (str_contains(strtolower($cookieName), 'laravel_session')) {
            $indicators['laravelSession'] = true;
        }
    }

    // Check HTML patterns
    $indicators['csrfMeta'] = $this->containsPattern($html, '<meta\s+name=["\']csrf-token["\']');
    $indicators['tokenInput'] = $this->containsPattern($html, '<input[^>]+name=["\']_token["\']');

    // Check for Vite
    $vitePatterns = [
        'src=["\'][^"\']*@vite',
        'src=["\'][^"\']*@vite/client',
        'src=["\'][^"\']*\/build\/',
        'src=["\'][^"\']*\/build\/assets\/',
        'href=["\'][^"\']*\/build\/assets\/',
    ];
    foreach ($vitePatterns as $pattern) {
        if ($this->containsPattern($html, $pattern)) {
            $indicators['viteClient'] = true;
            break;
        }
    }

    // Check for Inertia.js
    if ($this->containsPattern($html, 'data-page')) {
        $indicators['inertia'] = true;
    }

    // Check for Livewire
    $livewirePatterns = ['wire:id', 'wire:initial-data', 'window\.Livewire'];
    foreach ($livewirePatterns as $pattern) {
        if (str_contains($html, $pattern)) {
            $indicators['livewire'] = true;
            break;
        }
    }

    // Check Laravel 404 page
    $laravel404Result = $this->checkLaravel404Page($url);
    $indicators['laravel404'] = $laravel404Result['detected'];

    return $indicators;
}
```

## Key Differences

### 1. Execution Context

**JavaScript (Browser)**
- Runs in the user's browser
- Has direct access to the DOM
- Can check `window` objects (Inertia, Livewire)
- Same-origin policy applies

**PHP (Server)**
- Runs on the server
- Fetches pages via HTTP
- Parses HTML as text
- No access to JavaScript runtime

### 2. Cookie Access

**JavaScript**
```javascript
document.cookie.includes("XSRF-TOKEN")
```

**PHP**
```php
$response->cookies()->contains('XSRF-TOKEN')
```

### 3. DOM Querying vs Regex

**JavaScript**
```javascript
!!document.querySelector('meta[name="csrf-token"]')
```

**PHP**
```php
preg_match('/<meta\s+name=["\']csrf-token["\']/', $html)
```

### 4. Window Object Access

**JavaScript** ✅
```javascript
!!window.Inertia  // Can check global objects
!!window.Livewire
```

**PHP** ❌
```php
// Cannot check JavaScript globals
// Must rely on HTML attributes only
```

### 5. URL Handling

**JavaScript**
```javascript
window.location.origin + "/random-path"
```

**PHP**
```php
rtrim($url, '/') . '/random-path'
```

## Advantages of Each Approach

### Chrome Extension (JavaScript)
✅ Access to live DOM  
✅ Can check JavaScript globals  
✅ No HTTP overhead  
✅ Can detect dynamically loaded content  
✅ Same-origin access to cookies  

❌ Requires browser extension  
❌ Only works for sites you visit  
❌ Can't batch analyze multiple sites  

### Laravel Application (PHP)
✅ No extension needed  
✅ Can analyze any public URL  
✅ Can batch process multiple sites  
✅ Server-side caching possible  
✅ Can add API endpoints  

❌ No access to JavaScript runtime  
❌ HTTP overhead for each check  
❌ May be blocked by security policies  
❌ Can't see dynamically loaded content  

## Scoring Logic (Identical)

Both implementations use the same scoring thresholds:

```javascript
// JavaScript
const HIGH_CONFIDENCE_THRESHOLD = 3;
const LOW_CONFIDENCE_THRESHOLD = 1;
const TOTAL_INDICATORS = 8;
```

```php
// PHP
private const HIGH_CONFIDENCE_THRESHOLD = 3;
private const LOW_CONFIDENCE_THRESHOLD = 1;
private const TOTAL_INDICATORS = 8;
```

## UI Translation

### Chrome Extension (React)
```jsx
<div className="bg-green-100 text-green-800">
    🎯 Highly likely Laravel!
</div>
```

### Laravel (Blade)
```blade
<div class="bg-green-50 text-green-800 border-l-4 border-green-500">
    🎯 Highly likely Laravel!
</div>
```

## Conclusion

The Laravel implementation successfully replicates the Chrome extension's detection logic using server-side HTTP requests and HTML parsing. While it lacks access to JavaScript runtime objects, it compensates by being:

- Universally accessible (no extension required)
- More flexible for batch operations
- Easier to extend with additional features

Both implementations are effective, just optimized for different use cases! 🎯

