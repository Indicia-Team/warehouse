-- #slow script#

UPDATE cache_occurrences o
SET family_taxa_taxon_list_id=cttl.family_taxa_taxon_list_id
FROM cache_taxa_taxon_lists cttl
WHERE cttl.id=o.taxa_taxon_list_id;