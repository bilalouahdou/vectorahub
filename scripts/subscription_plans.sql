-- Clear existing plans and add the new subscription plans
DELETE FROM subscription_plans;

-- Insert the three subscription plans (monthly prices)
INSERT INTO subscription_plans (name, price, coin_limit, features) VALUES 
('Free', 0.00, 200, '200 free coins/month; basic vectorize only; no bulk; community support'),
('Ultimate', 5.00, 1000, 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support'),
('API Pro', 15.00, 1000, '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit');
