ALTER TABLE cache_taxa_taxon_lists
    ADD COLUMN freshwater_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN terrestrial_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN non_native_flag boolean NOT NULL DEFAULT false;

ALTER TABLE cache_taxon_searchterms
    ADD COLUMN freshwater_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN terrestrial_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN non_native_flag boolean NOT NULL DEFAULT false;

ALTER TABLE cache_occurrences_functional
    ADD COLUMN freshwater_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN terrestrial_flag boolean NOT NULL DEFAULT false,
    ADD COLUMN non_native_flag boolean NOT NULL DEFAULT false;
