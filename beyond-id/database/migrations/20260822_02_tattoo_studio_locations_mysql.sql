ALTER TABLE tattoo_studios
  ADD COLUMN latitude DECIMAL(9,6) NULL,
  ADD COLUMN longitude DECIMAL(9,6) NULL,
  ADD COLUMN website_url VARCHAR(500) NULL,
  ADD COLUMN booking_url VARCHAR(500) NULL,
  ADD COLUMN verification_status VARCHAR(32) NOT NULL DEFAULT 'unverified',
  ADD COLUMN verified_at DATETIME NULL;

UPDATE tattoo_studios SET
  latitude=CASE slug
    WHEN 'stonerinkk-ottawa' THEN 45.4294 WHEN 'obscura-tattoo-ottawa' THEN 45.4097 WHEN 'the-18th-ink-ottawa' THEN 45.3340
    WHEN 'sting-studio-ottawa' THEN 45.4340 WHEN 'sunken-star-tattoo-ottawa' THEN 45.4291 WHEN 'two-0-six-tattoo-ottawa' THEN 45.4045
    WHEN 'relic-ottawa' THEN 45.3910 WHEN 'sage-tattoo-ottawa' THEN 45.3610 WHEN 'barnstormer-studio-ottawa' THEN 45.4080
    WHEN 'adrenaline-toronto-queen-west' THEN 43.6508 WHEN 'adrenaline-montreal' THEN 45.4960 WHEN 'prana-tattoo-montreal' THEN 45.5116
    WHEN 'adrenaline-vancouver-granville' THEN 49.2785 WHEN 'ambassador-tattoo-calgary' THEN 51.0373 WHEN 'calgary-tattoo-company' THEN 50.9900
    WHEN 'got-ink-edmonton' THEN 53.5410 WHEN 'winnipeg-tattoo' THEN 49.8810 WHEN 'rites-of-passage-saskatoon' THEN 52.1200
    WHEN 'sin-on-skin-halifax' THEN 44.6446 ELSE latitude END,
  longitude=CASE slug
    WHEN 'stonerinkk-ottawa' THEN -75.6904 WHEN 'obscura-tattoo-ottawa' THEN -75.6949 WHEN 'the-18th-ink-ottawa' THEN -75.7790
    WHEN 'sting-studio-ottawa' THEN -75.6220 WHEN 'sunken-star-tattoo-ottawa' THEN -75.6914 WHEN 'two-0-six-tattoo-ottawa' THEN -75.7240
    WHEN 'relic-ottawa' THEN -75.7530 WHEN 'sage-tattoo-ottawa' THEN -75.7830 WHEN 'barnstormer-studio-ottawa' THEN -75.6920
    WHEN 'adrenaline-toronto-queen-west' THEN -79.3898 WHEN 'adrenaline-montreal' THEN -73.5790 WHEN 'prana-tattoo-montreal' THEN -73.5614
    WHEN 'adrenaline-vancouver-granville' THEN -123.1225 WHEN 'ambassador-tattoo-calgary' THEN -114.0820 WHEN 'calgary-tattoo-company' THEN -114.0700
    WHEN 'got-ink-edmonton' THEN -113.5740 WHEN 'winnipeg-tattoo' THEN -97.2120 WHEN 'rites-of-passage-saskatoon' THEN -106.6500
    WHEN 'sin-on-skin-halifax' THEN -63.5750 ELSE longitude END,
  website_url=instagram_url,booking_url=instagram_url,verification_status='verified',verified_at='2026-08-22 00:00:00'
WHERE slug IN ('stonerinkk-ottawa','obscura-tattoo-ottawa','the-18th-ink-ottawa','sting-studio-ottawa','sunken-star-tattoo-ottawa','two-0-six-tattoo-ottawa','relic-ottawa','sage-tattoo-ottawa','barnstormer-studio-ottawa','adrenaline-toronto-queen-west','adrenaline-montreal','prana-tattoo-montreal','adrenaline-vancouver-granville','ambassador-tattoo-calgary','calgary-tattoo-company','got-ink-edmonton','winnipeg-tattoo','rites-of-passage-saskatoon','sin-on-skin-halifax');
