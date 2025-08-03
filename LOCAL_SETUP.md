# Local Development Setup

This guide will help you set up the VectraHub project for local development with Supabase.

## Prerequisites

- PHP 8.0 or higher
- Composer (for dependencies)
- Access to your Supabase project

## Setup Steps

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd test
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment Variables

Create a `.env` file in the root directory with your Supabase credentials:

```env
# Database Configuration (Supabase)
DATABASE_URL=postgresql://postgres:[YOUR-PASSWORD]@db.ozjbgmcxocvznfcttaty.supabase.co:5432/postgres

# Supabase Configuration
SUPABASE_URL=https://ozjbgmcxocvznfcttaty.supabase.co
SUPABASE_ANON_KEY=[YOUR-ANON-KEY]
SUPABASE_SERVICE_ROLE_KEY=[YOUR-SERVICE-ROLE-KEY]

# Application Settings
APP_ENV=development
APP_NAME=VectraHub
APP_URL=http://localhost

# File Upload Settings
UPLOAD_MAX_SIZE=52428800
SESSION_LIFETIME=86400
CSRF_TOKEN_EXPIRY=3600
```

### 4. Get Supabase Credentials

1. Go to your Supabase project dashboard
2. Navigate to Settings > Database
3. Copy the connection string and replace `[YOUR-PASSWORD]` with your database password
4. Go to Settings > API to get your API keys

### 5. Test Database Connection

Run the setup script to test your configuration:

```bash
php setup_local_db.php
```

### 6. Initialize Database (if needed)

If the setup script shows missing tables, run:

```bash
php scripts/init_database.php
```

### 7. Start Local Server

You can use PHP's built-in server or your preferred local server:

```bash
php -S localhost:8000
```

## Troubleshooting

### Database Connection Issues

1. **Check your DATABASE_URL**: Make sure the password is correct
2. **Verify Supabase access**: Ensure your IP is allowed in Supabase
3. **Check SSL mode**: Supabase requires SSL (`sslmode=require`)

### Registration Issues

If registration fails locally:

1. Run `php setup_local_db.php` to verify database connection
2. Check that subscription plans exist in the database
3. Verify that all required tables are created

### Common Errors

- **"Database connection failed"**: Check your `.env` file and Supabase credentials
- **"Table doesn't exist"**: Run the database initialization script
- **"Registration failed"**: Check the error logs in `php/php_errors.log`

## Production Deployment

For production deployment on Fly.io:

1. Set environment variables using Fly secrets:
   ```bash
   fly secrets set DATABASE_URL="your-supabase-url"
   fly secrets set SUPABASE_ANON_KEY="your-anon-key"
   fly secrets set SUPABASE_SERVICE_ROLE_KEY="your-service-role-key"
   ```

2. Deploy to Fly.io:
   ```bash
   fly deploy
   ```

## File Structure

```
test/
├── .env                    # Local environment variables (create this)
├── php/
│   ├── config.php         # Database configuration
│   ├── auth/
│   │   └── register.php   # Registration handler
│   └── utils.php          # Utility functions
├── scripts/
│   └── init_database.php  # Database initialization
└── setup_local_db.php     # Local setup script
```

## Support

If you encounter issues:

1. Check the error logs in `php/php_errors.log`
2. Run `php setup_local_db.php` to diagnose database issues
3. Verify your Supabase project settings 