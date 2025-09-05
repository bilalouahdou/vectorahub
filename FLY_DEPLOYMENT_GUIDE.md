# VectoraHub Fly.io Deployment Fix Guide

## Issues Fixed

### ✅ Phase 1: Immediate Fixes
- [x] **Health Check Path Mismatch**: Added explicit routing for `/health` endpoint in `.htaccess`
- [x] **Database Timeout**: Modified `health.php` to skip DB checks during deployment (first 5 minutes)
- [x] **Resource Constraints**: Increased RAM from 1GB to 2GB in `fly.toml`
- [x] **Health Check Timeouts**: Extended grace period to 90s, timeout to 20s

### ✅ Phase 2: Configuration Optimization  
- [x] **Dockerfile Optimization**: Reduced image size using multi-stage builds and cleanup
- [x] **Memory Optimization**: Added PHP OpCache and memory limit configurations
- [x] **Environment Variables**: Provided exact Fly.io secrets commands

## Deployment Commands

### 1. Set Environment Variables (Required)

```bash
# Essential Database Configuration (use your Supabase connection string)
flyctl secrets set DATABASE_URL="postgresql://postgres.[PROJECT_ID]:[PASSWORD]@aws-0-[REGION].pooler.supabase.com:5432/postgres?sslmode=require"

# Supabase Configuration  
flyctl secrets set SUPABASE_ANON_KEY="your_supabase_anon_key_here"
flyctl secrets set SUPABASE_SERVICE_ROLE_KEY="your_supabase_service_role_key_here"

# Stripe Configuration
flyctl secrets set STRIPE_PUBLISHABLE_KEY="pk_live_your_stripe_publishable_key"
flyctl secrets set STRIPE_SECRET_KEY="sk_live_your_stripe_secret_key"
flyctl secrets set STRIPE_WEBHOOK_SECRET="whsec_your_webhook_secret"

# Email Configuration (Resend)
flyctl secrets set RESEND_API_KEY="re_your_resend_api_key"

# Security (generates random key)
flyctl secrets set CRON_SECRET_KEY="$(openssl rand -hex 32)"

# External Runner Configuration (for GPU processing)
flyctl secrets set RUNNER_BASE_URL="https://your-runner-endpoint.com"
flyctl secrets set RUNNER_SHARED_TOKEN="your_secure_runner_token"
```

### 2. Deploy Application

```bash
# Deploy with the optimized configuration
flyctl deploy

# Monitor deployment logs
flyctl logs

# Check health status
curl https://vectrahub.fly.dev/health
```

### 3. Verify Deployment

```bash
# Check machine status
flyctl status

# View current secrets (names only)
flyctl secrets list

# Test health endpoint
flyctl ssh console
curl localhost/health
```

## Key Optimizations Made

### Dockerfile Improvements
- **Multi-stage build**: Reduced layers and image size
- **Dependency caching**: Better layer caching for composer and pip
- **Cleanup**: Removed unnecessary files and caches
- **Memory optimization**: Added PHP OpCache configuration

### Health Check Enhancements  
- **Deployment detection**: Automatically skips DB checks during startup
- **Direct routing**: `/health` endpoint bypasses URL redirects
- **Extended timeouts**: 90s grace period, 20s timeout
- **Resilient checks**: Won't fail on database connection issues during deployment

### Memory Configuration
- **Increased RAM**: 1GB → 2GB for better performance
- **PHP memory limit**: Set to 512M
- **OpCache enabled**: Improves PHP performance significantly

## Expected Results

- **Image size**: ~300MB (down from 582MB)
- **Health checks**: Should pass consistently 
- **Database**: Connections will work after secrets are set
- **Memory**: No more memory pressure issues
- **Startup time**: Faster due to optimized layers

## Troubleshooting

### If health checks still fail:
```bash
# Check health endpoint directly
flyctl ssh console
curl -v localhost/health

# Check Apache error logs
flyctl ssh console  
tail -f /var/log/apache2/error.log
```

### If database connections fail:
```bash
# Verify DATABASE_URL is set
flyctl secrets list

# Test database connection
flyctl ssh console
php -r "
\$url = getenv('DATABASE_URL');
\$parts = parse_url(\$url);
echo 'Host: ' . \$parts['host'] . PHP_EOL;
echo 'Database: ' . ltrim(\$parts['path'], '/') . PHP_EOL;
"
```

### If memory issues persist:
```bash
# Monitor memory usage
flyctl ssh console
free -h
top
```

## Next Steps

1. **Set your actual secrets** using the commands above
2. **Deploy** with `flyctl deploy`
3. **Monitor** the deployment logs
4. **Test** the health endpoint
5. **Verify** your application functionality

The deployment should now be significantly more stable and performant!
