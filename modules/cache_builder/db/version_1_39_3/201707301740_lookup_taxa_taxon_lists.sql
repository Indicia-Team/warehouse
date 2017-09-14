-- #slow script#
-- tagged as slow script so we can sure not switched till after data populated in search code column
DROP VIEW lookup_taxa_taxon_lists;

CREATE VIEW lookup_taxa_taxon_lists AS 
 SELECT taxa_taxon_list_id as id,
    taxon_meaning_id,
    taxon_list_id,
    original as taxon,
    authority,
    external_key, 
    search_code
   FROM cache_taxon_searchterms
  WHERE name_type IN ('L', 'S', 'V')
  AND simplified = false
  ORDER BY preferred DESC;

/***********************************************************************
 * NOTE - ensure that owner of lookup_taxa_taxon_lists is correctly set
 ***********************************************************************/