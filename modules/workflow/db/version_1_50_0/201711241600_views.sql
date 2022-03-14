
CREATE VIEW detail_workflow_metadata AS
 SELECT wm.id, wm.entity, wm.key, wm.key_value, wm.verification_delay_hours, wm.verifier_notifications_immediate,
    wm.log_all_communications
   FROM workflow_metadata wm
  WHERE wm.deleted = false;

