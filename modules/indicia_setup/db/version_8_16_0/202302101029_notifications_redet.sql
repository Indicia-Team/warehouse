ALTER TABLE notifications
  ADD COLUMN redet_taxa_taxon_list_id integer;

COMMENT ON COLUMN notifications.redet_taxa_taxon_list_id IS
  'If a comment is for a redetermination, provides the ID of the taxon it was redetermined to. '
  'No constraint added as it is just a snapshot to indicate this was a redet, so performance overhead not worthwhile.';