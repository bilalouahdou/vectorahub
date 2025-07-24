CREATE DATABASE vectorize;
USE vectorize;
-- USERS TABLE
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  full_name VARCHAR(100),
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  profile_image VARCHAR(255),
  role ENUM('user', 'admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SUBSCRIPTION PLANS TABLE
CREATE TABLE subscription_plans (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50),          -- Free, Ultimate, API Pro
  price DECIMAL(10, 2),      -- 0.00, 5.00, 15.00
  coin_limit INT,            -- e.g. 200 for Free
  features TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- USER SUBSCRIPTIONS TABLE
CREATE TABLE user_subscriptions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  plan_id INT,
  active BOOLEAN DEFAULT TRUE,
  start_date DATE,
  end_date DATE,
  auto_renew BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- IMAGE JOBS TABLE
CREATE TABLE image_jobs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  original_image_path VARCHAR(255),
  output_svg_path VARCHAR(255),
  status ENUM('queued', 'processing', 'done', 'failed') DEFAULT 'queued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- COIN USAGE TABLE
CREATE TABLE coin_usage (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  image_job_id INT,
  coins_used INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (image_job_id) REFERENCES image_jobs(id)
);

-- PAYMENTS TABLE
CREATE TABLE payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  amount DECIMAL(10,2),
  plan_id INT,
  payment_method VARCHAR(50),     -- e.g., 'stripe', 'paypal'
  transaction_id VARCHAR(100),
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- API KEYS TABLE
CREATE TABLE api_keys (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  api_key VARCHAR(255) UNIQUE,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ADMIN LOGS TABLE (optional)
CREATE TABLE admin_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  admin_id INT,
  action TEXT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id)
);
