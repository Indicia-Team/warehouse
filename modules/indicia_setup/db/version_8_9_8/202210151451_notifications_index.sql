-- #slow script#
CREATE INDEX IF NOT EXISTS ix_notifications_linked_user_species_alerts ON notifications(user_id, linked_id) WHERE source='species alerts';