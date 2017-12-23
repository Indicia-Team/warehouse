DROP VIEW gv_workflow_events;
CREATE VIEW gv_workflow_events AS
 SELECT we.id, we.entity,
   case we.event_type
     when 'S' THEN 'Initially set as workflow tracked record'
     when 'V' THEN 'Verification'
     when 'R' THEN 'Rejection'
     when 'U' THEN 'Unreleased'
     when 'P' THEN 'Pending review'
     when 'F' THEN 'Fully released'
     else we.event_type
   end as event_type,
   we.key, we.key_value, we.values, we.group_code
   FROM workflow_events we
  WHERE we.deleted = false;