ALTER TABLE workflow_events
  ADD COLUMN attrs_filter_term text;

ALTER TABLE workflow_events
  ADD COLUMN attrs_filter_values text[];


COMMENT ON COLUMN workflow_events.attrs_filter_term IS
  'When this event should only trigger if a certain attribute value is present, specify the DwC term here which identifies the attribute to use (e.g. ReproductiveCondition or Stage). Typically used to limit bird events to breeding ReproductiveCondition terms.';

COMMENT ON COLUMN workflow_events.attrs_filter_values IS
  'When this event should only trigger if a certain attribute value is present, specify the list of triggering values here. A record matching any value in the list will trigger the event.';