-- #slow script#

UPDATE cache_taxon_searchterms cts
SET external_key = cttl.external_key
FROM cache_taxa_taxon_lists cttl
WHERE cttl.id=cts.taxa_taxon_list_id
AND cttl.external_key IS NOT NULL;