-- Supabase Database Initialization Script
-- Run this in your Supabase SQL Editor to set up basic data

-- 1. Insert default subscription plans (only if they don't exist)
INSERT INTO subscription_plans (name, price, coin_limit, features)
VALUES
    ('Free', 0.00, 10, '10 vectorizations per month, Standard processing, Basic support'),
    ('Ultimate', 5.00, 200, '200 vectorizations per month, Priority processing, Email support, HD output'),
    ('API Pro', 15.00, 1000, '1000 vectorizations per month, API access, Priority processing, Premium support, Bulk operations')
ON CONFLICT (name) DO NOTHING;

-- 2. Insert default system settings (only if they don't exist)
INSERT INTO system_settings (setting_key, setting_value)
VALUES
    ('app_name', 'VectraHub'),
    ('app_url', 'https://vectrahub.fly.dev'),
    ('upload_max_size', '5242880'),
    ('session_lifetime', '86400'),
    ('csrf_token_expiry', '3600'),
    ('default_free_plan_id', '1')
ON CONFLICT (setting_key) DO NOTHING;

-- 3. Create a test admin user (only if doesn't exist)
INSERT INTO users (full_name, email, password_hash, role)
VALUES (
    'Admin User',
    'admin@vectrahub.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'admin'
)
ON CONFLICT (email) DO NOTHING;

-- 4. Create a test regular user (only if doesn't exist)
INSERT INTO users (full_name, email, password_hash, role)
VALUES (
    'Test User',
    'user@vectrahub.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: user123
    'user'
)
ON CONFLICT (email) DO NOTHING;

-- 5. Assign free subscription to test user
INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date)
SELECT 
    u.id,
    sp.id,
    TRUE,
    CURRENT_DATE,
    CURRENT_DATE + INTERVAL '1 month'
FROM users u
CROSS JOIN subscription_plans sp
WHERE u.email = 'user@vectrahub.com' 
  AND sp.name = 'Free'
ON CONFLICT (user_id, plan_id) DO NOTHING;

-- 6. Add some sample system logs
INSERT INTO system_logs (type, description, user_id, ip_address)
VALUES
    ('info', 'System initialized successfully', NULL, '127.0.0.1'),
    ('info', 'Admin user created', (SELECT id FROM users WHERE email = 'admin@vectrahub.com'), '127.0.0.1'),
    ('info', 'Test user registered', (SELECT id FROM users WHERE email = 'user@vectrahub.com'), '127.0.0.1'),
    ('info', 'Database setup completed', NULL, '127.0.0.1');

-- 7. Verify the data was inserted
SELECT 'Subscription Plans:' as info;
SELECT id, name, price, coin_limit FROM subscription_plans ORDER BY price;

SELECT 'System Settings:' as info;
SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key;

SELECT 'Users:' as info;
SELECT id, full_name, email, role FROM users ORDER BY id;

SELECT 'User Subscriptions:' as info;
SELECT us.id, u.email, sp.name as plan_name, us.active, us.start_date, us.end_date
FROM user_subscriptions us
JOIN users u ON us.user_id = u.id
JOIN subscription_plans sp ON us.plan_id = sp.id
ORDER BY us.id;

SELECT 'System Logs:' as info;
SELECT id, type, description, created_at FROM system_logs ORDER BY created_at DESC LIMIT 5; 