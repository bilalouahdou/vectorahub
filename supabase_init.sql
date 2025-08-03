-- Supabase Database Initialization Script
-- Run this in your Supabase SQL Editor

-- Insert default subscription plans (only if they don't exist)
INSERT INTO subscription_plans (name, price, coin_limit, features) 
VALUES 
    ('Free', 0.00, 10, '10 vectorizations per month, Standard processing, Basic support'),
    ('Ultimate', 5.00, 200, '200 vectorizations per month, Priority processing, Email support, HD output'),
    ('API Pro', 15.00, 1000, '1000 vectorizations per month, API access, Priority processing, Premium support, Bulk operations')
ON CONFLICT (name) DO NOTHING;

-- Insert default system settings (only if they don't exist)
INSERT INTO system_settings (setting_key, setting_value) 
VALUES 
    ('app_name', 'VectraHub'),
    ('app_url', 'https://vectrahub.fly.dev'),
    ('upload_max_size', '5242880'),
    ('session_lifetime', '86400'),
    ('csrf_token_expiry', '3600'),
    ('default_free_plan_id', '1')
ON CONFLICT (setting_key) DO NOTHING;

-- Verify the data was inserted
SELECT 'Subscription Plans:' as info;
SELECT id, name, price, coin_limit FROM subscription_plans ORDER BY price;

SELECT 'System Settings:' as info;
SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key; 