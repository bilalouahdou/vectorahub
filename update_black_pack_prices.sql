-- Update VectraHub Black Pack Price IDs
-- This script updates the stripe_price_id for VectraHub Black Pack plans only

-- Update monthly Black Pack
UPDATE subscription_plans 
SET stripe_price_id = 'price_1RtT9cJYJk34NKovi3qBfwh4'
WHERE name = 'VectraHub Black Pack' AND billing_period = 'monthly';

-- Update yearly Black Pack  
UPDATE subscription_plans 
SET stripe_price_id = 'price_1RtT9vJYJk34NKov8WwgiYGJ'
WHERE name = 'VectraHub Black Pack - Yearly' AND billing_period = 'yearly';

-- Verify the updates
SELECT id, name, billing_period, stripe_price_id 
FROM subscription_plans 
WHERE name LIKE '%Black Pack%'
ORDER BY name, billing_period;

