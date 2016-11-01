ALTER TABLE groups ADD COLUMN licence_id integer;
COMMENT ON COLUMN groups.licence_id IS 'ID of the licence that is associated with this group and the records submitted to it.';

ALTER TABLE groups
  ADD CONSTRAINT fk_group_licence FOREIGN KEY (licence_id) REFERENCES licences (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON CONSTRAINT fk_group_licence ON groups
  IS 'The records submitted to each group are licenced according to the linked record.';
CREATE INDEX ix_group_licence
  ON  groups(licence_id);