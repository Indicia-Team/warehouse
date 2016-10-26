DROP VIEW list_notifications;

ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE notifications
  ADD CONSTRAINT chk_notification_source_type CHECK (source_type::text = 'T'::bpchar::text
  OR source_type::text = 'V'::bpchar::text OR source_type::text = 'C'::bpchar::text
  OR source_type::text = 'A'::bpchar::text OR source_type::text = 'S'::bpchar::text
  OR source_type::text = 'VT'::bpchar::text OR source_type::text = 'M'::bpchar::text
  OR source_type::text = 'PT'::bpchar::text OR source_type::text = 'GU'::bpchar::text);

COMMENT ON COLUMN notifications.source_type
  IS
$$Defines the type of source of this notification, as described in the source. Options are:
T (= trigger)
C (= comment)
V (= verification)
A (= automated record check)
S (= species alert)
VT (= verifier task)
AC (= achievement)
GU (= pending groups user)$$;

CREATE OR REPLACE VIEW list_notifications AS
 SELECT n.id,
    n.source,
    n.source_type,
    n.data,
    n.acknowledged,
    n.user_id,
    n.triggered_on,
    n.digest_mode,
    n.cc
   FROM notifications n;