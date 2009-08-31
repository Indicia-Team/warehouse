ALTER TABLE occurrences ADD COLUMN website_id integer NOT NULL;
ALTER TABLE occurrences ADD COLUMN external_key character varying(50);
ALTER TABLE occurrences ADD COLUMN "comment" text;

ALTER TABLE occurrences
  ADD CONSTRAINT fk_occurrence_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrences.website_id IS 'Foreign key to the websites table. Website that the occurrence record is linked to.';
COMMENT ON COLUMN occurrences.external_key IS 'For occurrences imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';
COMMENT ON COLUMN occurrences."comment" IS 'User'' comment on data entry of the occurrence.';

ALTER TABLE locations ADD COLUMN "comment" text;
ALTER TABLE locations ADD COLUMN external_key character varying(50);

COMMENT ON COLUMN locations."comment" IS 'Comment regarding the location.';
COMMENT ON COLUMN locations.external_key IS 'For locations imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';

ALTER TABLE samples ADD COLUMN "comment" text;
ALTER TABLE samples ADD COLUMN external_key character varying(50);

COMMENT ON COLUMN samples."comment" IS 'Comment regarding the sample.';
COMMENT ON COLUMN samples.external_key IS 'For samples imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';

ALTER TABLE occurrence_comments ADD COLUMN email_address character varying(50);
ALTER TABLE occurrence_comments ALTER COLUMN created_by_id DROP NOT NULL;
ALTER TABLE occurrence_comments ALTER COLUMN updated_by_id DROP NOT NULL;

COMMENT ON COLUMN occurrence_comments.email_address IS 'Email of user who created the comment, if the user was not logged in but supplied an email address.';
COMMENT ON COLUMN occurrence_comments.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';
COMMENT ON COLUMN occurrence_comments.created_by_id IS 'Foreign key to the users table (creator), if user was logged in when comment created.';

ALTER TABLE sample_attributes ADD COLUMN applies_to_location boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN sample_attributes.applies_to_location IS 'For attributes that are gathered which pertain to the site or location rather than the specific sample, this flag is set to true.';

ALTER TABLE websites ADD COLUMN default_survey_id integer;
COMMENT ON COLUMN websites.default_survey_id IS 'Survey which records for this website are created under if not specified by the data entry form.';

ALTER TABLE websites
  ADD CONSTRAINT fk_website_default_survey FOREIGN KEY (default_survey_id)
      REFERENCES surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE users_websites ADD COLUMN preferred_sref_system character varying(10);
COMMENT ON COLUMN users_websites.preferred_sref_system IS 'Spatial reference system used for data entry and viewing of spatial data by this user of the website.';

COMMENT ON TABLE occurrence_comments IS 'List of comments regarding the occurrence posted by users viewing the occurrence subsequent to initial data entry.';
COMMENT ON TABLE site_roles IS 'List of roles that exist at the online recording website level.';
COMMENT ON TABLE "system" IS 'Contains system versioning information.';
COMMENT ON TABLE user_tokens IS 'Contains tokens stored in cookies used to authenticate users on the core module.';

ALTER TABLE samples ADD COLUMN sample_method_id integer;
COMMENT ON COLUMN samples.sample_method_id IS 'Foreign key to the termlists_terms table. Identifies the term which describes the sampling method.';

ALTER TABLE samples
  ADD CONSTRAINT fk_sample_method FOREIGN KEY (sample_method_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE occurrences DROP CONSTRAINT fk_occurrence_taxon;
ALTER TABLE occurrences DROP COLUMN taxon_id;

ALTER TABLE occurrences
   ADD COLUMN taxa_taxon_list_id integer;

ALTER TABLE occurrences
  ADD CONSTRAINT fk_occurrence_taxa_taxon_list FOREIGN KEY (taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrences.taxa_taxon_list_id IS 'Foreign key to the taxa_taxon_lists table. Identifies the taxa on a taxon list that this occurrence is a record of.';

CREATE INDEX fki_occurrence_taxa_taxon_list
  ON occurrences
  USING btree
  (taxa_taxon_list_id);