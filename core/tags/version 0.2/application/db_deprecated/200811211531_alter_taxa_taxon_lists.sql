ALTER TABLE taxa
DROP COLUMN parent_id,
DROP COLUMN taxon_meaning_id,
DROP COLUMN taxonomic_sort_order,
DROP COLUMN preferred;

ALTER TABLE taxa_taxon_lists
ADD COLUMN parent_id integer, -- Foreign key to the taxa_taxon_lists table. Identifies the taxonomic parent, for example the genus of a species.
ADD COLUMN taxon_meaning_id integer, -- Foreign key to the taxon_meanings table. Identifies the meaning of this taxon record. Each group of taxa with the same meaning are considered synonymous.
ADD COLUMN taxonomic_sort_order integer, -- Provides a sort order which allows the taxon hierarchy to be displayed in taxonomic rather than alphabetical order.
ADD COLUMN preferred boolean NOT NULL DEFAULT false, -- Flag set to true if the name constitutes the preferred name when selected amongst all taxa that have the same meaning.
ADD CONSTRAINT fk_taxon_parent FOREIGN KEY (parent_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_taxon_taxon_meaning FOREIGN KEY (taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN taxa_taxon_lists.parent_id IS 'Foreign key to the taxa table. Identifies the taxonomic parent, for example the genus of a species.';
COMMENT ON COLUMN taxa_taxon_lists.taxon_meaning_id IS 'Foreign key to the taxon_meanings table. Identifies the meaning of this taxon record. Eacg group of taxa with the same meaning are considered synonymous.';
COMMENT ON COLUMN taxa_taxon_lists.taxonomic_sort_order IS 'Provides a sort order which allows the taxon hierarchy to be displayed in taxonomic rather than alphabetical order.';
COMMENT ON COLUMN taxa_taxon_lists.preferred IS 'Flag set to true if the name constitutes the preferred name when selected amongst all taxa that have the same meaning.';
