-- update field size to reflect previous change to taxa_taxon_lists table
ALTER TABLE cache_taxa_taxon_lists ALTER taxonomic_sort_order TYPE bigint;
ALTER TABLE cache_occurrences ALTER taxonomic_sort_order TYPE bigint;