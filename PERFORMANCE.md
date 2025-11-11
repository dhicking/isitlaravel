# Performance Optimizations

This Laravel application is optimized for maximum performance on Laravel Cloud.

## Optimizations Implemented

### 1. **Laravel Octane with FrankenPHP** 🚀
- **10-50x faster** than traditional PHP-FPM
- Uses persistent application state between requests
- FrankenPHP is Laravel Cloud's recommended server
- Configured with auto workers and request cycling

### 2. **Route Caching**
- Pre-compiled route definitions
- Eliminates route file parsing on each request
- Deployed automatically via `.build/deploy.sh`

### 3. **Config Caching**
- All configuration files compiled into single cached file
- Removes config file parsing overhead
- Applied during deployment

### 4. **View Caching**
- Blade templates pre-compiled
- No runtime compilation needed
- Reduces response time significantly

### 5. **Optimized Autoloader**
- Composer autoloader optimized with class map
- Faster class loading
- Applied with `--optimize-autoloader` flag

### 6. **Response Caching**
- Detection results cached for 15 minutes
- Error responses cached for 5 minutes
- Reduces redundant HTTP requests to target sites
- Improves response time for repeated URL checks
- Cache can be bypassed with "Re-scan" button

### 7. **Parallel HTTP Requests**
- Multiple detection checks run simultaneously using `Http::pool()`
- Significantly faster than sequential requests
- All tool checks, 404 checks, and endpoint checks run in parallel

## Deployment

The app uses a custom deployment script at `.build/deploy.sh` that:
1. Installs dependencies with optimized autoloader
2. Caches configuration
3. Caches routes
4. Caches views
5. Caches events

## Performance Expectations

With these optimizations:
- **Response Time**: ~10-30ms (vs ~100-200ms without Octane)
- **Throughput**: ~1000-5000 req/sec (vs ~50-100 req/sec)
- **Memory**: More efficient due to persistent state
- **CPU**: Lower usage due to reduced compilation

## Laravel Cloud Configuration

The `Procfile` configures Octane to run with:
- **FrankenPHP** as the server
- **Auto workers** (scales with available CPU)
- **500 max requests** per worker before recycling

## Environment Variables

For production on Laravel Cloud, ensure these are set:
```env
APP_ENV=production
APP_DEBUG=false
OCTANE_SERVER=frankenphp
```

## Testing Performance

After deployment, you can test the performance:
```bash
# Using Apache Bench
ab -n 1000 -c 10 https://your-app.cloud.laravel.com/

# Using wrk
wrk -t4 -c100 -d30s https://your-app.cloud.laravel.com/
```

## Monitoring

Laravel Cloud provides built-in monitoring for:
- Request/response times
- Memory usage
- Error rates
- Throughput

Check these metrics in your Cloud dashboard after deployment.

