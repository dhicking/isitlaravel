# Deployment Guide

Ready to deploy "Is It Laravel?" to production? Here are your options.

## Quick Deployment Options

### Option 1: Laravel Forge (Easiest)

Laravel Forge makes deployment trivial:

1. Connect your Forge account to your server
2. Create a new site
3. Point to your Git repository
4. Forge handles: Nginx, PHP, SSL, deployments
5. Push to deploy automatically

**Cost**: ~$5/month server + $12/month Forge

### Option 2: Laravel Vapor (Serverless)

Deploy to AWS Lambda with zero server management:

```bash
composer require laravel/vapor-cli --dev
vapor init
vapor deploy production
```

**Cost**: Pay per request (very economical for low traffic)

### Option 3: Shared Hosting

If you have traditional shared hosting:

1. Upload files via FTP/SFTP
2. Point document root to `/public`
3. Set environment variables
4. Ensure PHP 8.2+

### Option 4: DigitalOcean App Platform

One-click Laravel deployment:

1. Connect GitHub repo
2. Select Laravel template
3. Deploy!

**Cost**: ~$5/month

## Manual Server Setup

### Prerequisites

- Ubuntu 22.04 or 24.04 LTS
- Root or sudo access
- Domain name (optional but recommended)

### Step 1: Install PHP 8.2+

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-gd php8.2-bcmath
```

### Step 2: Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 3: Install Nginx

```bash
sudo apt install -y nginx
```

### Step 4: Clone Repository

```bash
cd /var/www
sudo git clone <your-repo-url> is-it-laravel
cd is-it-laravel
```

### Step 5: Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### Step 6: Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Step 7: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/is-it-laravel
sudo chmod -R 755 /var/www/is-it-laravel
sudo chmod -R 775 /var/www/is-it-laravel/storage
sudo chmod -R 775 /var/www/is-it-laravel/bootstrap/cache
```

### Step 8: Configure Nginx

Create `/etc/nginx/sites-available/is-it-laravel`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    root /var/www/is-it-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/is-it-laravel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 9: Install SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

### Step 10: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Environment Variables

### Required
```env
APP_KEY=base64:xxxxx
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Optional (No Database Required)
This app doesn't need a database by default, but if you want to add features like:
- Analytics tracking
- Detection history
- User accounts

Then configure:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

## Performance Optimization

### 1. Enable OPcache

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### 2. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Use CDN for Tailwind (Already Done)

The app uses Tailwind CSS via CDN, which is fine for production.

For better performance, you could:
```bash
npm install
npm run build
```

Then update views to use `@vite()` instead of CDN.

### 4. Queue System (Optional)

For handling multiple detection requests:

```bash
sudo apt install -y redis-server
```

Update `.env`:
```env
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

Run queue worker:
```bash
php artisan queue:work --daemon
```

### 5. HTTP Client Optimization

The app already uses reasonable timeouts (10s for main requests, 5s for 404 checks).

For high traffic, consider:
- Implementing caching (results cached by URL for X hours)
- Rate limiting per IP
- Async job processing

## Monitoring & Maintenance

### 1. Error Tracking

Consider adding:
- **Sentry**: Real-time error tracking
- **Flare**: Laravel-specific error tracking

```bash
composer require sentry/sentry-laravel
```

### 2. Uptime Monitoring

Use services like:
- UptimeRobot (free)
- Pingdom
- StatusCake

### 3. Log Rotation

Laravel logs are in `storage/logs/`. Set up logrotate:

```bash
sudo nano /etc/logrotate.d/laravel
```

```
/var/www/is-it-laravel/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0640 www-data www-data
}
```

### 4. Automated Backups

Since there's no database, just backup:
- Application code (already in Git)
- `.env` file (secrets)

```bash
# Backup .env
sudo cp /var/www/is-it-laravel/.env /backups/env-$(date +%Y%m%d).bak
```

## Security Checklist

✅ APP_DEBUG=false in production  
✅ Strong APP_KEY generated  
✅ HTTPS enabled (SSL certificate)  
✅ File permissions correct (755/775)  
✅ Nginx configured properly  
✅ Firewall enabled (ufw)  
✅ PHP version up to date  
✅ Composer dependencies updated  
✅ Rate limiting enabled (optional)  

## Rate Limiting (Recommended)

Add to `app/Http/Kernel.php`:

```php
'api' => [
    'throttle:60,1', // 60 requests per minute
],
```

Apply to routes in `routes/web.php`:

```php
Route::post('/detect', [DetectorController::class, 'detect'])
    ->middleware('throttle:10,1') // 10 requests per minute
    ->name('detect');
```

## Scaling Considerations

### For High Traffic

1. **Load Balancer**: Use multiple app servers
2. **Redis**: Cache detection results
3. **Queue Workers**: Process detections async
4. **CDN**: Serve static assets
5. **Database**: Store results for analytics

### Caching Strategy

Add caching to `LaravelDetectorService.php`:

```php
use Illuminate\Support\Facades\Cache;

public function detect(string $url): array
{
    $cacheKey = 'detection:' . md5($url);
    
    return Cache::remember($cacheKey, 3600, function () use ($url) {
        // Existing detection logic
    });
}
```

## Troubleshooting

### 500 Error
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
tail -f storage/logs/laravel.log
```

### Permission Errors
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Nginx 502 Bad Gateway
```bash
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
```

### SSL Issues
```bash
sudo certbot renew --dry-run
sudo systemctl reload nginx
```

## Cost Estimates

### Minimal Setup
- **DigitalOcean Droplet**: $6/month
- **Domain**: $10-15/year
- **Total**: ~$7/month

### Managed Setup
- **Laravel Forge**: $12/month
- **Server**: $5-12/month
- **Domain**: $10-15/year
- **Total**: ~$18-25/month

### Serverless (Laravel Vapor)
- **Vapor**: $39/month
- **AWS Lambda**: Pay per request (~$0-5/month for low traffic)
- **Domain**: $10-15/year
- **Total**: ~$40-45/month (but truly scalable)

## Recommended: Start Simple

For this app, I recommend:

1. **Deploy to DigitalOcean App Platform** ($5/month)
   - Zero configuration
   - Auto-scaling
   - Free SSL
   - Easy deployments

2. **Or use Laravel Forge** if you plan to host multiple Laravel apps

---

Ready to deploy? Choose your method and get "Is It Laravel?" online! 🚀

