CREATE INDEX IF NOT EXISTS ix_rest_api_sync_skipped_records_source ON rest_api_sync_skipped_records(server_id, source_id, dest_table);