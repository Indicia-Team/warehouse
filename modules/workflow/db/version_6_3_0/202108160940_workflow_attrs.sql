ALTER TABLE workflow_events
  ADD COLUMN attrs_filter json;

COMMENT ON COLUMN workflow_events.attrs_filter IS
  'List of occurrence attributes (identified by term, e.g. ReproductiveCondition, Stage or Sex) with the matching attribute values required to trigger this workflow event.'