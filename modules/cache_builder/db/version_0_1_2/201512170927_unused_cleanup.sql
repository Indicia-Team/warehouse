-- Clean up a few rarely or never used indexes
-- cache_occurrences table
DROP INDEX IF EXISTS ix_occurrences_search_name;

-- cache_taxa_taxon_lists
DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_search_name;
DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_cache_created_on;
DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_updated_on;

-- plus an unused column
ALTER TABLE cache_occurrences
DROP COLUMN search_name;