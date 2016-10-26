ALTER TABLE user_email_notification_settings DROP CONSTRAINT chk_notification_source_type;

ALTER TABLE user_email_notification_settings
  ADD CONSTRAINT chk_notification_source_type CHECK (notification_source_type::text = 'T'::bpchar::text
  OR notification_source_type::text = 'V'::bpchar::text OR notification_source_type::text = 'C'::bpchar::text
  OR notification_source_type::text = 'A'::bpchar::text OR notification_source_type::text = 'S'::bpchar::text
  OR notification_source_type::text = 'VT'::bpchar::text OR notification_source_type::text = 'M'::bpchar::text
  OR notification_source_type::text = 'PT'::bpchar::text OR notification_source_type::text = 'GU'::bpchar::text);

COMMENT ON COLUMN user_email_notification_settings.notification_source_type
  IS
$$The notification type the setting relates to, as described in the notification Source Type. Options are:
T (= trigger)
C (= comment)
V (= verification)
A (= automated record check)
S (= species alert)
VT (= verifier task)
M (= milestone)
PT (= pending record task)
GU (= groups_user pending request).
Needs updating when notification.source_type constraint is altered.$$;

-- Some additional comments missing from the DB at the moment
COMMENT ON COLUMN user_email_notification_settings.user_id
  IS 'Foreign key to the users table. Identifies the user this setting applies to.';
COMMENT ON COLUMN user_email_notification_settings.notification_frequency
  IS
$$Frequency of sending out of this type of notification. Options are:
IH (immediate or hourly)
D (daily)
W (weekly).$$;
COMMENT ON COLUMN user_email_notification_settings.created_on
  IS 'Date this record was created.';
COMMENT ON COLUMN user_email_notification_settings.created_by_id
  IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN user_email_notification_settings.updated_on
  IS 'Date this record was last updated.';
COMMENT ON COLUMN user_email_notification_settings.updated_by_id
  IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN user_email_notification_settings.deleted
  IS 'Has this record been deleted?';

-- Insert settings for existing users, but only if the user has already opted into some notification emails. Use the
-- maximum frequency the user has opted for.
INSERT INTO user_email_notification_settings (user_id, notification_source_type, notification_frequency, created_on,
  created_by_id, updated_on, updated_by_id)
SELECT user_id, 'GU' AS notification_source_type,
  CASE max(case notification_frequency WHEN 'IH' THEN 3 WHEN 'D' THEN 2 ELSE 1 END)
    WHEN 3 THEN 'IH'
    WHEN 2 THEN 'D'
    ELSE 'W'
  END AS notification_frequency,
  now(), 1 AS updated_by_id, now(), 1 AS updated_by_id
FROM user_email_notification_settings
GROUP BY user_id;