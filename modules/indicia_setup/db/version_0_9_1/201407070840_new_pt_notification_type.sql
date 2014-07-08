ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE notifications 
  ADD CONSTRAINT chk_notification_source_type CHECK (source_type::text = 'T'::bpchar::text OR source_type::text = 'V'::bpchar::text OR source_type::text = 'C'::bpchar::text OR source_type::text = 'A'::bpchar::text OR source_type::text = 'S'::bpchar::text OR source_type::text = 'VT'::bpchar::text OR source_type::text = 'M'::bpchar::text OR source_type::text = 'PT'::bpchar::text);

COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Value can be T (= trigger), C (= comment), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), M (= milestone), PT (= pending record task). When this column is altered, then the user_email_notifications_settings.notification_source_type needs updating.';


ALTER TABLE user_email_notification_settings DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE user_email_notification_settings
  ADD CONSTRAINT chk_notification_source_type CHECK (notification_source_type::text = 'T'::bpchar::text OR notification_source_type::text = 'V'::bpchar::text OR notification_source_type::text = 'C'::bpchar::text OR notification_source_type::text = 'A'::bpchar::text OR notification_source_type::text = 'S'::bpchar::text OR notification_source_type::text = 'VT'::bpchar::text OR notification_source_type::text = 'M'::bpchar::text OR notification_source_type::text = 'PT'::bpchar::text);

COMMENT ON COLUMN user_email_notification_settings.notification_source_type IS 'The notification type the setting relates to, as described in the notification Source Type. Value can be T (= trigger), C (= comment), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), M (= milestone), PT (= pending record task). Needs updating when notification.source_type constraint is altered.';