ALTER TABLE profiles ADD COLUMN username TEXT;
ALTER TABLE profiles ADD COLUMN public_profile_enabled INTEGER NOT NULL DEFAULT 0;
ALTER TABLE profiles ADD COLUMN creator_links TEXT;
ALTER TABLE profiles ADD COLUMN creator_verified_at TEXT;
ALTER TABLE profiles ADD COLUMN seller_verified_at TEXT;
CREATE UNIQUE INDEX IF NOT EXISTS idx_profiles_username ON profiles(username);
