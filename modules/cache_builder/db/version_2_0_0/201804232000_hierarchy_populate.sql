-- #slow script#

DROP TABLE IF EXISTS ttl_path;

WITH RECURSIVE q AS (
  SELECT distinct ttlpref.id AS child_pref_ttl_id, ttlpref.parent_id,
      ttlpref.taxon_meaning_id AS rank_taxon_meaning_id, tr.sort_order AS taxon_rank_sort_order, ttlpref.taxon_list_id
  FROM taxa_taxon_lists ttlpref
  JOIN taxa t ON t.id=ttlpref.taxon_id and t.deleted=false and t.deleted=false
  JOIN taxon_ranks tr ON tr.id=t.taxon_rank_id and tr.deleted=false and tr.deleted=false
  WHERE ttlpref.preferred=true
  AND ttlpref.deleted=false
  UNION ALL
  SELECT q.child_pref_ttl_id, ttl.parent_id,
      ttl.taxon_meaning_id AS rank_taxon_meaning_id, tr.sort_order AS taxon_rank_sort_order, ttl.taxon_list_id
  FROM q
  JOIN taxa_taxon_lists ttl ON ttl.id=q.parent_id and ttl.deleted=false and ttl.taxon_list_id=q.taxon_list_id
  JOIN taxa t ON t.id=ttl.taxon_id and t.deleted=false and t.deleted=false
  JOIN taxon_ranks tr ON tr.id=t.taxon_rank_id and tr.deleted=false and tr.deleted=false
)
SELECT child_pref_ttl_id, array_agg(rank_taxon_meaning_id order by taxon_rank_sort_order) as path
INTO TEMPORARY ttl_path
FROM q
GROUP BY child_pref_ttl_id
ORDER BY child_pref_ttl_id;

INSERT INTO cache_taxon_paths (taxon_meaning_id, taxon_list_id, external_key, path)
SELECT DISTINCT ON (cttl.taxon_meaning_id, cttl.taxon_list_id) cttl.taxon_meaning_id, cttl.taxon_list_id, cttl.external_key, tp.path
FROM cache_taxa_taxon_lists cttl
JOIN ttl_path tp ON tp.child_pref_ttl_id=cttl.id;

