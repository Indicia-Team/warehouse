-- #slow script#

-- Build a complete hierarchy so we can spot changes required in the cache tables
WITH RECURSIVE q AS (
  SELECT distinct ttl1.id AS child_id, ttl1.taxon AS child_taxon, ttlpref.parent_id,
      ttlpref.id AS rank_ttl_id, t.taxon AS rank_taxon, tr.rank, tr.id AS taxon_rank_id, tr.sort_order AS taxon_rank_sort_order
  FROM cache_taxa_taxon_lists ttl1
  JOIN cache_taxa_taxon_lists ttlpref ON ttlpref.external_key=ttl1.external_key
    AND ttlpref.taxon_list_id=(SELECT uksi_taxon_list_id FROM uksi.uksi_settings) AND ttlpref.preferred=true AND ttlpref.allow_data_entry=true
  JOIN taxa_taxon_lists ttlprefraw ON ttlprefraw.id=ttlpref.id AND ttlprefraw.deleted=false
  JOIN taxa t ON t.id=ttlprefraw.taxon_id AND t.deleted=false AND t.deleted=false
  JOIN taxon_ranks tr ON tr.id=t.taxon_rank_id AND tr.deleted=false AND tr.deleted=false
  WHERE ttl1.taxon_list_id=(SELECT uksi_taxon_list_id FROM uksi.uksi_settings)
  UNION ALL
  SELECT q.child_id, q.child_taxon, ttl.parent_id,
      ttl.id AS rank_ttl_id, t.taxon AS rank_taxon, tr.rank, tr.id AS taxon_rank_id, tr.sort_order AS taxon_rank_sort_order
  from q
  JOIN taxa_taxon_lists ttl ON ttl.id=q.parent_id AND ttl.deleted=false AND ttl.taxon_list_id=(SELECT uksi_taxon_list_id FROM uksi.uksi_settings)
  JOIN taxa t ON t.id=ttl.taxon_id AND t.deleted=false AND t.deleted=false
  JOIN taxon_ranks tr ON tr.id=t.taxon_rank_id AND tr.deleted=false AND tr.deleted=false
) SELECT DISTINCT * INTO temporary rankupdate FROM q;

-- Apply hierarchical changes as required otherwise these could be missed by the simplistic
-- approach to identifying changed rows.
-- Update data for the higher taxa kingdom, order and family.
UPDATE cache_taxa_taxon_lists u
SET kingdom_taxa_taxon_list_id=ru.rank_ttl_id, kingdom_taxon=rank_taxon
FROM cache_taxa_taxon_lists cttl
LEFT JOIN rankupdate ru ON ru.child_id=cttl.preferred_taxa_taxon_list_id AND ru.rank='Kingdom'
where cttl.id=u.id
AND (
  coalesce(cttl.kingdom_taxa_taxon_list_id, 0)<>coalesce(ru.rank_ttl_id, 0)
  OR coalesce(cttl.kingdom_taxon, '')<>coalesce(rank_taxon, '')
);

UPDATE cache_taxa_taxon_lists u
SET order_taxa_taxon_list_id=ru.rank_ttl_id, order_taxon=rank_taxon
FROM cache_taxa_taxon_lists cttl
LEFT JOIN rankupdate ru ON ru.child_id=cttl.preferred_taxa_taxon_list_id AND ru.rank='Order'
where cttl.id=u.id
AND (
  coalesce(cttl.order_taxa_taxon_list_id, 0)<>coalesce(ru.rank_ttl_id, 0)
  OR coalesce(cttl.order_taxon, '')<>coalesce(rank_taxon, '')
);

UPDATE cache_taxa_taxon_lists u
SET family_taxa_taxon_list_id=ru.rank_ttl_id, family_taxon=rank_taxon
FROM cache_taxa_taxon_lists cttl
LEFT JOIN rankupdate ru ON ru.child_id=cttl.preferred_taxa_taxon_list_id AND ru.rank='Family'
where cttl.id=u.id
AND (
  coalesce(cttl.family_taxa_taxon_list_id, 0)<>coalesce(ru.rank_ttl_id, 0)
  OR coalesce(cttl.family_taxon, '')<>coalesce(rank_taxon, '')
);

UPDATE cache_occurrences_functional u
SET family_taxa_taxon_list_id=ru.rank_ttl_id
FROM cache_occurrences_functional co
LEFT JOIN rankupdate ru ON ru.child_id=co.preferred_taxa_taxon_list_id AND ru.rank='Family'
WHERE co.id=u.id
AND (
  coalesce(co.family_taxa_taxon_list_id, 0)<>coalesce(ru.rank_ttl_id, 0)
);

-- Update rank data for the same level.
UPDATE cache_taxa_taxon_lists u
SET taxon_rank_id=ru.taxon_rank_id, taxon_rank=ru.rank, taxon_rank_sort_order=ru.taxon_rank_sort_order
FROM rankupdate ru
where ru.child_id=u.preferred_taxa_taxon_list_id
AND ru.child_id=ru.rank_ttl_id
AND (coalesce(u.taxon_rank_id, 0) <> ru.taxon_rank_id
  OR coalesce(u.taxon_rank, '')<>ru.rank
  OR coalesce(u.taxon_rank_sort_order, 0)<>ru.taxon_rank_sort_order
);

UPDATE cache_taxon_searchterms u
SET taxon_rank_sort_order=ru.taxon_rank_sort_order
FROM rankupdate ru
where ru.child_id=u.preferred_taxa_taxon_list_id
AND ru.child_id=ru.rank_ttl_id
AND coalesce(u.taxon_rank_sort_order, 0)<>ru.taxon_rank_sort_order;

UPDATE cache_occurrences_nonfunctional u
SET taxon_rank_sort_order=ru.taxon_rank_sort_order
FROM rankupdate ru
where ru.child_id=u.preferred_taxa_taxon_list_id
AND ru.child_id=ru.rank_ttl_id
AND coalesce(u.taxon_rank_sort_order, 0)<>ru.taxon_rank_sort_order;

DROP TABLE rankupdate;