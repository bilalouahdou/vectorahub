-- Add yearly subscription plans
-- This script adds yearly versions of existing plans with discounts

-- First, let's add a new column to track billing period
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS billing_period VARCHAR(20) DEFAULT 'monthly';

-- Add a column for Stripe price ID
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(100);

-- Update existing plans to be monthly and add Stripe price IDs
UPDATE subscription_plans 
SET billing_period = 'monthly' 
WHERE billing_period IS NULL;

-- Insert yearly plans (with ~15% discount for yearly billing)
INSERT INTO subscription_plans (name, price, coin_limit, features, billing_period, stripe_price_id) VALUES 
('Ultimate - Yearly', 51.00, 2400, '200 vectorizations per month, Priority processing, Email support, HD output, 15% yearly discount', 'yearly', 'price_YEARLY_ULTIMATE_ID'),
('API Pro - Yearly', 153.00, 12000, '1000 vectorizations per month, API access, Priority processing, Premium support, Bulk operations, 15% yearly discount', 'yearly', 'price_YEARLY_API_PRO_ID');

-- Update monthly plans with Stripe price IDs (replace with your actual Stripe price IDs)
UPDATE subscription_plans 
SET stripe_price_id = 'price_MONTHLY_ULTIMATE_ID' 
WHERE name = 'Ultimate' AND billing_period = 'monthly';

UPDATE subscription_plans 
SET stripe_price_id = 'price_MONTHLY_API_PRO_ID' 
WHERE name = 'API Pro' AND billing_period = 'monthly';

-- Show all plans
SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans ORDER BY name, billing_period;
