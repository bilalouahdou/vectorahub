-- Add billing_type column to payments table if it doesn't exist
ALTER TABLE payments ADD COLUMN billing_type ENUM('monthly', 'yearly') DEFAULT 'monthly' AFTER transaction_id;
