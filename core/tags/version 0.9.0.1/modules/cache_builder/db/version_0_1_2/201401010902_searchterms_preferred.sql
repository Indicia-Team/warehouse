UPDATE cache_taxon_searchterms
SET preferred=ttl.preferred
FROM taxa_taxon_lists ttl
WHERE ttl.id=cache_taxon_searchterms.taxa_taxon_list_id;

DELETE FROM cache_taxon_searchterms WHERE taxa_taxon_list_id IN (
  SELECT id FROM taxa_taxon_lists WHERE deleted=true
);