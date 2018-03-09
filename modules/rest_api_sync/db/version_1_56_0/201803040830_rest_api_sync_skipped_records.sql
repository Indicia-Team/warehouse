-- Table: rest_api_sync_skipped_records

-- DROP TABLE rest_api_sync_skipped_records;

CREATE TABLE rest_api_sync_skipped_records
(
  id serial NOT NULL,
  server_id character varying(10) NOT NULL, -- Identifier of the server configuration being synced from.
  source_id character varying NOT NULL, -- Unique identifier of the source record on the remote server which was skipped.
  dest_table character varying NOT NULL, -- Name of the table the record should be destined for on this warehouse.
  error_message character varying NOT NULL, -- Error message which occurred when the record sync was attempted.
  current boolean NOT NULL DEFAULT true, -- Is this information still current? Set to false to indicate an error which previuosly occurred but is no longer an issue.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  CONSTRAINT pk_rest_api_sync_skipped_records PRIMARY KEY (id),
  CONSTRAINT fk_rest_api_sync_skipped_record_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE rest_api_sync_skipped_records
  IS 'List of records skipped by a Rest API Sync run, allowing them to be re-imported once problems have been fixed.';
COMMENT ON COLUMN rest_api_sync_skipped_records.server_id IS 'Identifier of the server configuration being synced from.';
COMMENT ON COLUMN rest_api_sync_skipped_records.source_id IS 'Unique identifier of the source record on the remote server which was skipped.';
COMMENT ON COLUMN rest_api_sync_skipped_records.dest_table IS 'Name of the table the record should be destined for on this warehouse.';
COMMENT ON COLUMN rest_api_sync_skipped_records.error_message IS 'Error message which occurred when the record sync was attempted.';
COMMENT ON COLUMN rest_api_sync_skipped_records.current IS 'Is this information still current? Set to false to indicate an error which previuosly occurred but is no longer an issue.';
COMMENT ON COLUMN rest_api_sync_skipped_records.created_on IS 'Date this record was created.';
COMMENT ON COLUMN rest_api_sync_skipped_records.created_by_id IS 'Foreign key to the users table (creator).';

CREATE OR REPLACE VIEW gv_rest_api_sync_skipped_records AS
 SELECT sr.id,
    sr.server_id,
    sr.source_id,
    sr.dest_table,
    sr.error_message,
    sr.created_on,
    u.username
   FROM rest_api_sync_skipped_records sr
   JOIN users u on u.id=sr.created_by_id
  WHERE sr.current = true;