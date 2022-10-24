-- #slow script#

UPDATE cache_taxon_searchterms cts
SET taxon_rank=tr.rank
FROM taxa_taxon_lists ttl
JOIN taxa t ON t.id=ttl.taxon_id AND t.deleted=false
JOIN taxon_ranks tr ON tr.id=t.taxon_rank_id AND t.deleted=false
WHERE ttl.deleted=false
AND ttl.id=cts.taxa_taxon_list_id;
