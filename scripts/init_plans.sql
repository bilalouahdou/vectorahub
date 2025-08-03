-- Insert default subscription plans
INSERT INTO subscription_plans (name, price, coin_limit, features) VALUES 
('Free', 0.00, 10, '10 vectorizations per month, Standard processing, Basic support'),
('Ultimate', 5.00, 200, '200 vectorizations per month, Priority processing, Email support, HD output'),
('API Pro', 15.00, 1000, '1000 vectorizations per month, API access, Priority processing, Premium support, Bulk operations');

-- Create a default admin user (password: admin123)
INSERT INTO users (full_name, email, password_hash, role) VALUES 
('Admin User', 'admin@vectorizeai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Assign Free plan to admin user
INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date) VALUES 
(1, 1, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year');
