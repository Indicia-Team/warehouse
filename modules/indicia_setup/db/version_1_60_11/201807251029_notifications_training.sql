CREATE OR REPLACE VIEW list_notifications AS
  SELECT n.id,
    n.source,
    n.source_type,
    n.data,
    n.acknowledged,
    n.user_id,
    n.triggered_on,
    n.digest_mode,
    n.cc,
    o.training
   FROM notifications n
   LEFT JOIN occurrences o on o.id=n.linked_id;