CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN

CREATE TABLE filters
(
   id serial NOT NULL, 
   title character varying NOT NULL, 
   description character varying, 
   definition character varying NOT NULL, 
   sharing character,
   public boolean DEFAULT false,
   defines_permissions boolean DEFAULT false,
   created_on timestamp without time zone NOT NULL, 
   created_by_id integer NOT NULL, 
   updated_on timestamp without time zone NOT NULL, 
   updated_by_id integer NOT NULL, 
   deleted boolean NOT NULL DEFAULT false,
   CONSTRAINT pk_filters PRIMARY KEY (id ),
   CONSTRAINT fk_filter_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
   CONSTRAINT fk_filter_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
   CONSTRAINT chk_filter_sharing_options CHECK ((sharing = ANY (ARRAY['R'::bpchar, 'P'::bpchar, 'V'::bpchar, 'D'::bpchar, 'M'::bpchar])))
   
) 
WITH (
  OIDS = FALSE
)
;

EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

COMMENT ON TABLE filters IS 'List of reusable filters, which define lists of records available to users for reporting, verification, download or other record sharing tasks.';
COMMENT ON COLUMN filters.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN filters.title IS 'Title of the filter';
COMMENT ON COLUMN filters.description IS 'Optional description of the filter.';
COMMENT ON COLUMN filters.definition IS 'A JSON string holding a definition of the filter parameters. Decodes to an array of key value pairs suitable for passing to a report supporting the standard report parameters.';
COMMENT ON COLUMN filters.sharing IS 'Identifies the record sharing task that this filter is for. Options are R(eporting), P(eer review), V(erification), D(ata flow), M(oderation).';
COMMENT ON COLUMN filters.public IS 'Flag indicating when a filter is publically available and discoverable. Non-public filters can only be allocated to users by the creator.';
COMMENT ON COLUMN filters.defines_permissions IS 'Flag indicating when a filter defines a limited set of permissions for the user having the filter. E.g. this could describe a set of records that a user is able to verify, any number of sets can be defined per user.';
COMMENT ON COLUMN filters.created_on IS 'Date this record was created.';
COMMENT ON COLUMN filters.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN filters.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN filters.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN filters.deleted IS 'Has this record been deleted?';

COMMENT ON CONSTRAINT chk_filter_sharing_options ON filters IS 'Checks that filter sharing option has one of the possible settings.';

BEGIN

CREATE TABLE filters_users
(
  id serial NOT NULL,
  filter_id integer NOT NULL, 
  user_id integer NOT NULL, 
  created_on timestamp without time zone NOT NULL, 
  created_by_id integer NOT NULL, 
  deleted boolean NOT NULL DEFAULT false, 
  CONSTRAINT pk_filters_users PRIMARY KEY (id ),
  CONSTRAINT fk_filters_user_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_filters_users_filters FOREIGN KEY (filter_id)
      REFERENCES filters (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_filters_users_users FOREIGN KEY (user_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

COMMENT ON TABLE filters_users
  IS 'Join table which identifies the filters that are available to each user.';
COMMENT ON COLUMN filters_users.filter_id IS 'Foreign key to the filters table. Identifies the filter that is available to the user.';
COMMENT ON COLUMN filters_users.user_id IS 'Foreign key to the users table. Identifies the user that the filter is available to.';
COMMENT ON COLUMN filters_users.created_on IS 'Date this record was created.';
COMMENT ON COLUMN filters_users.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN filters_users.deleted IS 'Has this record been deleted?';

IF NOT EXISTS(SELECT 1 FROM pg_constraint WHERE conname='uc_filter_name') THEN

  ALTER TABLE filters ADD CONSTRAINT uc_filter_name UNIQUE (title, sharing, created_by_id);
  COMMENT ON CONSTRAINT uc_filter_name ON filters IS 'Filter name must be unique per combination of record sharing task and creator.';

END IF;

CREATE OR REPLACE VIEW list_filters AS 
  SELECT f.id, f.title, f.description, f.definition, f.sharing, f.public, f.created_by_id
  FROM filters f
  WHERE f.deleted = false;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();
