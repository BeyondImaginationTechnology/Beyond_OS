-- Beyond Studio is the first online-first studio in the Beyond Tattoo directory.
INSERT INTO tattoo_studios
  (slug,name,description,address_line1,city,province,postal_code,country,phone,owner_display_name,owner_instagram_url,instagram_url,services,walk_ins,status,source_note,latitude,longitude,website_url,booking_url,verification_status,verified_at)
VALUES
  ('beyond-studio-nanaimo','Beyond Studio','An online-first tattoo studio connecting Nanaimo clients with thoughtful custom tattoo consultations and a growing roster of independent artists.','Online studio — Nanaimo service area','Nanaimo','British Columbia','V9R','Canada',NULL,'Beyond Studio team',NULL,NULL,'Custom tattooing, fine line, black and grey, colour, consultation',0,'active','Beyond Tattoo founding studio scaffold, launched 2026-08-30',49.1659,-123.9401,NULL,'/beyond-tattoo/book.php?studio=beyond-studio-nanaimo','verified',CURRENT_TIMESTAMP)
ON CONFLICT(slug) DO UPDATE SET
  name=excluded.name,description=excluded.description,address_line1=excluded.address_line1,city=excluded.city,province=excluded.province,
  postal_code=excluded.postal_code,services=excluded.services,status='active',source_note=excluded.source_note,latitude=excluded.latitude,
  longitude=excluded.longitude,website_url=excluded.website_url,booking_url=excluded.booking_url,verification_status='verified',updated_at=CURRENT_TIMESTAMP;
