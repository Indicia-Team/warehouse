

DROP SEQUENCE IF EXISTS workflow_events_id_seq CASCADE;
DROP SEQUENCE IF EXISTS workflow_undo_id_seq CASCADE;
DROP SEQUENCE IF EXISTS workflow_metadata_id_seq CASCADE;

DROP TABLE IF EXISTS workflow_events CASCADE;
DROP TABLE IF EXISTS workflow_undo CASCADE;
DROP TABLE IF EXISTS workflow_metadata CASCADE;

DELETE from system
  WHERE "name"='workflow';
