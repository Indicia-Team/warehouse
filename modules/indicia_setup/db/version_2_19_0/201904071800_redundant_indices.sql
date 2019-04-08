-- #slow script#

-- Remove indexes that are not being uses.
DROP INDEX IF EXISTS ix_cache_occurrences_functional_verify_family;
DROP INDEX IF EXISTS ix_cache_occurrences_functional_family_taxa_taxon_list_id;