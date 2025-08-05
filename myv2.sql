-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.activity_logs (
  id integer NOT NULL DEFAULT nextval('activity_logs_id_seq'::regclass),
  type character varying NOT NULL,
  description text NOT NULL,
  user_id integer,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT activity_logs_pkey PRIMARY KEY (id),
  CONSTRAINT activity_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.ad_views (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  user_id integer NOT NULL,
  view_count integer NOT NULL DEFAULT 0,
  last_viewed_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  view_date date DEFAULT ((last_viewed_at AT TIME ZONE 'UTC'::text))::date,
  CONSTRAINT ad_views_pkey PRIMARY KEY (id),
  CONSTRAINT ad_views_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.admin_logs (
  id integer NOT NULL DEFAULT nextval('admin_logs_id_seq'::regclass),
  admin_id integer,
  action text,
  timestamp timestamp with time zone DEFAULT now(),
  CONSTRAINT admin_logs_pkey PRIMARY KEY (id),
  CONSTRAINT admin_logs_admin_id_fkey FOREIGN KEY (admin_id) REFERENCES public.users(id)
);
CREATE TABLE public.api_keys (
  id integer NOT NULL DEFAULT nextval('api_keys_id_seq'::regclass),
  user_id integer,
  api_key character varying UNIQUE,
  active boolean DEFAULT true,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT api_keys_pkey PRIMARY KEY (id),
  CONSTRAINT api_keys_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.bulk_jobs (
  id integer NOT NULL DEFAULT nextval('bulk_jobs_id_seq'::regclass),
  user_id integer NOT NULL,
  group_id character varying NOT NULL UNIQUE,
  total_images integer NOT NULL,
  completed_images integer NOT NULL DEFAULT 0,
  failed_images integer NOT NULL DEFAULT 0,
  status character varying NOT NULL DEFAULT 'processing'::character varying CHECK (status::text = ANY (ARRAY['processing'::character varying, 'completed'::character varying, 'failed'::character varying]::text[])),
  created_at timestamp with time zone DEFAULT now(),
  completed_at timestamp with time zone,
  CONSTRAINT bulk_jobs_pkey PRIMARY KEY (id),
  CONSTRAINT bulk_jobs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.coin_usage (
  id integer NOT NULL DEFAULT nextval('coin_usage_id_seq'::regclass),
  user_id integer,
  image_job_id integer,
  coins_used integer DEFAULT 1,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT coin_usage_pkey PRIMARY KEY (id),
  CONSTRAINT coin_usage_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id),
  CONSTRAINT coin_usage_image_job_id_fkey FOREIGN KEY (image_job_id) REFERENCES public.image_jobs(id)
);
CREATE TABLE public.coupon_codes (
  id integer NOT NULL DEFAULT nextval('coupon_codes_id_seq'::regclass),
  code character varying NOT NULL UNIQUE,
  type character varying NOT NULL DEFAULT 'discount'::character varying CHECK (type::text = ANY (ARRAY['discount'::character varying, 'free_plan'::character varying, 'free_upgrade'::character varying]::text[])),
  discount_percent integer NOT NULL DEFAULT 0,
  discount_amount numeric NOT NULL DEFAULT 0,
  free_plan_id integer,
  free_duration_months integer NOT NULL DEFAULT 1,
  valid_from date NOT NULL,
  valid_until date NOT NULL,
  max_uses integer,
  current_uses integer NOT NULL DEFAULT 0,
  applicable_plans text DEFAULT 'all'::text,
  description text,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT coupon_codes_pkey PRIMARY KEY (id),
  CONSTRAINT coupon_codes_free_plan_id_fkey FOREIGN KEY (free_plan_id) REFERENCES public.subscription_plans(id)
);
CREATE TABLE public.image_jobs (
  id integer NOT NULL DEFAULT nextval('image_jobs_id_seq'::regclass),
  user_id integer,
  original_image_path character varying,
  original_filename character varying,
  output_svg_path character varying,
  status character varying NOT NULL DEFAULT 'queued'::character varying CHECK (status::text = ANY (ARRAY['queued'::character varying, 'processing'::character varying, 'done'::character varying, 'failed'::character varying]::text[])),
  created_at timestamp with time zone DEFAULT now(),
  is_bulk boolean DEFAULT false,
  bulk_group_id character varying,
  bulk_position integer,
  CONSTRAINT image_jobs_pkey PRIMARY KEY (id),
  CONSTRAINT image_jobs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.payments (
  id integer NOT NULL DEFAULT nextval('payments_id_seq'::regclass),
  user_id integer,
  amount numeric,
  plan_id integer,
  payment_method character varying,
  transaction_id character varying,
  paid_at timestamp with time zone DEFAULT now(),
  coupon_id integer,
  CONSTRAINT payments_pkey PRIMARY KEY (id),
  CONSTRAINT payments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id),
  CONSTRAINT payments_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES public.subscription_plans(id),
  CONSTRAINT payments_coupon_id_fkey FOREIGN KEY (coupon_id) REFERENCES public.coupon_codes(id)
);
CREATE TABLE public.referral_events (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  referrer_user_id integer,
  referred_user_id integer,
  event_type character varying NOT NULL,
  event_data jsonb,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT referral_events_pkey PRIMARY KEY (id),
  CONSTRAINT referral_events_referrer_user_id_fkey FOREIGN KEY (referrer_user_id) REFERENCES public.users(id),
  CONSTRAINT referral_events_referred_user_id_fkey FOREIGN KEY (referred_user_id) REFERENCES public.users(id)
);
CREATE TABLE public.referral_links (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  user_id integer NOT NULL,
  referral_code character varying NOT NULL UNIQUE,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT referral_links_pkey PRIMARY KEY (id),
  CONSTRAINT referral_links_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.referral_rewards (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  user_id integer NOT NULL,
  referred_user_id integer NOT NULL,
  reward_type character varying NOT NULL,
  amount numeric NOT NULL,
  status character varying NOT NULL DEFAULT 'pending'::character varying,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  awarded_at timestamp with time zone,
  CONSTRAINT referral_rewards_pkey PRIMARY KEY (id),
  CONSTRAINT referral_rewards_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id),
  CONSTRAINT referral_rewards_referred_user_id_fkey FOREIGN KEY (referred_user_id) REFERENCES public.users(id)
);
CREATE TABLE public.subscription_plans (
  id integer NOT NULL DEFAULT nextval('subscription_plans_id_seq'::regclass),
  name character varying UNIQUE,
  price numeric,
  coin_limit integer,
  features text,
  created_at timestamp with time zone DEFAULT now(),
  unlimited_black_images boolean DEFAULT false,
  CONSTRAINT subscription_plans_pkey PRIMARY KEY (id)
);
CREATE TABLE public.system_logs (
  id integer NOT NULL DEFAULT nextval('system_logs_id_seq'::regclass),
  type character varying NOT NULL,
  description text,
  user_id integer,
  ip_address character varying,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT system_logs_pkey PRIMARY KEY (id),
  CONSTRAINT system_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);
CREATE TABLE public.system_settings (
  id integer NOT NULL DEFAULT nextval('system_settings_id_seq'::regclass),
  setting_key character varying NOT NULL UNIQUE,
  setting_value text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT system_settings_pkey PRIMARY KEY (id)
);
CREATE TABLE public.user_subscriptions (
  id integer NOT NULL DEFAULT nextval('user_subscriptions_id_seq'::regclass),
  user_id integer,
  plan_id integer,
  active boolean DEFAULT true,
  start_date date,
  end_date date,
  auto_renew boolean DEFAULT true,
  coupon_id integer,
  is_free_from_coupon boolean DEFAULT false,
  CONSTRAINT user_subscriptions_pkey PRIMARY KEY (id),
  CONSTRAINT user_subscriptions_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id),
  CONSTRAINT user_subscriptions_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES public.subscription_plans(id),
  CONSTRAINT user_subscriptions_coupon_id_fkey FOREIGN KEY (coupon_id) REFERENCES public.coupon_codes(id)
);
CREATE TABLE public.users (
  id integer NOT NULL DEFAULT nextval('users_id_seq'::regclass),
  full_name character varying,
  email character varying NOT NULL UNIQUE,
  password_hash text NOT NULL,
  profile_image character varying,
  role character varying NOT NULL DEFAULT 'user'::character varying,
  CONSTRAINT users_pkey PRIMARY KEY (id)
);