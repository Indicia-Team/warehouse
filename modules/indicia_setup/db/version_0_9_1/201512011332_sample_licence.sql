ALTER TABLE samples ADD COLUMN licence_id integer;
COMMENT ON COLUMN samples.licence_id IS 'ID of the licence that is associated with this sample and the records it contains.';

ALTER TABLE samples
  ADD CONSTRAINT fk_sample_licence FOREIGN KEY (licence_id) REFERENCES licences (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON CONSTRAINT fk_sample_licence ON samples
  IS 'The records within each sample are licenced according to the linked record.';
CREATE INDEX ix_sample_licence
  ON samples(licence_id);