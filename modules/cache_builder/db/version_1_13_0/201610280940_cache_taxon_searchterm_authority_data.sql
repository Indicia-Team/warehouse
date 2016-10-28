-- #slow script#

UPDATE cache_taxon_searchterm cts
SET authority = t.authority,

FROM taxa_taxon_lists ttl
JOIN taxa t on t.id=ttl.taxon_id
WHERE ttl.id=cts.taxa_taxon_list_id;