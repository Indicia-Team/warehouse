ALTER TABLE taxa ADD COLUMN marine_flag boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN taxa.marine_flag IS 'Set to true for marine species.';
