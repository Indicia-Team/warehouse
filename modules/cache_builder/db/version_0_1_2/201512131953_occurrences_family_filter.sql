-- #slow script#
-- Adds family_taxa_taxon_list_id to cache_occurrences since it makes a big improvement to filtering
ALTER TABLE cache_occurrences
  ADD family_taxa_taxon_list_id integer NULL;

UPDATE cache_occurrences o
SET family_taxa_taxon_list_id=cttl.family_taxa_taxon_list_id
FROM cache_taxa_taxon_lists cttl
WHERE cttl.id=o.taxa_taxon_list_id;