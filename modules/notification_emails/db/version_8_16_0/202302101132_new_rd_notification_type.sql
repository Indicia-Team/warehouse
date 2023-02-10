ALTER TABLE user_email_notification_settings DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE user_email_notification_settings
  ADD CONSTRAINT chk_notification_source_type CHECK (notification_source_type IN ('T', 'V', 'C', 'Q', 'RD', 'A', 'S', 'VT', 'M', 'PT', 'GU'));

COMMENT ON COLUMN website_email_notification_settings.notification_source_type IS 'The notification type the setting relates to, as described in the notification Source Type. '
  'Value can be T (= trigger), C (= comment), Q (= query), RD (= redetermination), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), M (= milestone), PT (= pending record task). Needs updating when notification.source_type constraint is altered.';

ALTER TABLE website_email_notification_settings DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE website_email_notification_settings
  ADD CONSTRAINT chk_notification_source_type CHECK (notification_source_type IN ('T', 'V', 'C', 'Q', 'RD', 'A', 'S', 'VT', 'M', 'PT', 'GU'));

COMMENT ON COLUMN website_email_notification_settings.notification_source_type IS 'The notification type the setting relates to, as described in the notification Source Type. Value can be T (= trigger), C (= comment), Q (= query), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), M (= milestone), PT (= pending record task). Needs updating when notification.source_type constraint is altered.';