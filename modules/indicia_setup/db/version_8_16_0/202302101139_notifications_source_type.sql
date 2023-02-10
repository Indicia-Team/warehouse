DROP VIEW list_notifications;

ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;
ALTER TABLE notifications
  ADD CONSTRAINT chk_notification_source_type CHECK (
    source_type::text = 'A'::bpchar::text
    OR source_type::text = 'C'::bpchar::text
    OR source_type::text = 'GU'::bpchar::text
    OR source_type::text = 'M'::bpchar::text
    OR source_type::text = 'PT'::bpchar::text
    OR source_type::text = 'Q'::bpchar::text
    OR source_type::text = 'RD'::bpchar::text
    OR source_type::text = 'S'::bpchar::text
    OR source_type::text = 'T'::bpchar::text
    OR source_type::text = 'V'::bpchar::text
    OR source_type::text = 'VT'::bpchar::text
  );

COMMENT ON COLUMN notifications.source_type
  IS
$$Indiciates the source of this notification. Options are:
A (= automated record check)
C (= comment)
GU (= =  groups user)
M (= moderation)
PT (= pending record task)
Q (= query)
RD (= redetermination)
S (= species alert)
T (= trigger)
V (= verification)
VT (= verifier task)$$;

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
