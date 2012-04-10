alter table notifications
  alter column source_type TYPE character(1);
  
create or replace view list_notifications as
select n.id, n.source, n.source_type, n.data, n.acknowledged, n.user_id, n.triggered_on, n.digest_mode, n.cc
from notifications n;

ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;

ALTER TABLE notifications
  ADD CONSTRAINT chk_notification_source_type CHECK (source_type::text = 'T'::bpchar::text OR source_type::text = 'V'::bpchar::text OR source_type::text = 'C'::bpchar::text);

COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Value can be T (= trigger), C (= comment), V (= verification).';