DROP VIEW list_notifications;

ALTER TABLE notifications ALTER source_type TYPE character varying(2);
ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE notifications 
  ADD CONSTRAINT chk_notification_source_type CHECK (source_type::text = 'T'::bpchar::text OR source_type::text = 'V'::bpchar::text OR source_type::text = 'C'::bpchar::text OR source_type::text = 'A'::bpchar::text OR source_type::text = 'S'::bpchar::text OR source_type::text = 'VT'::bpchar::text OR source_type::text = 'AC'::bpchar::text);

COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Value can be T (= trigger), C (= comment), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), AC (= achievement)';

CREATE OR REPLACE VIEW list_notifications as
SELECT n.id, n.source, n.source_type, n.data, n.acknowledged, n.user_id, n.triggered_on, n.digest_mode, n.cc
FROM notifications n;
END;