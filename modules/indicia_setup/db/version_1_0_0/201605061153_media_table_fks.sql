-- Clean up media that might have crept in which violates foreign keys that were missed when creating these tables.
-- Hopefully there won't be any.
DELETE FROM occurrence_media WHERE id in (
  SELECT m.id
  FROM occurrence_media m
  LEFT JOIN occurrences o ON o.id=m.occurrence_id
  WHERE o.id IS NULL
);

DELETE FROM sample_media WHERE id in (
  SELECT m.id
  FROM sample_media m
  LEFT JOIN samples s ON s.id=m.sample_id
  WHERE s.id IS NULL
);

DELETE FROM survey_media WHERE id in (
  SELECT m.id
  FROM survey_media m
  LEFT JOIN surveys s ON s.id=m.survey_id
  WHERE s.id IS NULL
);

DELETE FROM taxon_media WHERE id in (
  SELECT m.id
  FROM taxon_media m
  LEFT JOIN taxon_meanings t ON t.id=m.taxon_meaning_id
  WHERE t.id IS NULL
);

-- Now add the required constraints to join media to their parent tables, plus covering indexes where required.
ALTER TABLE occurrence_media
  ADD CONSTRAINT fk_occurrence_media_occurrences FOREIGN KEY (occurrence_id) REFERENCES occurrences (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
-- occurrence_media already has the required index.

ALTER TABLE sample_media
  ADD CONSTRAINT fk_sample_media_samples FOREIGN KEY (sample_id) REFERENCES samples (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
CREATE INDEX fki_sample_media_samples
  ON sample_media(sample_id);

ALTER TABLE survey_media
  ADD CONSTRAINT fk_survey_media_surveys FOREIGN KEY (survey_id) REFERENCES surveys (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
CREATE INDEX fki_survey_media_surveys
  ON survey_media(survey_id);

ALTER TABLE taxon_media
  ADD CONSTRAINT fk_taxon_media_taxon_meanings FOREIGN KEY (taxon_meaning_id) REFERENCES taxon_meanings (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
CREATE INDEX fki_taxon_media_taxon_meanings
  ON taxon_media(taxon_meaning_id);