-- #slow script#
UPDATE cache_taxa_taxon_lists cttl
SET taxon_id=t.id,
  search_code=t.search_code
FROM taxa_taxon_lists ttl
JOIN taxa t on t.id=ttl.taxon_id
WHERE cttl.id=ttl.id;