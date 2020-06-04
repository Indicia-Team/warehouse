-- Adds family_taxa_taxon_list_id to cache_occurrences since it makes a big improvement to filtering
ALTER TABLE cache_occurrences
  ADD family_taxa_taxon_list_id integer NULL;