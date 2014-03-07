CREATE OR REPLACE function f_add_user_trusts (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN
  CREATE TABLE user_trusts
  (
  id serial NOT NULL,
  user_id integer NOT NULL, 
  survey_id integer,
  location_id integer,
  taxon_group_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL, 
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,

  CONSTRAINT pk_user_trusts PRIMARY KEY (id),
  CONSTRAINT fk_user_trust_user FOREIGN KEY (user_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_trust_survey FOREIGN KEY (survey_id)
        REFERENCES surveys (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_trust_location FOREIGN KEY (location_id)
        REFERENCES locations (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_trust_taxon_group FOREIGN KEY (taxon_group_id)
        REFERENCES taxon_groups (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_trust_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_trust_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  );
  success := TRUE;
EXCEPTION
    WHEN duplicate_table THEN RAISE NOTICE 'table exists.';
END;

COMMENT ON TABLE user_trusts
  IS 'Lists the survey, location and taxon group combinations that a user is a trusted verifier for. If one or more of the columns is null then expertise is unlimited for that item. The logic is AND, e.g. someone who is associated with a Woodland Survey, Insects and Dorset is only considered as trusted with these 3 in combination.';
COMMENT ON COLUMN user_trusts.user_id IS 'User for whom this trust applies. Foreign key to the users table.';
COMMENT ON COLUMN user_trusts.survey_id IS 'The survey associated with this trust. Foreign key to the surveys table.';
COMMENT ON COLUMN user_trusts.location_id IS 'The location associated with this trust. Foreign key to the locations table.';
COMMENT ON COLUMN user_trusts.taxon_group_id IS 'The taxon_group associated with this trust. Foreign key to the taxon_groups table.';
COMMENT ON COLUMN user_trusts.created_on IS 'Date this record was created.';
COMMENT ON COLUMN user_trusts.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN user_trusts.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN user_trusts.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN user_trusts.deleted IS 'Has this record been deleted?';

END
$func$;

SELECT f_add_user_trusts();

DROP FUNCTION f_add_user_trusts();