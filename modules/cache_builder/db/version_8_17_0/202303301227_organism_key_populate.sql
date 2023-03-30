-- #slow script#

UPDATE cache_taxa_taxon_lists cttl
SET organism_key=t.organism_key
FROM taxa t
WHERE t.id=cttl.taxon_id
AND t.organism_key IS NOT NULL;

UPDATE cache_taxon_searchterms cts
SET organism_key=cttl.organism_key
FROM cache_taxa_taxon_lists cttl
WHERE cttl.id=cts.taxa_taxon_list_id
AND cttl.organism_key IS NOT NULL;