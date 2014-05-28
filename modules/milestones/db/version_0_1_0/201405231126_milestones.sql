CREATE OR REPLACE function f_add_milestones (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN
  CREATE TABLE milestones
  (
  id serial NOT NULL,
  title character varying(100) NOT NULL,
  count integer NOT NULL,
  entity character(1) NOT NULL,
  filter_id integer NOT NULL,
  success_message character varying(100) NOT NULL,
  website_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL, 
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,


  CONSTRAINT pk_milestones PRIMARY KEY (id),
  CONSTRAINT uq_milestones_title
    UNIQUE(title),
  CONSTRAINT fk_milestones_filter FOREIGN KEY (filter_id)
        REFERENCES filters (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_milestones_website FOREIGN KEY (website_id)
        REFERENCES websites (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_milestones_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_milestones_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  );
  success := TRUE;
EXCEPTION
    WHEN duplicate_table THEN RAISE NOTICE 'table exists.';

COMMENT ON TABLE milestones
  IS 'Contains data relating to website milestones. This is a count of different data on a website so that when various users pass that milestone then they are informed.';
COMMENT ON COLUMN milestones.title IS 'Milestone title. Must be unique.';
COMMENT ON COLUMN milestones.count IS 'A count to track progress against the milestone.';
COMMENT ON COLUMN milestones.entity IS 'Indication of what is being counted: T, O or M for taxa, occurrences or media files.';
COMMENT ON COLUMN milestones.filter_id IS 'The filter linked to the milestone. Foreign key to the filters table.';
COMMENT ON COLUMN milestones.success_message IS 'Message to display upon successful milestone completion.';
COMMENT ON COLUMN milestones.website_id IS 'The website linked to the milestone. Foreign key to the websites table.';
COMMENT ON COLUMN milestones.created_on IS 'Date this record was created.';
COMMENT ON COLUMN milestones.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN milestones.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN milestones.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN milestones.deleted IS 'Has this record been deleted?';
END;


BEGIN
  CREATE TABLE milestone_awards
  (
  id serial NOT NULL,
  milestone_id integer NOT NULL,
  user_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL, 
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,


  CONSTRAINT pk_milestone_awards PRIMARY KEY (id),
  CONSTRAINT fk_milestone_awards_milestone FOREIGN KEY (milestone_id)
        REFERENCES milestones (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_milestone_awards_user FOREIGN KEY (user_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT uq_milestone_awards_milestone_user
    UNIQUE(milestone_id,user_id),
  CONSTRAINT fk_milestones_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_milestones_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  );
  success := TRUE;
EXCEPTION
    WHEN duplicate_table THEN RAISE NOTICE 'table exists.';

COMMENT ON TABLE milestone_awards
  IS 'Track when a user has reached a particular milestone goal so that they are not notified of the completion more than once.';
COMMENT ON COLUMN milestone_awards.milestone_id IS 'ID of the milestone that the user completed.';
COMMENT ON COLUMN milestone_awards.user_id IS 'ID of the user who completed the milestone.';
COMMENT ON COLUMN milestone_awards.created_on IS 'Date this record was created.';
COMMENT ON COLUMN milestone_awards.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN milestone_awards.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN milestone_awards.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN milestone_awards.deleted IS 'Has this record been deleted?';
END;


BEGIN
  CREATE OR REPLACE VIEW gv_milestones AS 
    SELECT m.id, m.title, m.count,m.entity,m.website_id,m.filter_id
      FROM milestones m
    WHERE m.deleted = false;
END;

END;
$func$;

SELECT f_add_milestones();

DROP FUNCTION f_add_milestones();