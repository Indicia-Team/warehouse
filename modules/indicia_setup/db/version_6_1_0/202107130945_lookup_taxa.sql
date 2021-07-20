CREATE OR REPLACE VIEW lookup_taxa_taxon_lists AS
  SELECT ttl.id,
      ttl.taxon_meaning_id,
      ttl.taxon_list_id,
      t.taxon || COALESCE(' ' || t.attribute, '') as taxon,
      t.authority,
      t.external_key,
      t.search_code
    FROM taxa_taxon_lists ttl
    JOIN taxa t on t.id=ttl.taxon_id AND t.deleted=false
    WHERE ttl.deleted=false
    ORDER BY ttl.allow_data_entry DESC, ttl.preferred DESC;