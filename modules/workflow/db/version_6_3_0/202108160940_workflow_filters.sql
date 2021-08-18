ALTER TABLE workflow_events
  ADD COLUMN attrs_filter_term text;

ALTER TABLE workflow_events
  ADD COLUMN attrs_filter_values text[];

ALTER TABLE workflow_events
ADD COLUMN location_ids_filter integer[];

COMMENT ON COLUMN workflow_events.attrs_filter_term IS
  'When this event should only trigger if a certain attribute value is present, specify the DwC term here which identifies the attribute to use (e.g. ReproductiveCondition or Stage). Typically used to limit bird events to breeding ReproductiveCondition terms.';

COMMENT ON COLUMN workflow_events.attrs_filter_values IS
  'When this event should only trigger if a certain attribute value is present, specify the list of triggering values here. A record matching any value in the list will trigger the event.';

COMMENT ON COLUMN workflow_events.location_ids_filter IS
  'When this event should only trigger if the record overlaps an indexed location boundary, specify the location ID or list of IDs here. Used to limit alerts to geographic areas.';