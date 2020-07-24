ALTER TABLE taxa
    ADD COLUMN freshwater_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN terrestrial_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN non_native_flag boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN taxa.freshwater_flag
    IS 'Set to true for freshwater species.';
COMMENT ON COLUMN taxa.terrestrial_flag
    IS 'Set to true for terrestrial species.';
COMMENT ON COLUMN taxa.non_native_flag
    IS 'Set to true for non-native species.';