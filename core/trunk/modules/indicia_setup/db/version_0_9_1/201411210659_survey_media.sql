-- Table: survey_media

CREATE TABLE survey_media
(
  id serial NOT NULL,
  survey_id integer NOT NULL, -- Foreign key to the surveys table. Identifies the survey that the media is attached to.
  path character varying(200) NOT NULL, -- Path to the image file, relative to the server's image storage folder.
  caption character varying(100), -- Caption for the image.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  media_type_id integer NOT NULL DEFAULT 191, -- Foreign key to the termlists_terms table. Identifies the term which describes the type of media this record refers to,
  CONSTRAINT pk_survey_images PRIMARY KEY (id),
  CONSTRAINT fk_survey_image_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_image_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_media_type FOREIGN KEY (media_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE survey_media
  IS 'Lists images and other media files that are attached to survey records.';
COMMENT ON COLUMN survey_media.survey_id IS 'Foreign key to the surveys table. Identifies the survey that the media is attached to.';
COMMENT ON COLUMN survey_media.path IS 'Path to the media file, relative to the server''s upload folder.';
COMMENT ON COLUMN survey_media.caption IS 'Caption for the media file.';
COMMENT ON COLUMN survey_media.created_on IS 'Date this record was created.';
COMMENT ON COLUMN survey_media.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN survey_media.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN survey_media.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN survey_media.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN survey_media.media_type_id IS 'Foreign key to the termlists_terms table. Identifies the term which describes the type of media this record refers to,';

-- View: gv_survey_media

CREATE OR REPLACE VIEW gv_survey_media AS 
 SELECT sm.id, sm.path, sm.caption, sm.deleted, sm.survey_id, ctt.term AS media_type
   FROM survey_media sm
   JOIN cache_termlists_terms ctt ON ctt.id = sm.media_type_id
  WHERE sm.deleted = false;

-- View: list_survey_media

CREATE OR REPLACE VIEW list_survey_media AS 
 SELECT sm.id, sm.survey_id, sm.path, sm.caption, sm.created_on, sm.created_by_id, sm.updated_on, sm.updated_by_id, sm.deleted, s.website_id, sm.media_type_id, ctt.term AS media_type
   FROM survey_media sm
   JOIN cache_termlists_terms ctt ON ctt.id = sm.media_type_id
   JOIN surveys s ON s.id = sm.survey_id AND s.deleted = false
  WHERE sm.deleted = false;
