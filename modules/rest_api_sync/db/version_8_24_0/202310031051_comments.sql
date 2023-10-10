COMMENT ON COLUMN rest_api_sync_taxon_mappings.restrict_to_survey_id IS 'ID of the survey being imported to that this mapping applies to. If null, applies to all imports.';
COMMENT ON COLUMN rest_api_sync_taxon_mappings.other_taxon_name IS 'Taxon name as provided by the other system.';
COMMENT ON COLUMN rest_api_sync_taxon_mappings.mapped_taxon_list_id IS 'ID of the taxon list the mapped name belongs to.';
COMMENT ON COLUMN rest_api_sync_taxon_mappings.mapped_taxon_name IS 'Name to be recorded when the other taxon name is found in an import.';
COMMENT ON COLUMN rest_api_sync_taxon_mappings.mapped_search_code IS 'Key for the name that is being recorded (optional). If present, used in preferred to the mapped taxon name.';
COMMENT ON COLUMN rest_api_sync_taxon_mappings.created_on IS 'Date this record was created.';
COMMENT ON COLUMN rest_api_sync_taxon_mappings.created_by_id IS 'Foreign key to the users table (creator).';