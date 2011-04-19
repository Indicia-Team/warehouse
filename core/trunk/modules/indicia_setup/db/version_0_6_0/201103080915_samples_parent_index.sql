CREATE INDEX fki_samples_samples
  ON samples
  USING btree
  (parent_id);