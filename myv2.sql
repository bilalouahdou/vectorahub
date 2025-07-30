BEGIN;

-- Drop in reverse-dependency order
DROP TABLE IF EXISTS coin_usage;
DROP TABLE IF EXISTS bulk_jobs;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS user_subscriptions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS image_jobs;
DROP TABLE IF EXISTS coupon_codes;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS users;

-- 1. users
CREATE TABLE users (
  id           SERIAL PRIMARY KEY,
  full_name    VARCHAR(100),
  email        VARCHAR(100) NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  profile_image VARCHAR(255),
  role         VARCHAR(10) NOT NULL DEFAULT 'user'  -- you could add CHECK (role IN ('user','admin'))
  /*, coins INT DEFAULT 200 -- if you need it */
);

-- 2. subscription_plans
CREATE TABLE subscription_plans (
  id           SERIAL PRIMARY KEY,
  name         VARCHAR(50),
  price        NUMERIC(10,2),
  coin_limit   INT,
  features     TEXT,
  created_at   TIMESTAMPTZ DEFAULT now()
);

-- 3. coupon_codes
CREATE TABLE coupon_codes (
  id                  SERIAL PRIMARY KEY,
  code                VARCHAR(50)    NOT NULL UNIQUE,
  type                VARCHAR(20)    NOT NULL DEFAULT 'discount' 
    CHECK (type IN ('discount','free_plan','free_upgrade')),
  discount_percent    INT            NOT NULL DEFAULT 0,
  discount_amount     NUMERIC(10,2)  NOT NULL DEFAULT 0,
  free_plan_id        INT            REFERENCES subscription_plans(id),
  free_duration_months INT           NOT NULL DEFAULT 1,
  valid_from          DATE           NOT NULL,
  valid_until         DATE           NOT NULL,
  max_uses            INT,
  current_uses        INT            NOT NULL DEFAULT 0,
  applicable_plans    TEXT           DEFAULT 'all',
  description         TEXT,
  created_at          TIMESTAMPTZ     DEFAULT now()
);

-- 4. system_settings
CREATE TABLE system_settings (
  id            SERIAL PRIMARY KEY,
  setting_key   VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  created_at    TIMESTAMPTZ  DEFAULT now(),
  updated_at    TIMESTAMPTZ  DEFAULT now()
);

-- 5. system_logs
CREATE TABLE system_logs (
  id           SERIAL PRIMARY KEY,
  type         VARCHAR(50)   NOT NULL,
  description  TEXT,
  user_id      INT           REFERENCES users(id),
  ip_address   VARCHAR(45),
  created_at   TIMESTAMPTZ   DEFAULT now()
);

-- 6. payments
CREATE TABLE payments (
  id            SERIAL PRIMARY KEY,
  user_id       INT           REFERENCES users(id),
  amount        NUMERIC(10,2),
  plan_id       INT           REFERENCES subscription_plans(id),
  payment_method VARCHAR(50),
  transaction_id VARCHAR(100),
  paid_at       TIMESTAMPTZ   DEFAULT now(),
  coupon_id     INT           REFERENCES coupon_codes(id)
);

-- 7. user_subscriptions
CREATE TABLE user_subscriptions (
  id                SERIAL PRIMARY KEY,
  user_id           INT  REFERENCES users(id),
  plan_id           INT  REFERENCES subscription_plans(id),
  active            BOOLEAN DEFAULT TRUE,
  start_date        DATE,
  end_date          DATE,
  auto_renew        BOOLEAN DEFAULT TRUE,
  coupon_id         INT  REFERENCES coupon_codes(id),
  is_free_from_coupon BOOLEAN DEFAULT FALSE
);

-- 8. image_jobs
CREATE TABLE image_jobs (
  id                SERIAL PRIMARY KEY,
  user_id           INT           REFERENCES users(id),
  original_image_path VARCHAR(255),
  original_filename VARCHAR(255),
  output_svg_path   VARCHAR(255),
  status            VARCHAR(15)   NOT NULL DEFAULT 'queued'
    CHECK (status IN ('queued','processing','done','failed')),
  created_at        TIMESTAMPTZ   DEFAULT now(),
  is_bulk           BOOLEAN       DEFAULT FALSE,
  bulk_group_id     VARCHAR(50),
  bulk_position     INT
);

-- 9. bulk_jobs
CREATE TABLE bulk_jobs (
  id              SERIAL PRIMARY KEY,
  user_id         INT     NOT NULL REFERENCES users(id),
  group_id        VARCHAR(50) NOT NULL UNIQUE,
  total_images    INT     NOT NULL,
  completed_images INT    NOT NULL DEFAULT 0,
  failed_images   INT     NOT NULL DEFAULT 0,
  status          VARCHAR(10) NOT NULL DEFAULT 'processing'
    CHECK (status IN ('processing','completed','failed')),
  created_at      TIMESTAMPTZ DEFAULT now(),
  completed_at    TIMESTAMPTZ
);

-- 10. api_keys
CREATE TABLE api_keys (
  id        SERIAL PRIMARY KEY,
  user_id   INT     REFERENCES users(id),
  api_key   VARCHAR(255) UNIQUE,
  active    BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMPTZ DEFAULT now()
);

-- 11. admin_logs
CREATE TABLE admin_logs (
  id         SERIAL PRIMARY KEY,
  admin_id   INT REFERENCES users(id),
  action     TEXT,
  timestamp  TIMESTAMPTZ DEFAULT now()
);

-- 12. activity_logs
CREATE TABLE activity_logs (
  id           SERIAL PRIMARY KEY,
  type         VARCHAR(50) NOT NULL,
  description  TEXT        NOT NULL,
  user_id      INT         REFERENCES users(id),
  created_at   TIMESTAMPTZ DEFAULT now()
);

-- 13. coin_usage
CREATE TABLE coin_usage (
  id            SERIAL PRIMARY KEY,
  user_id       INT REFERENCES users(id),
  image_job_id  INT REFERENCES image_jobs(id),
  coins_used    INT DEFAULT 1,
  created_at    TIMESTAMPTZ DEFAULT now()
);

COMMIT;

