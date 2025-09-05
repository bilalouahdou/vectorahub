-- Update subscription schema for Stripe integration
-- Add necessary columns for subscription management

-- Add columns to subscription_plans table
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS billing_period VARCHAR(20) DEFAULT 'monthly';

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(100);

-- Add columns to user_subscriptions table
ALTER TABLE user_subscriptions 
ADD COLUMN IF NOT EXISTS stripe_subscription_id VARCHAR(100);

ALTER TABLE user_subscriptions 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Clear existing plans and add new ones based on your Stripe setup
DELETE FROM subscription_plans WHERE price > 0;

-- Insert your actual Stripe-based plans
INSERT INTO subscription_plans (name, price, coin_limit, features, billing_period, stripe_price_id) VALUES 
-- Free plan (no Stripe integration needed)
('Free', 0.00, 200, '200 free coins/month; basic vectorize only; no bulk; community support', 'monthly', NULL),

-- VectraHub Black Pack
('VectraHub Black Pack', 5.00, 1000, 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support', 'monthly', 'price_1RtT9cJYJk34NKovi3qBfwh4'),
('VectraHub Black Pack - Yearly', 48.00, 12000, 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support; 20% yearly discount', 'yearly', 'price_1RtT9vJYJk34NKov8WwgiYGJ'),

-- VectraHub Pro
('VectraHub Pro', 9.99, 2000, 'Everything in Black Pack + Advanced features; Premium support; HD output', 'monthly', 'price_1RtAUjJYJk34NKov2o5zue7b'),
('VectraHub Pro - Yearly', 95.90, 24000, 'Everything in Black Pack + Advanced features; Premium support; HD output; 20% yearly discount', 'yearly', 'price_1RtAVoJYJk34NKovE86WXQG6'),

-- VectraHub API Pro
('VectraHub API Pro', 15.00, 1000, '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit', 'monthly', 'price_1RtAXiJYJk34NKovNS5cpLFf'),
('VectraHub API Pro - Yearly', 144.00, 12000, '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit; 20% yearly discount', 'yearly', 'price_1RtAXyJYJk34NKovkjntSOr7');

-- Show updated plans
SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans ORDER BY price, billing_period;
