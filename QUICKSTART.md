# Quick Start Guide

Get your "Is It Laravel?" application running in under 2 minutes!

## Prerequisites

Make sure you have:
- PHP 8.2 or higher installed
- Composer installed

Check your versions:
```bash
php --version
composer --version
```

## Installation Steps

### 1. Navigate to the project
```bash
cd /Users/dhicking/Downloads/is-it-laravel
```

### 2. Install dependencies (if not already done)
```bash
composer install
```

### 3. Set up environment (if not already done)
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Run the application
```bash
php artisan serve
```

You should see:
```
INFO  Server running on [http://127.0.0.1:8000]
```

### 5. Open in your browser
Visit: **http://localhost:8000**

## Using the Application

1. **Enter a URL** in the search box (e.g., `laravel.com`)
2. **Click "🔍 Is it Laravel?"**
3. **View the results** showing:
   - Confidence score
   - Detection indicators
   - Components found (Inertia/Livewire)
   - Visual breakdown

## Test URLs

Try these URLs to see the detector in action:

### ✅ Definitely Laravel
- `laravel.com`
- `forge.laravel.com`
- `nova.laravel.com`
- `vapor.laravel.com`

### 🤔 Possibly Laravel (fewer indicators)
- Custom Laravel apps with minimal frontend
- Laravel API-only applications

### ❌ Not Laravel
- `wordpress.org`
- `github.com`
- `example.com`

## Troubleshooting

### Port already in use?
```bash
php artisan serve --port=8001
```

### Dependencies not installed?
```bash
composer install
```

### Permission errors?
```bash
chmod -R 775 storage bootstrap/cache
```

### Database errors?
The app doesn't require a database by default. If you see database errors, make sure your `.env` has valid database credentials or use SQLite:
```env
DB_CONNECTION=sqlite
```

## What to Check

When testing a website, the app checks for:

✅ XSRF-TOKEN cookies  
✅ laravel_session cookies  
✅ CSRF meta tags  
✅ _token inputs  
✅ Vite build assets  
✅ Inertia.js components  
✅ Livewire components  
✅ Laravel 404 pages  

## Next Steps

- Read `DETECTION_LOGIC.md` to understand how detection works
- Check `README.md` for full documentation
- Customize the UI in `resources/views/`
- Extend detection logic in `app/Services/LaravelDetectorService.php`

## Need Help?

Common issues:
1. **Timeout errors**: Some sites block automated requests
2. **SSL errors**: Some sites have strict SSL requirements
3. **False negatives**: Heavily customized Laravel apps may not be detected

Enjoy detecting Laravel websites! 🎯

