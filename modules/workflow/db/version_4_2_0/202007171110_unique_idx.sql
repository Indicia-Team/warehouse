
DROP INDEX ix_unique_workflow_event;
CREATE UNIQUE INDEX ix_unique_workflow_event ON workflow_events (group_code, entity, event_type, key, key_value);