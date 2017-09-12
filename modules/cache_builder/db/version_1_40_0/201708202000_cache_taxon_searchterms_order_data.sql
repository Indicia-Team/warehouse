-- #slow script#

UPDATE cache_taxon_searchterms cts
SET taxonomic_sort_order=cttl.taxonomic_sort_order
FROM cache_taxa_taxon_lists cttl
WHERE cttl.id=cts.taxa_taxon_list_id;