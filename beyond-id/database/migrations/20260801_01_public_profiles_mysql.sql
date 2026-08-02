ALTER TABLE profiles
  ADD COLUMN IF NOT EXISTS username VARCHAR(80) NULL AFTER bio,
  ADD COLUMN IF NOT EXISTS public_profile_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER username,
  ADD COLUMN IF NOT EXISTS creator_links TEXT NULL AFTER public_profile_enabled,
  ADD COLUMN IF NOT EXISTS creator_verified_at DATETIME NULL AFTER creator_links,
  ADD COLUMN IF NOT EXISTS seller_verified_at DATETIME NULL AFTER creator_verified_at;

CREATE UNIQUE INDEX idx_profiles_username ON profiles(username);
