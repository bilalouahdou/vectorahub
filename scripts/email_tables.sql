-- Email tracking and webhook support tables for VectraHub

-- Add email-related columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64),
ADD COLUMN IF NOT EXISTS email_bounced BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS bounce_reason TEXT,
ADD COLUMN IF NOT EXISTS email_complained BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_unsubscribed BOOLEAN DEFAULT FALSE;

-- Add warning_sent and last_notification_sent to user_subscriptions
ALTER TABLE user_subscriptions 
ADD COLUMN IF NOT EXISTS warning_sent BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS last_notification_sent TIMESTAMP;

-- Create email events table for webhook tracking
CREATE TABLE IF NOT EXISTS email_events (
    id SERIAL PRIMARY KEY,
    email_id VARCHAR(255),
    event_type VARCHAR(50) NOT NULL,
    email_address VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Unique constraint to prevent duplicate events
    UNIQUE(email_id, event_type)
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_email_events_email_address ON email_events(email_address);
CREATE INDEX IF NOT EXISTS idx_email_events_event_type ON email_events(event_type);
CREATE INDEX IF NOT EXISTS idx_email_events_timestamp ON email_events(timestamp);

CREATE INDEX IF NOT EXISTS idx_users_email_verified ON users(email_verified);
CREATE INDEX IF NOT EXISTS idx_users_email_verification_token ON users(email_verification_token);
CREATE INDEX IF NOT EXISTS idx_users_email_bounced ON users(email_bounced);

CREATE INDEX IF NOT EXISTS idx_user_subscriptions_expires_at ON user_subscriptions(expires_at);
CREATE INDEX IF NOT EXISTS idx_user_subscriptions_warning_sent ON user_subscriptions(warning_sent);

-- Insert default email preferences (if not exists)
INSERT INTO system_settings (setting_key, setting_value, description) 
VALUES 
    ('email_from_name', 'VectraHub', 'Default from name for emails'),
    ('email_from_address', 'noreply@vectrahub.online', 'Default from address for emails'),
    ('email_admin_address', 'admin@vectrahub.online', 'Admin email for incident notifications'),
    ('email_bounce_threshold', '3', 'Number of bounces before marking email as invalid'),
    ('email_daily_limit', '1000', 'Daily email sending limit')
ON CONFLICT (setting_key) DO NOTHING;

-- Create email templates table (optional - for storing templates in DB)
CREATE TABLE IF NOT EXISTS email_templates (
    id SERIAL PRIMARY KEY,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT,
    variables JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default email templates
INSERT INTO email_templates (template_name, subject, html_content, variables) 
VALUES 
    ('welcome', 'Welcome to VectraHub - Verify Your Email', '', '["user_name", "verification_url", "app_url"]'),
    ('subscription_expired', 'Your VectraHub Subscription Has Expired', '', '["user_name", "plan_name", "billing_url", "app_url"]'),
    ('incident', 'VectraHub Incident Alert', '', '["error_message", "user_info", "request_info", "timestamp", "app_url"]')
ON CONFLICT (template_name) DO NOTHING;

-- Grant permissions
GRANT SELECT, INSERT, UPDATE ON email_events TO postgres;
GRANT SELECT, INSERT, UPDATE, DELETE ON email_templates TO postgres;
GRANT USAGE, SELECT ON SEQUENCE email_events_id_seq TO postgres;
GRANT USAGE, SELECT ON SEQUENCE email_templates_id_seq TO postgres;
