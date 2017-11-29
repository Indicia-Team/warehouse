ALTER TABLE workflow_metadata
  ADD COLUMN group_code character varying;

COMMENT ON COLUMN workflow_metadata.group_code IS 'Code identifying the group of websites which this metadata applies to.';

UPDATE workflow_metadata SET group_code=(SELECT group_code FROM workflow_events limit 1);

 ALTER TABLE workflow_metadata
  ALTER COLUMN group_code SET NOT NULL;

DROP VIEW gv_workflow_metadata;

CREATE VIEW gv_workflow_metadata AS
 SELECT wm.id, wm.group_code, wm.entity, wm.key, wm.key_value
   FROM workflow_metadata wm
  WHERE wm.deleted = false;