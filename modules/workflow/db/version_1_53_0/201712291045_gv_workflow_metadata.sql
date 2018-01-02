CREATE OR REPLACE VIEW gv_workflow_metadata AS
  SELECT DISTINCT ON (wm.id) wm.id, wm.group_code, wm.entity,
    wm.key, wm.key_value, cttl.preferred_taxon as label
  FROM workflow_metadata wm
  LEFT JOIN cache_taxa_taxon_lists cttl ON cttl.external_key = wm.key_value and wm.key='taxa_taxon_list_external_key'
  WHERE wm.deleted = false;