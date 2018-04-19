
ALTER TABLE occurrence_media
  ADD COLUMN licence_id integer,
  ADD CONSTRAINT fk_occurrence_media_licence FOREIGN KEY (licence_id)
      REFERENCES licences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE sample_media
  ADD COLUMN licence_id integer,
  ADD CONSTRAINT fk_sample_media_licence FOREIGN KEY (licence_id)
      REFERENCES licences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE location_media
  ADD COLUMN licence_id integer,
  ADD CONSTRAINT fk_location_media_licence FOREIGN KEY (licence_id)
      REFERENCES licences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE taxon_media
  ADD COLUMN licence_id integer,
  ADD CONSTRAINT fk_taxon_media_licence FOREIGN KEY (licence_id)
      REFERENCES licences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE survey_media
  ADD COLUMN licence_id integer,
  ADD CONSTRAINT fk_survey_media_licence FOREIGN KEY (licence_id)
      REFERENCES licences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_media.licence_id IS 'ID of the licence that is associated with this media file if set.';
COMMENT ON COLUMN sample_media.licence_id IS 'ID of the licence that is associated with this media file if set.';
COMMENT ON COLUMN location_media.licence_id IS 'ID of the licence that is associated with this media file if set.';
COMMENT ON COLUMN taxon_media.licence_id IS 'ID of the licence that is associated with this media file if set.';
COMMENT ON COLUMN survey_media.licence_id IS 'ID of the licence that is associated with this media file if set.';
